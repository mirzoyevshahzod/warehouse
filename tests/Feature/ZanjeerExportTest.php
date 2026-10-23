<?php

namespace Tests\Feature;

use App\Exports\ZanjeerExport;
use App\Jobs\ExportUzbekistanFile;
use App\Models\User;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;

/** Zanjeer saytiga yuklash formatidagi eksport (uz_import_*.xlsx / uz_export_*.xlsx) */
class ZanjeerExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // 2026-10-23 10:00 Toshkent vaqti
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-10-23 05:00:00', 'UTC'));
        $this->travelTo(CarbonImmutable::parse('2026-10-23 05:00:00', 'UTC'));
        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_import_rows_are_grouped_by_company_vehicle_and_date(): void
    {
        // 1-mashina: bitta firma, bitta sana, 2 xil tovar (2 ta ГТД) — bitta qator bo'ladi
        $this->importRow(['item_no' => '1', 'declaration_number' => '0005777', 'hs_code' => '1701991009', 'gross_weight_kg' => 20000, 'customs_amount_usd' => 1000.5]);
        $this->importRow(['item_no' => '2', 'declaration_number' => '0005778', 'hs_code' => '1701140000', 'gross_weight_kg' => 25000, 'customs_amount_usd' => 2000.25,
            'product_description' => 'BEET SUGAR', 'exporter_standard_name' => 'LLC SAXAR 2']);
        // boshqa mashina — alohida qator
        $this->importRow(['item_no' => '3', 'vehicle_number' => '01A777AA', 'trailer_number' => null, 'gross_weight_kg' => 1000]);
        // boshqa kun — alohida qator (eng yangi sana birinchi chiqadi)
        $this->importRow(['item_no' => '4', 'date' => '2026-08-30', 'registration_date' => '2026-08-30']);

        $rows = $this->export('import', ['scope' => 'all']);

        $this->assertSame(array_values(ZanjeerExport::IMPORT_COLUMNS), $rows[0]);
        $this->assertCount(25, $rows[0]);
        $this->assertCount(4, $rows); // sarlavha + 3 qator

        $byVehicle = collect(array_slice($rows, 1, 2))
            ->map(fn ($r) => array_combine(array_keys(ZanjeerExport::IMPORT_COLUMNS), $r))
            ->keyBy('vehicle_number');
        $first = $byVehicle['70R254EB/707628AA'];
        $this->assertSame('LLC BLESSED SUGAR', $first['importer_name']);
        $this->assertSame('312883153', $first['importer_inn']);
        $this->assertSame('МАТКАРИМОВ ФАХРИДДИН', $first['importer_contact_person']);
        $this->assertSame('+998995773577', $first['importer_contact_phone']);
        $this->assertSame('+998973109434', $first['importer_phone']);
        $this->assertSame('UZBEKISTAN, KASHKADARYA REGION, KARSHI', $first['unloading_place']);
        $this->assertSame('LLC SAXAR; LLC SAXAR 2', $first['exporter_name']);
        $this->assertSame('RUSSIA, KRASNODAR KRAI, PAVLOVSKAYA STATION', $first['loading_place']);
        $this->assertSame('FCA, 02', $first['delivery_terms']);
        $this->assertSame('70R254EB/707628AA', $first['vehicle_number']);
        $this->assertSame('АВТО', $first['transport_type']);
        $this->assertEquals(45, $first['tonnage']);
        $this->assertSame('2026-08-31', $first['date']);
        $this->assertSame('BEET SUGAR; CANE SUGAR', $first['product']);
        $this->assertEquals(3000.75, $first['value_usd']);
        $this->assertSame(2, $first['items_count']);
        $this->assertSame('1701140000; 1701991009', $first['hs_code']);
        $this->assertSame('0005777; 0005778', $first['declaration_number']);
        $this->assertSame('', $first['importer_company_std']); // Zanjeer bazasida — bizda yo'q

        $this->assertSame(1, $byVehicle['01A777AA']['items_count']);

        $this->assertSame('2026-08-30', $rows[3][array_search('Дата', $rows[0])]);
    }

    public function test_import_without_vehicle_groups_by_declaration(): void
    {
        $this->importRow(['item_no' => '1', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0001']);
        $this->importRow(['item_no' => '2', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0001']);
        $this->importRow(['item_no' => '1', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0002']);

        $rows = $this->export('import', ['scope' => 'all']);

        $this->assertCount(3, $rows); // ikkita yuk (ГТД) — ikkita qator
        $counts = [$rows[1][22], $rows[2][22]]; // "Товарных позиций"
        sort($counts);
        $this->assertSame([1, 2], $counts);
    }

    public function test_export_rows_are_grouped_by_exporter_buyer_and_date(): void
    {
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 500, 'customs_value_usd' => 100, 'hs_code' => '6802931000']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 1500, 'customs_value_usd' => 200, 'hs_code' => '0806101001']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'OTHER BUYER', 'net_weight_kg' => 10]);
        UzbekistanExport::where('exporter_inn', '306803919')->update([
            'responsible_person_name' => 'Karimov Aziz', 'responsible_person_phone' => '+998901234567',
        ]);

        $rows = $this->export('export', ['scope' => 'all']);

        $this->assertSame(array_values(ZanjeerExport::EXPORT_COLUMNS), $rows[0]);
        $this->assertCount(3, $rows);

        $buyer = collect(array_slice($rows, 1))
            ->map(fn ($r) => array_combine(array_keys(ZanjeerExport::EXPORT_COLUMNS), $r))
            ->firstWhere('importer_name', 'TOO BUYER');
        $this->assertSame('LLC INTERNET TECHNOLOGY', $buyer['exporter_name']);
        $this->assertSame('Karimov Aziz', $buyer['exporter_contact_person']);
        $this->assertSame('+998901234567', $buyer['exporter_contact_phone']);
        $this->assertSame('UZBEKISTAN', $buyer['loading_place']);
        $this->assertSame('RUSSIA, МОСКВА', $buyer['unloading_place']);
        $this->assertSame('RUSSIA', $buyer['importer_country']);
        $this->assertSame('CPT, 07', $buyer['delivery_terms']);
        $this->assertSame('ROAD', $buyer['transport_type']);
        $this->assertEquals(2, $buyer['tonnage']);
        $this->assertEquals(300, $buyer['value_usd']);
        $this->assertSame(2, $buyer['items_count']);
        $this->assertSame('0806101001; 6802931000', $buyer['hs_code']);
    }

    public function test_all_scope_respects_table_filters(): void
    {
        $this->importRow(['item_no' => '1']);
        $this->importRow(['item_no' => '2', 'importer_inn' => '300000001', 'importer_name' => 'LLC OTHER']);

        $rows = $this->export('import', ['scope' => 'all', 'filters' => ['importer_inn' => ['values' => ['300000001']]]]);

        $this->assertCount(2, $rows);
        $this->assertSame('LLC OTHER', $rows[1][0]);
    }

    public function test_today_scope_takes_only_rows_from_todays_uploads(): void
    {
        $this->importRow(['item_no' => '1', 'importer_name' => 'OLD LLC', 'importer_inn' => '100000001']);
        DB::table('uzbekistan_imports')->update(['updated_at' => '2026-10-20 08:00:00']);

        // bugungi yuklash: 09:30–09:31 (UTC 04:30–04:31)
        UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'import', 'status' => 'completed', 'original_name' => 'today.xlsx',
            'processed_rows' => 1, 'started_at' => '2026-10-23 04:30:00', 'finished_at' => '2026-10-23 04:31:00']);
        // kechagi yuklash va boshqa bo'lim — hisobga olinmaydi
        UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'import', 'status' => 'completed', 'original_name' => 'old.xlsx',
            'started_at' => '2026-10-20 07:59:00', 'finished_at' => '2026-10-20 08:01:00']);
        UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'export', 'status' => 'completed', 'original_name' => 'export.xlsx',
            'started_at' => '2026-10-23 04:00:00', 'finished_at' => '2026-10-23 04:01:00']);

        $this->importRow(['item_no' => '2', 'importer_name' => 'TODAY LLC', 'importer_inn' => '100000002']);
        DB::table('uzbekistan_imports')->where('importer_inn', '100000002')->update(['updated_at' => '2026-10-23 04:30:30']);

        $this->getJson('/api/uzbekistan-import/zanjeer/today')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'today.xlsx');

        $rows = $this->export('import', ['scope' => 'today']);
        $this->assertCount(2, $rows);
        $this->assertSame('TODAY LLC', $rows[1][0]);
    }

    public function test_today_scope_without_uploads_is_rejected(): void
    {
        Queue::fake();

        $this->postJson('/api/uzbekistan-export/export', ['format' => 'zanjeer', 'scope' => 'today'])
            ->assertStatus(422)
            ->assertJsonPath('status', false);
        $this->postJson('/api/uzbekistan-export/export', ['format' => 'zanjeer'])->assertStatus(422);
        $this->postJson('/api/uzbekistan-export/export', ['format' => 'csv'])->assertStatus(422);

        Queue::assertNothingPushed();
    }

    public function test_file_name_and_sheet_follow_zanjeer_convention(): void
    {
        $this->importRow(['item_no' => '1']);

        Queue::fake();
        $task = UzbekistanImportTask::findOrFail(
            $this->postJson('/api/uzbekistan-import/export', ['format' => 'zanjeer', 'scope' => 'all'])->assertStatus(202)->json('data.id')
        );
        $this->assertSame('uz_import_2026-10-23_100000.xlsx', $task->original_name); // Toshkent vaqti

        (new ExportUzbekistanFile($task))->handle();

        $reader = new Reader();
        $reader->open(Storage::disk('local')->path($task->refresh()->file_path));
        $names = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            $names[] = $sheet->getName();
        }
        $reader->close();

        $this->assertSame(['Импорт'], $names);
        $this->assertSame('completed', $task->status);
        $this->assertSame(1, $task->total_rows);
    }

    // ================= Yordamchilar =================

    /** @return array<int, array> Excel qatorlari (sarlavha bilan) */
    private function export(string $dataset, array $body): array
    {
        Queue::fake();
        $id = $this->postJson("/api/uzbekistan-$dataset/export", ['format' => 'zanjeer'] + $body)->assertStatus(202)->json('data.id');
        $task = UzbekistanImportTask::findOrFail($id);
        (new ExportUzbekistanFile($task))->handle();

        $reader = new Reader();
        $reader->open(Storage::disk('local')->path($task->refresh()->file_path));
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        return $rows;
    }

    private function importRow(array $o): void
    {
        UzbekistanImport::create($o + [
            'customs_code' => '03015',
            'date' => '2026-08-31',
            'registration_date' => '2026-08-31',
            'declaration_number' => '0005777',
            'importer_name' => 'LLC BLESSED SUGAR',
            'importer_inn' => '312883153',
            'importer_phone' => '+998973109434',
            'importer_address' => 'REPUBLIC OF UZBEKISTAN, KASHKADARYA REGION, KARSHI, UL. MUSTAQILLIK 5;',
            'responsible_person_name' => 'МАТКАРИМОВ ФАХРИДДИН',
            'responsible_person_phone' => '+998995773577',
            'exporter_standard_name' => 'LLC SAXAR',
            'exporter_country' => 'RUSSIA',
            'exporter_address' => '352040, RUSSIA, KRASNODAR KRAI, PAVLOVSKAYA STATION, UL. ZAVODSKAYA 1',
            'trade_mode' => 'FCA',
            'trade_mode_code' => '02',
            'vehicle_number' => '70R254EB',
            'trailer_number' => '707628AA',
            'transport_type' => 'АВТО',
            'hs_code' => '1701991009',
            'product_description' => 'CANE SUGAR',
            'origin_country' => 'RUSSIA',
            'gross_weight_kg' => 1000,
            'customs_amount_usd' => 10,
        ]);
    }

    private function exportRow(array $o): void
    {
        static $n = 0;
        (new UzbekistanExport())->forceFill($o + [
            'row_key' => 'k'.(++$n),
            'date' => '2026-08-31',
            'exporter_name' => 'LLC INTERNET TECHNOLOGY',
            'importer_name' => 'TOO BUYER',
            'hs_code' => '6802931000',
            'product_description' => 'GRANITE',
            'destination_country' => 'RUSSIA',
            'destination' => 'МОСКВА',
            'transaction_method' => 'CPT',
            'delivery_condition_code' => '07',
            'transportation_method' => 'ROAD',
        ])->save();
    }
}
