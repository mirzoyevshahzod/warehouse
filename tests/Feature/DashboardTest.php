<?php

namespace Tests\Feature;

use App\Models\UploadLog;
use App\Models\User;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use App\Support\DataSources;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-10-19 10:00 Toshkent vaqti
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-19 05:00:00', 'UTC'));
        $this->user = User::factory()->create(['name' => 'Ali']);
        Sanctum::actingAs($this->user);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_stats_count_rows_per_source_by_tashkent_days(): void
    {
        $this->turkeys([
            '2026-10-19 01:00:00', // Toshkent: 19-oktabr 06:00 — bugun
            '2026-10-18 20:00:00', // Toshkent: 19-oktabr 01:00 — bugun (UTC'da kecha)
            '2026-10-18 18:00:00', // Toshkent: 18-oktabr 23:00 — kecha
            '2026-10-14 10:00:00', // 5 kun oldin — hafta ichida
            '2026-09-01 10:00:00', // oydan eski
        ]);
        $this->drivers(['2026-09-01 10:00:00']); // eskirgan manba

        $data = $this->getJson('/api/dashboard/stats?fresh=1')->assertOk()->json('data');

        $sources = collect($data['sources'])->keyBy('key');
        $this->assertCount(count(DataSources::all()), $sources);

        $turkey = $sources['turkey'];
        $this->assertSame([5, 2, 4, 4, 'fresh', '/turkey-scraping'],
            [$turkey['rows'], $turkey['today'], $turkey['week'], $turkey['month'], $turkey['status'], $turkey['url']]);
        $this->assertSame('stale', $sources['drivers']['status']);
        $this->assertSame('empty', $sources['mintrans']['status']);

        $this->assertSame(6, $data['totals']['rows']);
        $this->assertSame(2, $data['totals']['today']);
        $this->assertSame(1, $data['totals']['active']);
        $this->assertSame(1, $data['totals']['stale']);

        // kunlik grafik: 30 kun, oxirgisi — bugun
        $this->assertCount(30, $data['daily']['dates']);
        $this->assertSame('2026-10-19', end($data['daily']['dates']));
        $turkeyDaily = $data['daily']['series']['turkey'];
        $this->assertSame(2, $turkeyDaily[29]); // 19-oktabr
        $this->assertSame(1, $turkeyDaily[28]); // 18-oktabr
        $this->assertSame(1, $turkeyDaily[24]); // 14-oktabr
        $this->assertSame(4, array_sum($turkeyDaily));
    }

    public function test_stats_include_uzbekistan_firms(): void
    {
        UzbekistanImport::create(['importer_inn' => '300000001', 'importer_name' => 'FIRST LLC', 'item_no' => '1']);
        UzbekistanImport::create(['importer_inn' => '300000001', 'importer_name' => 'FIRST LLC', 'item_no' => '2']);
        UzbekistanImport::create(['importer_inn' => '300000002', 'importer_name' => 'SECOND LLC', 'item_no' => '3']);

        $uz = $this->getJson('/api/dashboard/stats?fresh=1')->json('data.uzbekistan');

        $this->assertSame(2, $uz['import']['total']);
        $this->assertSame(0, $uz['export']['total']);
        $this->assertSame(2, $uz['unique_total']);
        $this->assertSame(['FIRST LLC', 'SECOND LLC'], array_column($uz['import']['top'], 'name'));
    }

    public function test_upload_middleware_logs_success_and_failure(): void
    {
        Route::middleware(['api', 'auth:sanctum', 'log.upload:turkey'])->post('/api/test-upload', function () {
            DB::table('turkeys')->insert([['car_number' => 'A'], ['car_number' => 'B']]);

            return response()->json(['status' => true]);
        });
        Route::middleware(['api', 'auth:sanctum', 'log.upload:turkey'])->post('/api/test-upload-fail', function () {
            throw new \RuntimeException('Fayl buzilgan');
        });
        Route::middleware(['api', 'auth:sanctum', 'log.upload:turkey'])->post('/api/test-upload-invalid', function () {
            request()->validate(['file' => 'required|mimes:xlsx']);
        });

        $file = UploadedFile::fake()->create('turkey.xlsx', 10);
        $this->post('/api/test-upload', ['file' => $file], ['Accept' => 'application/json'])->assertOk();
        $this->post('/api/test-upload-fail', ['file' => $file], ['Accept' => 'application/json'])->assertStatus(500);
        // validatsiya xatosi jurnalga yozilmaydi
        $this->post('/api/test-upload-invalid', [], ['Accept' => 'application/json'])->assertStatus(422);

        $logs = UploadLog::orderBy('id')->get();
        $this->assertCount(2, $logs);

        $this->assertSame(['turkey', 'turkey.xlsx', 'success', 0, 2, 2, $this->user->id],
            [$logs[0]->source, $logs[0]->file_name, $logs[0]->status, $logs[0]->rows_before, $logs[0]->rows_after, $logs[0]->addedRows(), $logs[0]->user_id]);
        $this->assertSame('failed', $logs[1]->status);
        $this->assertNotNull($logs[1]->error);
    }

    public function test_all_import_routes_are_logged(): void
    {
        $logged = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('POST', $route->methods()))
            ->mapWithKeys(fn ($route) => [$route->uri() => collect($route->gatherMiddleware())->first(fn ($m) => is_string($m) && str_starts_with($m, 'log.upload:'))])
            ->filter();

        // har bir manba (O'zbekistondan tashqari — u navbat orqali, o'z jurnali bor) jurnalga yoziladi
        $expected = array_diff(array_keys(DataSources::all()), ['uzbekistan-import', 'uzbekistan-export']);
        $this->assertEqualsCanonicalizing($expected, $logged->map(fn ($m) => substr($m, strlen('log.upload:')))->values()->all());
    }

    public function test_recent_uploads_merge_logs_and_uzbekistan_tasks(): void
    {
        $log = UploadLog::create(['source' => 'turkey', 'user_id' => $this->user->id, 'file_name' => 't.xlsx', 'status' => 'success', 'rows_before' => 10, 'rows_after' => 15]);
        $log->forceFill(['created_at' => now()->subHour()])->save();
        UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'export', 'status' => 'processing', 'original_name' => 'uz.xlsx', 'user_id' => $this->user->id, 'processed_rows' => 100]);
        UzbekistanImportTask::create(['type' => 'export', 'dataset' => 'import', 'status' => 'completed', 'original_name' => 'download.xlsx']);

        $uploads = $this->getJson('/api/dashboard/uploads')->assertOk()->json('data');

        $this->assertCount(2, $uploads);
        $this->assertSame(['uzbekistan-export', 'processing', 'uz.xlsx', 'Ali'], [$uploads[0]['source'], $uploads[0]['status'], $uploads[0]['file_name'], $uploads[0]['user']]);
        $this->assertSame(['turkey', "Turkiya", 5], [$uploads[1]['source'], $uploads[1]['label'], $uploads[1]['added']]);
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->app['auth']->forgetGuards();
        $this->withHeaders(['Accept' => 'application/json'])->get('/api/dashboard/stats')->assertUnauthorized();
    }

    private function turkeys(array $dates): void
    {
        DB::table('turkeys')->insert(array_map(fn ($d) => ['car_number' => 'X', 'created_at' => $d, 'updated_at' => $d], $dates));
    }

    private function drivers(array $dates): void
    {
        DB::table('drivers')->insert(array_map(fn ($d) => ['full_name' => 'D', 'created_at' => $d, 'updated_at' => $d], $dates));
    }
}
