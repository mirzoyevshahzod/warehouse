<?php

namespace App\Support;

use App\Models\UploadLog;
use App\Models\UzbekistanImportTask;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard statistikasi: har bir manbada qancha ma'lumot bor, qachon qancha qo'shildi,
 * oxirgi yuklashlar va O'zbekiston firmalari.
 */
class DashboardStats
{
    /** Kunlar Toshkent vaqti bo'yicha hisoblanadi (bazada UTC) */
    public const TIMEZONE = 'Asia/Tashkent';

    public const DAYS = 30;

    private CarbonImmutable $now;

    public function __construct(?CarbonImmutable $now = null)
    {
        $this->now = ($now ?? CarbonImmutable::now())->setTimezone(self::TIMEZONE);
    }

    public function overview(): array
    {
        $sizes = $this->tableSizes();
        $days = $this->days();
        $since = [
            'today' => $this->now->startOfDay()->utc(),
            'week' => $this->now->startOfDay()->subDays(6)->utc(),
            'month' => $this->now->startOfDay()->subDays(self::DAYS - 1)->utc(),
        ];

        $sources = [];
        $daily = [];
        foreach (DataSources::all() as $key => $source) {
            $table = DataSources::table($key);
            $row = DB::table($table)
                ->selectRaw('count(*) as total')
                ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as today', [$since['today']])
                ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as week', [$since['week']])
                ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as month', [$since['month']])
                ->selectRaw('max(created_at) as last_at')
                ->first();

            $lastAt = $row->last_at ? CarbonImmutable::parse($row->last_at, 'UTC') : null;
            $total = (int) $row->total;

            $sources[] = [
                'key' => $key,
                'label' => $source['label'],
                'group' => $source['group'],
                'icon' => $source['icon'],
                'url' => route($source['route'], [], false),
                'rows' => $total,
                'today' => (int) $row->today,
                'week' => (int) $row->week,
                'month' => (int) $row->month,
                'last_at' => $lastAt?->toIso8601String(),
                'size_mb' => $sizes[$table] ?? null,
                'status' => match (true) {
                    $total === 0 => 'empty',
                    $lastAt === null || $lastAt->lt($this->now->subDays(DataSources::STALE_DAYS)) => 'stale',
                    default => 'fresh',
                },
            ];

            $daily[$key] = $this->dailyCounts(DB::table($table), $since['month'], $days);
        }

        $collection = collect($sources);

        return [
            'generated_at' => $this->now->toIso8601String(),
            'stale_days' => DataSources::STALE_DAYS,
            'totals' => [
                'rows' => $collection->sum('rows'),
                'today' => $collection->sum('today'),
                'week' => $collection->sum('week'),
                'month' => $collection->sum('month'),
                'sources' => $collection->count(),
                'active' => $collection->where('status', 'fresh')->count(),
                'stale' => $collection->where('status', 'stale')->count(),
                'empty' => $collection->where('status', 'empty')->count(),
                'size_mb' => $sizes ? round($collection->sum('size_mb'), 1) : null,
            ],
            'sources' => $sources,
            'daily' => ['dates' => $days, 'series' => $daily],
            'uploads' => $this->uploadsDaily($since['month'], $days),
            'uzbekistan' => $this->uzbekistan(),
        ];
    }

    /**
     * Oxirgi yuklashlar: barcha manbalar jurnali + O'zbekiston fayllari (navbat orqali)
     */
    public function recentUploads(int $limit = 15): Collection
    {
        $logs = UploadLog::with('user:id,name')->latest('id')->limit($limit)->get()->map(fn (UploadLog $log) => [
            'source' => $log->source,
            'label' => DataSources::get($log->source)['label'] ?? $log->source,
            'file_name' => $log->file_name,
            'status' => $log->status,
            'added' => $log->addedRows(),
            'rows' => $log->rows_after,
            'error' => $log->error,
            'duration_ms' => $log->duration_ms,
            'user' => $log->user?->name,
            'created_at' => $log->created_at?->toIso8601String(),
        ]);

        $tasks = UzbekistanImportTask::with('user:id,name')
            ->where('type', UzbekistanImportTask::TYPE_IMPORT)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (UzbekistanImportTask $task) => [
                'source' => 'uzbekistan-'.$task->tradeDataset()->value,
                'label' => DataSources::get('uzbekistan-'.$task->tradeDataset()->value)['label'],
                'file_name' => $task->original_name,
                'status' => match ($task->status) {
                    UzbekistanImportTask::STATUS_COMPLETED => UploadLog::STATUS_SUCCESS,
                    UzbekistanImportTask::STATUS_FAILED => UploadLog::STATUS_FAILED,
                    default => $task->status, // pending / processing
                },
                'added' => null,
                'rows' => $task->processed_rows,
                'error' => $task->error,
                'duration_ms' => $task->started_at && $task->finished_at
                    ? (int) $task->started_at->diffInMilliseconds($task->finished_at)
                    : null,
                'user' => $task->user?->name,
                'created_at' => $task->created_at?->toIso8601String(),
            ]);

