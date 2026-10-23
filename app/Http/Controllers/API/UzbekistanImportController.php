<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Exports\ZanjeerExport;
use App\Jobs\ExportUzbekistanFile;
use App\Jobs\ImportUzbekistanFile;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use App\Support\DashboardStats;
use App\Support\ExcelFilter;
use App\Support\FirmDirectory;
use App\Support\TradeDataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * O'zbekiston savdo ma'lumotlari: import (/api/uzbekistan-import/...) va eksport (/api/uzbekistan-export/...).
 * Ikkala yo'nalish bir xil endpoint'larga ega; qaysi ma'lumot ekanligi route'dagi "dataset" dan olinadi.
 */
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

        $data = $this->model($request)::query()
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
    public function columns(Request $request)
    {
        $model = $this->model($request);
        $types = $model::columnTypes();

        return response()->json([
            'status' => true,
            'dataset' => $this->dataset($request)->value,
            'columns' => collect($model::LABELS)->map(fn ($label, $name) => [
                'name' => $name,
                'label' => $label,
                'type' => $types[$name],
            ])->values(),
            'main_filters' => $model::MAIN_FILTERS,
            'operators' => ExcelFilter::OPERATORS,
            'default_sort' => $model::DEFAULT_SORT,
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
            'field' => ['required', Rule::in(array_keys($this->model($request)::LABELS))],
            'q' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $model = $this->model($request);
        $options = $model::excelFilter()->options(
            $model::query(),
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
     * @OA\Get(
     *     path="/api/uzbekistan-import/firms",
     *     summary="Takrorlanmas firmalar (STIR bo'yicha) — nomlari, manzili, telefonlari, mas'ul xodimlari",
     *     description="Import — importyorlar, eksport (/api/uzbekistan-export/firms) — eksportyorlar. Qatorlar soni bo'yicha kamayish tartibida. Jadval filtrlari (filters[...]) hisobga olinadi.",
     *     tags={"Uzbekistan Import"},
     *     @OA\Parameter(name="q", in="query", description="Firma nomi yoki STIR bo'yicha qidirish", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="page", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", description="Default 20, max 100", required=false, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="{total, with_contacts, rows, current_page, last_page, data: [{inn, name, names, rows, last_date, address, phones, contacts, countries}]}")
     * )
     */
    public function firms(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $firms = (new FirmDirectory($this->model($request)))->paginate(
            $this->filterParams($request),
            $request->q,
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 20),
        );

        // firma ikkinchi yo'nalishda ham bormi (import qilgan firma eksport ham qilganmi va aksincha)
        $other = new FirmDirectory($this->dataset($request)->other()->model());
        $inOther = $other->scope()
            ->whereIn($other->innColumn(), $firms['data']->pluck('inn'))
            ->distinct()
            ->pluck($other->innColumn())
            ->map(fn ($inn) => (string) $inn)
            ->flip();
        $firms['data'] = $firms['data']->map(fn ($firm) => $firm + ['in_other' => $inOther->has($firm['inn'])]);

        return response()->json([
            'status' => true,
            'data' => $firms,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-firms/summary",
     *     summary="Import va eksportdagi takrorlanmas firmalar soni (filtrsiz)",
     *     tags={"Uzbekistan Import"},
     *     @OA\Response(response=200, description="{import: {total, with_contacts, rows}, export: {...}, unique_total — ikkalasida jami takrorlanmas, both — ikkalasida ham bor}")
     * )
     */
    public function firmsSummary()
    {
        $import = new FirmDirectory(TradeDataset::Import->model());
        $export = new FirmDirectory(TradeDataset::Export->model());

        $importSummary = $import->summary();
        $exportSummary = $export->summary();

        // ham import, ham eksport qilgan firmalar (STIR bo'yicha)
        $both = $import->scope()
            ->whereIn($import->innColumn(), $export->scope()->select($export->innColumn()))
            ->distinct()
            ->count($import->innColumn());

        return response()->json([
            'status' => true,
            'data' => [
                'import' => $importSummary,
                'export' => $exportSummary,
                'unique_total' => $importSummary['total'] + $exportSummary['total'] - $both,
                'both' => $both,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/uzbekistan-import/import",
     *     summary="Excel fayl yuklash (fonda, queue orqali) — import yoki eksport fayli",
     *     description="Fayl turi (import/eksport) ustun nomlariga qarab avtomatik aniqlanadi va tegishli jadvalga yoziladi. Holatini /api/uzbekistan-import/tasks/{id} orqali kuzating.",
     *     tags={"Uzbekistan Import"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(property="file", type="string", format="binary", description="Download_UZBEKISTAN_Import_Trade_Data_*.xlsx yoki Download_UZBEKISTAN_Export_Trade_Data_*.xlsx")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=202, description="Vazifa yaratildi"),
     *     @OA\Response(response=422, description="Fayl turi tanilmadi")
     * )
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx|max:512000'
        ]);

        $file = $request->file('file');

        // import yoki eksport fayli ekanligini ustun nomlaridan aniqlaymiz — qaysi sahifadan yuklanganidan qat'i nazar
        $dataset = TradeDataset::detect($file->getRealPath());
        if ($dataset === null) {
            return response()->json([
                'status' => false,
                'message' => "Fayl turi tanilmadi: O'zbekiston import yoki eksport savdo ma'lumotlari fayli kutilgan",
            ], 422);
        }

        $path = $file->storeAs('uzbekistan/imports', uniqid('import_', true).'.xlsx', 'local');

        $task = UzbekistanImportTask::create([
            'type' => UzbekistanImportTask::TYPE_IMPORT,
            'dataset' => $dataset->value,
            'status' => UzbekistanImportTask::STATUS_PENDING,
            'user_id' => $request->user()?->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);

        ImportUzbekistanFile::dispatch($task);

        return response()->json([
            'status' => true,
            'message' => $dataset->label()." fayli qabul qilindi, fonda yuklanmoqda",
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
     *     @OA\Parameter(name="format", in="query", description="zanjeer — Zanjeer saytiga yuklash formatida (uz_import_*.xlsx, qatorlar kompaniya+mashina+sana bo'yicha birlashtiriladi)", required=false, @OA\Schema(type="string", enum={"zanjeer"})),
     *     @OA\Parameter(name="scope", in="query", description="format=zanjeer uchun: all — barcha ma'lumotlar (filtrlar bilan), today — bugun yuklangan fayllar", required=false, @OA\Schema(type="string", enum={"all","today"})),
     *     @OA\Response(response=202, description="Vazifa yaratildi"),
     *     @OA\Response(response=422, description="Bugun fayl yuklanmagan (scope=today)")
     * )
     */
    public function export(Request $request)
    {
        $request->validate([
            'format' => ['nullable', Rule::in(['zanjeer'])],
            'scope' => ['required_if:format,zanjeer', Rule::in(['all', 'today'])],
        ]);

        $dataset = $this->dataset($request);
        $params = $this->filterParams($request);
        $name = 'uzbekistan_'.$dataset->value.'_{id}_'.now()->format('Y_m_d_His').'.xlsx';

        if ($request->format === 'zanjeer') {
            $windows = null;
            if ($request->scope === 'today') {
                $uploads = $this->todayUploads($dataset);
                if ($uploads->isEmpty()) {
                    return response()->json([
                        'status' => false,
                        'message' => "Bugun {$dataset->label()} fayli yuklanmagan (yoki hali tugamagan)",
                    ], 422);
                }
                // bugungi fayllar yozgan qatorlar: har bir yuklash jarayoni davomida updated_at qo'yilgan
                $windows = $uploads->map(fn (UzbekistanImportTask $t) => [
                    $t->started_at->copy()->subSecond()->format('Y-m-d H:i:s'),
                    $t->finished_at->copy()->addSecond()->format('Y-m-d H:i:s'),
                ])->values()->all();
            }
            $params['zanjeer'] = ['scope' => $request->scope, 'windows' => $windows];
            $name = ZanjeerExport::fileName($dataset);
        }

        $task = UzbekistanImportTask::create([
            'type' => UzbekistanImportTask::TYPE_EXPORT,
            'dataset' => $dataset->value,
            'status' => UzbekistanImportTask::STATUS_PENDING,
            'user_id' => $request->user()?->id,
            'params' => $params,
        ]);
        $task->update(['original_name' => str_replace('{id}', $task->id, $name)]);

        ExportUzbekistanFile::dispatch($task);

        return response()->json([
            'status' => true,
            'message' => 'Export fonda tayyorlanmoqda',
            'data' => $task
        ], 202);
    }

    /**
     * @OA\Get(
     *     path="/api/uzbekistan-import/zanjeer/today",
     *     summary="Bugun yuklangan fayllar (Zanjeer formatidagi eksport uchun)",
     *     description="Eksport ma'lumotlari uchun — /api/uzbekistan-export/zanjeer/today",
     *     tags={"Uzbekistan Import"},
     *     @OA\Response(response=200, description="[{id, name, rows, finished_at}]")
     * )
     */
    public function zanjeerToday(Request $request)
    {
        return response()->json([
            'status' => true,
            'data' => $this->todayUploads($this->dataset($request))->map(fn (UzbekistanImportTask $t) => [
                'id' => $t->id,
                'name' => $t->original_name,
                'rows' => $t->processed_rows,
                'finished_at' => $t->finished_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    /** Bugun (Toshkent vaqti) muvaffaqiyatli yuklangan shu bo'lim fayllari */
    private function todayUploads(TradeDataset $dataset)
    {
        $todayStart = now(DashboardStats::TIMEZONE)->startOfDay()->utc();

        return UzbekistanImportTask::query()
            ->where('type', UzbekistanImportTask::TYPE_IMPORT)
            ->where('dataset', $dataset->value)
            ->where('status', UzbekistanImportTask::STATUS_COMPLETED)
            ->whereNotNull('started_at')
            ->where('finished_at', '>=', $todayStart)
            ->orderBy('id')
            ->get();
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
            'dataset' => ['nullable', Rule::enum(TradeDataset::class)],
        ]);

        $data = UzbekistanImportTask::query()
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->dataset, fn ($q, $dataset) => $q->where('dataset', $dataset))
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
        return $this->model($request)::excelFilter()->validate($request->only(['filters', 'sort']));
    }

    /** Route'dagi ma'lumot turi: /api/uzbekistan-import — import, /api/uzbekistan-export — eksport */
    private function dataset(Request $request): TradeDataset
    {
        return TradeDataset::from($request->route('dataset') ?? TradeDataset::Import->value);
    }

    /** @return class-string<UzbekistanImport|\App\Models\UzbekistanExport> */
    private function model(Request $request): string
    {
        return $this->dataset($request)->model();
    }
}
