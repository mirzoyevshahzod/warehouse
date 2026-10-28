<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanExporterContact;
use App\Models\UzbekistanExporterDeclarant;
use App\Support\ContactParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Eksportyor firmalarga mas'ul xodim va deklarantni qo'lda biriktirish ("Xodim qo'shish" oynasi, ikki tab).
 * Firma STIR (exporter_inn) bo'yicha aniqlanadi; xodim/deklarant shu firmaning barcha eksport qatorlariga yoziladi.
 */
class UzbekistanExporterContactController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/uzbekistan-export/exporters",
     *     summary="Eksportyor firmalar (STIR bo'yicha takrorlanmas) va biriktirilgan xodim",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="q", in="query", description="Firma nomi yoki STIR bo'yicha qidirish", required=false, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="[{inn, name, names, rows, contact, declarant}] — qatorlar soni bo'yicha kamayish tartibida")
     * )
     */
    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:255']);

        $contacts = UzbekistanExporterContact::all()->keyBy('exporter_inn');
        $declarants = UzbekistanExporterDeclarant::all()->keyBy('exporter_inn');

        // har bir STIR uchun eng ko'p uchragan nom va qatorlar soni
        $firms = UzbekistanExport::query()
            ->whereNotNull('exporter_inn')
            ->where('exporter_inn', '!=', '')
            ->selectRaw('exporter_inn, exporter_name, count(*) as rows_count')
            ->groupBy('exporter_inn', 'exporter_name')
            ->toBase()
            ->get()
            ->groupBy('exporter_inn')
            ->map(function ($names, $inn) use ($contacts, $declarants) {
                $main = $names->sortByDesc('rows_count')->first();
                $contact = $contacts->get($inn);
                $declarant = $declarants->get($inn);

                return [
                    'inn' => (string) $inn,
                    'name' => $main->exporter_name,
                    'names' => $names->pluck('exporter_name')->filter()->values(),
                    'rows' => (int) $names->sum('rows_count'),
                    'contact' => $contact?->only(['full_name', 'position', 'phone', 'updated_at']),
                    'declarant' => $declarant?->only(['full_name', 'phone', 'email', 'company', 'updated_at']),
                ];
            });

        if ($request->filled('q')) {
            $q = mb_strtolower(trim($request->q));
            $firms = $firms->filter(fn ($f) => str_contains($f['inn'], $q)
                || $f['names']->contains(fn ($n) => str_contains(mb_strtolower($n), $q)));
        }

        // eng ko'p qatorli firmalar birinchi; teng bo'lsa — nom bo'yicha
        $firms = $firms->sort(fn ($a, $b) => [$b['rows'], mb_strtolower((string) $a['name'])]
            <=> [$a['rows'], mb_strtolower((string) $b['name'])]);

        return response()->json([
            'status' => true,
            'data' => $firms->values(),
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

        [$contact, $updated] = DB::transaction(function () use ($request, $inn, $name, $data) {
            $contact = UzbekistanExporterContact::updateOrCreate(['exporter_inn' => $inn], [
                'exporter_name' => $name,
                'full_name' => trim($data['full_name']),
                'position' => filled($data['position'] ?? null) ? trim($data['position']) : null,
                'phone' => $this->phone($data['phone'] ?? null),
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

    /**
     * @OA\Put(
     *     path="/api/uzbekistan-export/exporters/{inn}/declarant",
     *     summary="Firmaga deklarant biriktirish (firmaning barcha qatorlariga yoziladi)",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="inn", in="path", required=true, description="Eksportyor STIR", @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name"},
     *             @OA\Property(property="full_name", type="string", description="Deklarant ismi"),
     *             @OA\Property(property="phone", type="string", description="Telefon"),
     *             @OA\Property(property="email", type="string", description="Email"),
     *             @OA\Property(property="company", type="string", description="Deklarant kompaniyasi")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Saqlandi; updated_rows — yangilangan qatorlar soni"),
     *     @OA\Response(response=404, description="Bunday STIR'li eksportyor yo'q")
     * )
     */
    public function saveDeclarant(Request $request, string $inn)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => 'nullable|email|max:255',
            'company' => 'nullable|string|max:255',
        ], [
            'full_name.required' => 'Deklarant ismini kiriting',
            'phone.regex' => "Telefon raqamida faqat raqamlar, +, -, ( ) bo'lishi mumkin",
            'email.email' => "Email noto'g'ri kiritilgan",
        ]);

        $name = $this->firmName($inn);
        if ($name === false) {
            return response()->json(['status' => false, 'message' => "Bunday STIR'li eksportyor topilmadi"], 404);
        }

        [$declarant, $updated] = DB::transaction(function () use ($request, $inn, $name, $data) {
            $declarant = UzbekistanExporterDeclarant::updateOrCreate(['exporter_inn' => $inn], [
                'exporter_name' => $name,
                'full_name' => trim($data['full_name']),
                'phone' => $this->phone($data['phone'] ?? null),
                'email' => filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null,
                'company' => filled($data['company'] ?? null) ? trim($data['company']) : null,
                'user_id' => $request->user()?->id,
            ]);

            $updated = UzbekistanExport::where('exporter_inn', $inn)->update($declarant->toExportColumns());

            return [$declarant, $updated];
        });

        return response()->json([
            'status' => true,
            'message' => "Deklarant saqlandi: $updated ta qatorga yozildi",
            'updated_rows' => $updated,
            'data' => $declarant,
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/uzbekistan-export/exporters/{inn}/declarant",
     *     summary="Firmadan deklarantni olib tashlash (qatorlardan ham tozalanadi)",
     *     tags={"Uzbekistan Export"},
     *     @OA\Parameter(name="inn", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="O'chirildi")
     * )
     */
    public function destroyDeclarant(string $inn)
    {
        $updated = DB::transaction(function () use ($inn) {
            UzbekistanExporterDeclarant::where('exporter_inn', $inn)->delete();

            return UzbekistanExport::where('exporter_inn', $inn)->update(UzbekistanExporterDeclarant::emptyExportColumns());
        });

        return response()->json([
            'status' => true,
            'message' => "Deklarant olib tashlandi: $updated ta qatordan tozalandi",
            'updated_rows' => $updated,
        ]);
    }

    /** O'zbekiston raqami bo'lsa +998XXXXXXXXX ko'rinishiga keltiriladi; bo'sh bo'lsa — null */
    private function phone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return $phone === '' ? null : (ContactParser::phone($phone) ?? $phone);
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
