<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ExportUzbekistanFile;
use App\Jobs\ImportUzbekistanFile;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use App\Support\ExcelFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UzbekistanImportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import",
     *     summary="O'zbekiston import ro'yxati — Excel'dagidek filtr va saralash bilan",
     *     description="Filtr: filters[ustun][values][]=A, filters[ustun][mode]=in|not_in, filters[ustun][blanks]=1, filters[ustun][conditions][0][op]=contains&filters[ustun][conditions][0][value]=x, filters[ustun][logic]=and|or. Saralash: sort[]=-net_weight_kg&sort[]=importer_name. Ustunlar va operatorlar: /api/uzbekistan-import/columns",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="filters[importer_name][values][]", in="query", description="Belgilangan qiymatlar", required=false, @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Parameter(name="filters[importer_name][mode]", in="query", description="in — faqat belgilanganlar, not_in — belgilanganlardan tashqari", required=false, @OA\Schema(type="string", enum={"in","not_in"})),
     *     @OA\Parameter(name="filters[importer_name][blanks]", in="query", description="(Blanks) belgilangan", required=false, @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="filters[hs_code][conditions][0][op]", in="query", description="equals, not_equals, contains, not_contains, starts_with, not_starts_with, ends_with, not_ends_with, gt, gte, lt, lte, between, not_between, blank, not_blank", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="filters[hs_code][conditions][0][value]", in="query", description="Qiymat (* va ? — wildcard)", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort[]", in="query", description="Saralash, '-' — kamayish tartibida. Masalan: -net_weight_kg", required=false, @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Parameter(name="per_page", in="query", description="Sahifadagi yozuvlar soni (default 30, max 200)", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List qaytadi"),
     *     @OA\Response(response=422, description="Noto'g'ri filtr")
     * )
     */
    public function index(Request $request)
    {
        $params = $this->filterParams($request);

        $data = UzbekistanImport::query()
            ->filter($params)
            ->sorted($params)
            ->paginate(min((int) $request->input('per_page', 30), 200));

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/columns",
     *     summary="Ustunlar (nomi, sarlavhasi, turi), asosiy filtrlar va operatorlar",
     *     tags={"Uzbekistan Import"},
     *     @OA\Response(response=200, description="Ustunlar ro'yxati")
     * )
     */
    public function columns()
    {
        $types = UzbekistanImport::columnTypes();

        return response()->json([
            'status' => true,
            'columns' => collect(UzbekistanImport::LABELS)->map(fn ($label, $name) => [
                'name' => $name,
                'label' => $label,
                'type' => $types[$name],
            ])->values(),
            'main_filters' => UzbekistanImport::MAIN_FILTERS,
            'operators' => ExcelFilter::OPERATORS,
            'default_sort' => UzbekistanImport::DEFAULT_SORT,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/filter-options",
     *     summary="Ustunning ochiladigan ro'yxati uchun qiymatlar (Excel'dagidek o'sish tartibida, soni bilan)",
     *     description="Boshqa ustunlarda tanlangan filtrlar hisobga olinadi (filters[...] ni ham yuboring), shu ustunniki — yo'q. blanks — bo'sh kataklar soni.",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="field", in="query", description="Ustun nomi", required=true, @OA\Schema(type="string")),
     *     @OA\Parameter(name="q", in="query", description="Ro'yxat ichidan qidirish", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="limit", in="query", description="Qiymatlar soni (default 1000, max 10000)", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="{values: [{value, count}], blanks, has_more}")
     * )
     */
    public function filterOptions(Request $request)
    {
        $request->validate([
            'field' => ['required', Rule::in(array_keys(UzbekistanImport::LABELS))],
            'q' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $options = UzbekistanImport::excelFilter()->options(
            UzbekistanImport::query(),
            $request->field,
            $this->filterParams($request),
            $request->q,
            (int) $request->input('limit', 1000),
        );

        return response()->json([
            'status' => true,
            'data' => $options
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/uzbekistan-import/import",
     *     summary="Excel fayl yuklash (fonda, queue orqali)",
     *     description="Fayl saqlanadi va queue'ga qo'yiladi. Holatini /api/uzbekistan-import/tasks/{id} orqali kuzating.",
     *     tags={"Uzbekistan Import"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary", description="Download_UZBEKISTAN_Import_Trade_Data_*.xlsx")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=202, description="Vazifa yaratildi")
     * )
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:512000'
        ]);

        $file = $request->file('file');
        $path = $file->storeAs('uzbekistan/imports', uniqid('import_', true).'.xlsx', 'local');

        $task = UzbekistanImportTask::create([
            'type' => UzbekistanImportTask::TYPE_IMPORT,
            'status' => UzbekistanImportTask::STATUS_PENDING,
            'user_id' => $request->user()?->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);

        ImportUzbekistanFile::dispatch($task);

        return response()->json([
            'status' => true,
            'message' => 'Fayl qabul qilindi, fonda yuklanmoqda',
            'data' => $task
        ], 202);
    }

    /**
     * @OA\Post(
     *     path="/api/uzbekistan-import/export",
     *     summary="Excel export (fonda, queue orqali)",
     *     description="index bilan bir xil filters va sort parametrlarini qabul qiladi (jadvalda ko'ringan tartibda yoziladi). Tayyor bo'lgach /api/uzbekistan-import/tasks/{id}/download orqali yuklab olinadi.",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="filters[importer_name][values][]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Parameter(name="sort[]", in="query", required=false, @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Response(response=202, description="Vazifa yaratildi")
     * )
     */
    public function export(Request $request)
    {
        $task = UzbekistanImportTask::create([
            'type' => UzbekistanImportTask::TYPE_EXPORT,
            'status' => UzbekistanImportTask::STATUS_PENDING,
            'user_id' => $request->user()?->id,
            'params' => $this->filterParams($request),
        ]);
        $task->update(['original_name' => 'uzbekistan_import_'.$task->id.'_'.now()->format('Y_m_d_His').'.xlsx']);

        ExportUzbekistanFile::dispatch($task);

        return response()->json([
            'status' => true,
            'message' => 'Export fonda tayyorlanmoqda',
            'data' => $task
        ], 202);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/tasks",
     *     summary="Import/export vazifalari ro'yxati",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"import","export"})),
     *     @OA\Response(response=200, description="List qaytadi")
     * )
     */
    public function tasks(Request $request)
    {
        $request->validate([
            'type' => ['nullable', Rule::in([UzbekistanImportTask::TYPE_IMPORT, UzbekistanImportTask::TYPE_EXPORT])],
        ]);

        $data = UzbekistanImportTask::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/tasks/{id}",
     *     summary="Vazifa holati (status, progress, xatolik)",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Vazifa")
     * )
     */
    public function task(UzbekistanImportTask $task)
    {
        return response()->json([
            'status' => true,
            'data' => $task
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/tasks/{id}/download",
     *     summary="Tayyor export faylni yuklab olish",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Excel fayl",
     *         @OA\MediaType(mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
     *     ),
     *     @OA\Response(response=404, description="Fayl hali tayyor emas yoki topilmadi")
     * )
     */
    public function download(UzbekistanImportTask $task)
    {
        if ($task->type !== UzbekistanImportTask::TYPE_EXPORT
            || $task->status !== UzbekistanImportTask::STATUS_COMPLETED
            || ! $task->file_path
            || ! Storage::disk('local')->exists($task->file_path)) {
            return response()->json([
                'status' => false,
                'message' => 'Fayl hali tayyor emas yoki topilmadi'
            ], 404);
        }

        return Storage::disk('local')->download($task->file_path, $task->original_name);
    }

    /**
     * filters va sort parametrlari (index, export va filter-options uchun umumiy). Xato bo'lsa — 422.
     */
    private function filterParams(Request $request): array
    {
        return UzbekistanImport::excelFilter()->validate($request->only(['filters', 'sort']));
    }
}
