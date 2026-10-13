<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanExporterContact;
use App\Support\ContactParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Eksportyor firmalarga mas'ul xodimni qo'lda biriktirish ("Xodim qo'shish").
 * Firma STIR (exporter_inn) bo'yicha aniqlanadi; xodim shu firmaning barcha eksport qatorlariga yoziladi.
 */
class UzbekistanExporterContactController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/uzbekistan-export/exporters",
     *     summary="Eksportyor firmalar (STIR bo'yicha takrorlanmas) va biriktirilgan xodim",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="q", in="query", description="Firma nomi yoki STIR bo'yicha qidirish", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="[{inn, name, names_count, rows, contact}]")
     * )
     */
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:255']);

        $contacts = UzbekistanExporterContact::all()->keyBy('exporter_inn');

        // har bir STIR uchun eng ko'p uchragan nom va qatorlar soni
        $firms = UzbekistanExport::query()
            ->whereNotNull('exporter_inn')
            ->where('exporter_inn', '!=', '')
            ->selectRaw('exporter_inn, exporter_name, count(*) as rows_count')
            ->groupBy('exporter_inn', 'exporter_name')
            ->toBase()
            ->get()
            ->groupBy('exporter_inn')
            ->map(function ($names, $inn) use ($contacts) {
                $main = $names->sortByDesc('rows_count')->first();
                $contact = $contacts->get($inn);

                return [
                    'inn' => (string) $inn,
                    'name' => $main->exporter_name,
                    'names' => $names->pluck('exporter_name')->filter()->values(),
                    'rows' => (int) $names->sum('rows_count'),
                    'contact' => $contact?->only(['full_name', 'position', 'phone', 'updated_at']),
                ];
            });

        if ($request->filled('q')) {
            $q = mb_strtolower(trim($request->q));
            $firms = $firms->filter(fn ($f) => str_contains($f['inn'], $q)
                || $f['names']->contains(fn ($n) => str_contains(mb_strtolower($n), $q)));
        }

        return response()->json([
            'status' => true,
            'data' => $firms->sortBy(fn ($f) => mb_strtolower((string) $f['name']))->values(),
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/uzbekistan-export/exporters/{inn}/contact",
     *     summary="Firmaga xodim biriktirish (firmaning barcha qatorlariga yoziladi)",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="inn", in="path", required=true, description="Eksportyor STIR", @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name"},
     *             @OA\Property(property="full_name", type="string", description="Ism familiya"),
     *             @OA\Property(property="position", type="string", description="Lavozim"),
     *             @OA\Property(property="phone", type="string", description="Telefon")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Saqlandi; updated_rows — yangilangan qatorlar soni"),
     *     @OA\Response(response=404, description="Bunday STIR'li eksportyor yo'q")
     * )
     */
    public function save(Request $request, string $inn)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
        ], [
            'full_name.required' => 'Xodimning ism familiyasini kiriting',
            'phone.regex' => "Telefon raqamida faqat raqamlar, +, -, ( ) bo'lishi mumkin",
        ]);

        $name = $this->firmName($inn);
        if ($name === false) {
            return response()->json(['status' => false, 'message' => "Bunday STIR'li eksportyor topilmadi"], 404);
        }

        $phone = trim((string) ($data['phone'] ?? ''));

        [$contact, $updated] = DB::transaction(function () use ($request, $inn, $name, $data, $phone) {
            $contact = UzbekistanExporterContact::updateOrCreate(['exporter_inn' => $inn], [
                'exporter_name' => $name,
                'full_name' => trim($data['full_name']),
                'position' => filled($data['position'] ?? null) ? trim($data['position']) : null,
                // O'zbekiston raqami bo'lsa +998XXXXXXXXX ko'rinishiga keltiriladi
                'phone' => $phone === '' ? null : (ContactParser::phone($phone) ?? $phone),
                'user_id' => $request->user()?->id,
            ]);

            $updated = UzbekistanExport::where('exporter_inn', $inn)->update($contact->toExportColumns());

            return [$contact, $updated];
        });

        return response()->json([
            'status' => true,
            'message' => "Xodim saqlandi: $updated ta qatorga yozildi",
            'updated_rows' => $updated,
            'data' => $contact,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/uzbekistan-export/exporters/{inn}/contact",
     *     summary="Firmadan xodimni olib tashlash (qatorlardan ham tozalanadi)",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="inn", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="O'chirildi")
     * )
     */
    public function destroy(string $inn)
    {
        $updated = DB::transaction(function () use ($inn) {
            UzbekistanExporterContact::where('exporter_inn', $inn)->delete();

            return UzbekistanExport::where('exporter_inn', $inn)->update(UzbekistanExporterContact::emptyExportColumns());
        });

        return response()->json([
            'status' => true,
            'message' => "Xodim olib tashlandi: $updated ta qatordan tozalandi",
            'updated_rows' => $updated,
        ]);
    }

    /** Firmaning eng ko'p uchragan nomi; firma bo'lmasa — false */
    private function firmName(string $inn): string|null|false
    {
        $row = UzbekistanExport::where('exporter_inn', $inn)
            ->selectRaw('exporter_name, count(*) as rows_count')
            ->groupBy('exporter_name')
            ->orderByDesc('rows_count')
            ->toBase()
            ->first();

        return $row ? $row->exporter_name : false;
    }
}