        return $logs->concat($tasks)->sortByDesc('created_at')->take($limit)->values();
    }

    /** Kunlik yuklashlar soni (muvaffaqiyatli / xato) — barcha manbalar bo'yicha */
    private function uploadsDaily(CarbonImmutable $since, array $days): array
    {
        $success = $this->dailyCounts(DB::table('upload_logs')->where('status', UploadLog::STATUS_SUCCESS), $since, $days);
        $failed = $this->dailyCounts(DB::table('upload_logs')->where('status', UploadLog::STATUS_FAILED), $since, $days);

        $tasks = DB::table('uzbekistan_import_tasks')->where('type', UzbekistanImportTask::TYPE_IMPORT);
        $tasksSuccess = $this->dailyCounts((clone $tasks)->where('status', UzbekistanImportTask::STATUS_COMPLETED), $since, $days);
        $tasksFailed = $this->dailyCounts((clone $tasks)->where('status', UzbekistanImportTask::STATUS_FAILED), $since, $days);

        $sum = fn (array $a, array $b) => array_map(fn ($x, $y) => $x + $y, $a, $b);

        return [
            'success' => $sum($success, $tasksSuccess),
            'failed' => $sum($failed, $tasksFailed),
        ];
    }

    private function uzbekistan(): array
    {
        $import = new FirmDirectory(TradeDataset::Import->model());
        $export = new FirmDirectory(TradeDataset::Export->model());

        $importSummary = $import->summary();
        $exportSummary = $export->summary();
        $both = $import->scope()
            ->whereIn($import->innColumn(), $export->scope()->select($export->innColumn()))
            ->distinct()
            ->count($import->innColumn());

        $top = fn (FirmDirectory $dir) => $dir->paginate([], null, 1, 5)['data']
            ->map(fn ($firm) => ['inn' => $firm['inn'], 'name' => $firm['name'], 'rows' => $firm['rows'], 'has_contact' => count($firm['contacts']) > 0])
            ->values();

        return [
            'import' => $importSummary + ['top' => $top($import)],
            'export' => $exportSummary + ['top' => $top($export)],
            'unique_total' => $importSummary['total'] + $exportSummary['total'] - $both,
            'both' => $both,
        ];
    }

    /** @return string[] oxirgi DAYS kun, Y-m-d (Toshkent vaqti) */
    private function days(): array
    {
        $start = $this->now->startOfDay()->subDays(self::DAYS - 1);

        return array_map(fn ($i) => $start->addDays($i)->toDateString(), range(0, self::DAYS - 1));
    }

    /** @return int[] har bir kun uchun qatorlar soni ($days tartibida) */
    private function dailyCounts(Builder $query, CarbonImmutable $since, array $days): array
    {
        $day = $this->localDateSql($query);

        $counts = $query
            ->where('created_at', '>=', $since)
            ->selectRaw("$day as day, count(*) as c")
            ->groupByRaw($day)
            ->pluck('c', 'day');

        return array_map(fn ($d) => (int) ($counts[$d] ?? 0), $days);
    }

    /** created_at (UTC) ning Toshkent vaqtidagi sanasi — SQL ifodasi */
    private function localDateSql(Builder $query): string
    {
        $hours = (int) ($this->now->getOffset() / 3600);

        return $query->getConnection()->getDriverName() === 'sqlite'
            ? "date(created_at, '+$hours hours')"
            : "date(date_add(created_at, interval $hours hour))";
    }

    /** @return array<string, float> jadval => hajmi (MB); faqat MySQL */
    private function tableSizes(): array
    {
        if (DB::getDriverName() !== 'mysql') {
            return [];
        }

        return DB::table('information_schema.tables')
            ->where('table_schema', DB::getDatabaseName())
            ->selectRaw('table_name as name, round((data_length + index_length) / 1024 / 1024, 1) as mb')
            ->pluck('mb', 'name')
            ->map(fn ($mb) => (float) $mb)
            ->all();
    }
}
