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

    public function test_import_rows_are_grouped_by_importer_exporter_date_and_vehicle(): void
    {
        // 1-yuk: bir importyor, bir eksportyor, bir kun, bir mashina, bir xil tovar pozitsiyasi (1701) — bitta qator
        $this->importRow(['item_no' => '1', 'declaration_number' => '0005777', 'hs_code' => '1701991009', 'gross_weight_kg' => 20000, 'customs_amount_usd' => 1000.5,
            'product_description' => 'CANE OR BEET SUGAR: OTHER: RED SUGAR - WEIGHT: 20 T']);
        $this->importRow(['item_no' => '2', 'declaration_number' => '0005778', 'hs_code' => '1701140000', 'gross_weight_kg' => 25000, 'customs_amount_usd' => 2000.25,
            'product_description' => 'CANE OR BEET SUGAR:  OTHER:WHITE SUGAR - WEIGHT: 25 T']);
        // o'sha mashina, o'sha kun, lekin boshqa eksportyor — alohida qator
        $this->importRow(['item_no' => '3', 'exporter_standard_name' => 'LLC SAXAR 2', 'gross_weight_kg' => 5000]);
        // boshqa mashina — alohida qator
        $this->importRow(['item_no' => '4', 'vehicle_number' => '01A777AA', 'trailer_number' => null, 'gross_weight_kg' => 1000]);
        // boshqa kun — alohida qator (eng yangi sana birinchi chiqadi)
        $this->importRow(['item_no' => '5', 'date' => '2026-08-30', 'registration_date' => '2026-08-30']);

        $rows = $this->export('import', ['scope' => 'all', 'merge' => true]);

        $this->assertSame(array_values(ZanjeerExport::IMPORT_COLUMNS), $rows[0]);
        $this->assertCount(25, $rows[0]);
        $this->assertCount(6, $rows); // sarlavha + 4 yuk, 45 t li yuk 24 t + 21 t ga bo'lingan

        $lines = collect(array_slice($rows, 1))->map(fn ($r) => array_combine(array_keys(ZanjeerExport::IMPORT_COLUMNS), $r));
        $parts = $lines->filter(fn ($l) => $l['items_count'] === 2)->values();
        $this->assertCount(2, $parts);
        $this->assertEquals([24, 21], $parts->pluck('tonnage')->all());           // 20 t + 25 t = 45 t => 24 t + 21 t
        $this->assertEquals(3000.75, round($parts->sum('value_usd'), 2));         // narxlar qo'shilgan, bo'linganda jami o'zgarmaydi
        $first = $parts->first();
        $this->assertSame('LLC BLESSED SUGAR', $first['importer_name']);
        $this->assertSame('312883153', $first['importer_inn']);
        $this->assertSame('МАТКАРИМОВ ФАХРИДДИН', $first['importer_contact_person']);
        $this->assertSame('+998995773577', $first['importer_contact_phone']);
        $this->assertSame('+998973109434', $first['importer_phone']);
        $this->assertSame('UZBEKISTAN, KASHKADARYA REGION, KARSHI', $first['unloading_place']);
        $this->assertSame('LLC SAXAR', $first['exporter_name']);
        $this->assertSame('RUSSIA, KRASNODAR KRAI, PAVLOVSKAYA STATION', $first['loading_place']);
        $this->assertSame('FCA, 02', $first['delivery_terms']);
        $this->assertSame('70R254EB/707628AA', $first['vehicle_number']);
        $this->assertSame('АВТО', $first['transport_type']);
        $this->assertSame('2026-08-31', $first['date']);
        $this->assertSame('CANE OR BEET SUGAR', $first['product']); // "qizil" va "oq" shakar — umumiy nom
        $this->assertSame('1701140000; 1701991009', $first['hs_code']);
        $this->assertSame('0005777; 0005778', $first['declaration_number']);
        $this->assertSame('', $first['importer_company_std']);     // Zanjeer bazasida — bizda yo'q

        $other = $lines->firstWhere('exporter_name', 'LLC SAXAR 2');
        $this->assertSame(1, $other['items_count']);
        $this->assertSame('70R254EB/707628AA', $other['vehicle_number']);
        $this->assertSame(1, $lines->firstWhere('vehicle_number', '01A777AA')['items_count']);
        $this->assertSame('2026-08-30', $lines->last()['date']);
    }

    public function test_different_goods_in_one_load_keep_one_general_name_each(): void
    {
        // bitta yukda olma turlari (0808) va uzum (0806): olma — turlarining umumiy nomi, uzum — bosh nomi
        foreach (['RED APPLES', 'GREEN APPLES', 'YELLOW APPLES'] as $i => $kind) {
            $this->importRow(['item_no' => (string) ($i + 1), 'hs_code' => '080810800'.$i, 'product_description' => "APPLES, PEARS AND QUINCES, FRESH: APPLES: OTHER: {$kind}"]);
        }
        $this->importRow(['item_no' => '9', 'hs_code' => '0806101009', 'product_description' => 'GRAPES, FRESH OR DRIED: FRESH: TABLE GRAPES']);

        $rows = $this->export('import', ['scope' => 'all', 'merge' => true]);

        $this->assertCount(2, $rows);
        $this->assertSame(4, $rows[1][22]); // "Товарных позиций"
        $this->assertSame('APPLES, PEARS AND QUINCES, FRESH: APPLES; GRAPES, FRESH OR DRIED', $rows[1][19]);
    }

    public function test_by_default_every_row_is_exported_separately(): void
    {
        // bitta mashina, bitta kun — lekin birlashtirilmaydi
        $this->importRow(['item_no' => '1', 'hs_code' => '1701991009', 'gross_weight_kg' => 20000, 'customs_amount_usd' => 1000.5]);
        $this->importRow(['item_no' => '2', 'hs_code' => '1701140000', 'gross_weight_kg' => 23000, 'customs_amount_usd' => 2000.25]);
        $this->importRow(['item_no' => '3', 'vehicle_number' => '01A777AA', 'trailer_number' => null]);

        $rows = $this->export('import', ['scope' => 'all']);

        $this->assertCount(4, $rows); // sarlavha + 3 qator — manbadagi qatorlar soni bilan bir xil
        $lines = collect(array_slice($rows, 1))->map(fn ($r) => array_combine(array_keys(ZanjeerExport::IMPORT_COLUMNS), $r));
        $this->assertSame([1, 1, 1], $lines->pluck('items_count')->all());
        $this->assertEqualsCanonicalizing(['1701991009', '1701140000', '1701991009'], $lines->pluck('hs_code')->all());
        $this->assertEqualsCanonicalizing([20.0, 23.0, 1.0], $lines->pluck('tonnage')->map(fn ($t) => (float) $t)->all());

        // eksport ham
        $this->exportRow(['net_weight_kg' => 500]);
        $this->exportRow(['net_weight_kg' => 1500]);
        $this->assertCount(3, $this->export('export', ['scope' => 'all']));
    }

    public function test_import_without_vehicle_groups_by_origin_country(): void
    {
        // mashina raqami yo'q — o'rniga kelib chiqish davlati
        $this->importRow(['item_no' => '1', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0001', 'origin_country' => 'CHINA']);
        $this->importRow(['item_no' => '2', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0002', 'origin_country' => 'CHINA']);
        $this->importRow(['item_no' => '3', 'vehicle_number' => null, 'trailer_number' => null, 'declaration_number' => '0003', 'origin_country' => 'TURKEY']);

        $rows = $this->export('import', ['scope' => 'all', 'merge' => true]);

        $this->assertCount(3, $rows); // CHINA (2 ta) va TURKEY (1 ta)
        $byCountry = collect(array_slice($rows, 1))->mapWithKeys(fn ($r) => [$r[20] => $r[22]]); // "Страна происхождения" => "Товарных позиций"
        $this->assertSame(['CHINA' => 2, 'TURKEY' => 1], $byCountry->sortKeys()->all());
    }

    public function test_export_rows_are_grouped_by_exporter_buyer_and_date(): void
    {
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 500, 'customs_value_usd' => 100, 'hs_code' => '6802931000']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 1500, 'customs_value_usd' => 200, 'hs_code' => '0806101001']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'OTHER BUYER', 'net_weight_kg' => 10]);
        UzbekistanExport::where('exporter_inn', '306803919')->update([
            'responsible_person_name' => 'Karimov Aziz', 'responsible_person_phone' => '+998901234567',
        ]);

        $rows = $this->export('export', ['scope' => 'all', 'merge' => true]);

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

    public function test_transport_type_can_be_chosen(): void
    {
        $this->importRow(['item_no' => '1', 'transport_type' => 'АВТО']);
        $this->importRow(['item_no' => '2', 'transport_type' => 'ЖД']);
        $this->importRow(['item_no' => '3', 'transport_type' => 'ЖД:']); // faylda shunday yozuv ham bor
        $this->importRow(['item_no' => '4', 'transport_type' => 'АВИА']);

        $transports = fn (array $body) => collect(array_slice($this->export('import', ['scope' => 'all'] + $body), 1))
            ->pluck(16)->sort()->values()->all(); // "Вид перевозки"

        $this->assertSame(['АВТО'], $transports(['transport' => 'auto']));
        $this->assertSame(['ЖД', 'ЖД:'], $transports(['transport' => 'rail']));
        $this->assertSame(['АВТО', 'ЖД', 'ЖД:'], $transports(['transport' => 'both']));
        $this->assertSame(['АВТО', 'ЖД', 'ЖД:'], $transports([])); // standart — ikkalasi

        // eksport: ROAD / RAIL / AIR
        $this->exportRow(['transportation_method' => 'ROAD']);
        $this->exportRow(['transportation_method' => 'RAIL']);
        $this->exportRow(['transportation_method' => 'AIR']);
        $exportTransports = fn (string $transport) => collect(array_slice($this->export('export', ['scope' => 'all', 'transport' => $transport]), 1))
            ->pluck(15)->sort()->values()->all();
        $this->assertSame(['ROAD'], $exportTransports('auto'));
        $this->assertSame(['RAIL'], $exportTransports('rail'));
        $this->assertSame(['RAIL', 'ROAD'], $exportTransports('both'));

        Queue::fake();
        $this->postJson('/api/uzbekistan-import/export', ['format' => 'zanjeer', 'scope' => 'all', 'transport' => 'ship'])->assertStatus(422);
    }

    public function test_heavy_rows_are_split_by_truck_and_wagon_capacity(): void
    {
        // bitta ГТД bir nechta transportni qamragan, faylda bitta raqam
        $this->importRow(['item_no' => '1', 'transport_type' => 'АВТО', 'vehicle_number' => 'A1', 'gross_weight_kg' => 125340, 'customs_amount_usd' => 1000]);
        $this->importRow(['item_no' => '2', 'transport_type' => 'ЖД', 'vehicle_number' => 'W1', 'trailer_number' => null, 'gross_weight_kg' => 150000, 'customs_amount_usd' => 300]);
        $this->importRow(['item_no' => '3', 'transport_type' => 'АВИА', 'vehicle_number' => 'P1', 'trailer_number' => null, 'gross_weight_kg' => 100000]);
        $this->importRow(['item_no' => '4', 'transport_type' => 'АВТО', 'vehicle_number' => 'A2', 'trailer_number' => null, 'gross_weight_kg' => 24000]); // aynan 24 t — bo'linmaydi

        $lines = collect(array_slice($this->export('import', ['scope' => 'all', 'transport' => 'both']), 1))
            ->map(fn ($r) => array_combine(array_keys(ZanjeerExport::IMPORT_COLUMNS), $r));

        $auto = $lines->filter(fn ($l) => str_starts_with($l['vehicle_number'], 'A1'))->values();
        $this->assertEquals([24, 24, 24, 24, 24, 5.34], $auto->pluck('tonnage')->all());   // 125.34 t => 5 × 24 t + 5.34 t
        $this->assertEquals(1000, round($auto->sum('value_usd'), 2));
        $this->assertEquals(191.48, $auto[0]['value_usd']);                               // 1000 × 24 / 125.34 = 191.479…
        $this->assertSame([1, 1, 1, 1, 1, 1], $auto->pluck('items_count')->all());

        $rail = $lines->where('vehicle_number', 'W1')->values();
        $this->assertEquals([70, 70, 10], $rail->pluck('tonnage')->all());               // 150 t => 2 × 70 t + 10 t
        $this->assertEquals(300, round($rail->sum('value_usd'), 2));

        $this->assertEquals([24], $lines->where('vehicle_number', 'A2')->pluck('tonnage')->all());

        // avia bo'linmaydi (transport=both avia'ni olmaydi — shuning uchun alohida tekshiramiz)
        $this->assertCount(0, $lines->where('vehicle_number', 'P1'));
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
