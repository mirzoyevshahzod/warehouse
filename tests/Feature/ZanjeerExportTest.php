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

/** Zanjeer saytiga yuklash shablonidagi eksport ("Шаблон импорта - Импортеры/Экспортеры.xlsx", 63 ustun) */
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

        $this->assertSame(array_values(ZanjeerExport::COLUMNS), $rows[0]);
        $this->assertCount(63, $rows[0]);
        $this->assertCount(6, $rows); // sarlavha + 4 yuk, 45 t li yuk 24 t + 21 t ga bo'lingan

        $lines = $this->lines($rows);
        $parts = $lines->filter(fn ($l) => $l['item_no'] === '1; 2')->values();
        $this->assertCount(2, $parts);
        $this->assertEquals([24000, 21000], $parts->pluck('gross_weight_kg')->all());   // 20 t + 25 t = 45 t => 24 t + 21 t
        $this->assertEquals(3000.75, round($parts->sum('customs_amount_usd'), 2));    // narxlar qo'shilgan, bo'linganda jami o'zgarmaydi
        $first = $parts->first();
        $this->assertSame('LLC BLESSED SUGAR', $first['importer_name']);
        $this->assertSame('312883153', $first['importer_inn']);
        $this->assertSame('МАТКАРИМОВ ФАХРИДДИН', $first['responsible_person_name']);
        $this->assertSame('+998995773577', $first['responsible_person_phone']);
        $this->assertSame('+998973109434', $first['importer_phone']);
        $this->assertSame('LLC SAXAR', $first['exporter_standard_name']);
        $this->assertSame('RUSSIA', $first['exporter_country']);
        $this->assertSame('FCA', $first['trade_mode']);
        $this->assertSame('02', $first['trade_mode_code']);
        $this->assertSame('70R254EB', $first['vehicle_number']);
        $this->assertSame('707628AA', $first['trailer_number']);
        $this->assertSame('АВТО', $first['transport_type']);
        $this->assertSame('2026-08-31', $first['date']);
        $this->assertSame('CANE OR BEET SUGAR', $first['product_description']); // "qizil" va "oq" shakar — umumiy nom
        $this->assertSame('1701140000; 1701991009', $first['hs_code']);

        $other = $lines->firstWhere('exporter_standard_name', 'LLC SAXAR 2');
        $this->assertSame('3', $other['item_no']);
        $this->assertSame('70R254EB', $other['vehicle_number']);
        $this->assertSame('4', $lines->firstWhere('vehicle_number', '01A777AA')['item_no']);
        $this->assertSame('2026-08-30', $lines->last()['date']);
    }

    public function test_import_row_fills_every_template_column(): void
    {
        $this->importRow([
            'item_no' => '7', 'declaration_date' => '2026-08-30', 'regime_type' => 'ИМ', 'regime_code' => '40',
            'importer_region' => 'KASHKADARYA REGION', 'importer_district' => 'KARSHI', 'importer_village' => 'MUSTAQILLIK',
            'exporter_region' => 'KRASNODAR KRAI', 'exporter_city' => 'PAVLOVSKAYA', 'exporter_street' => 'UL. ZAVODSKAYA 1', 'exporter_phone' => '+7 900',
            'brand' => 'SAXAR', 'quantity' => 48, 'quantity_unit' => 'PCS', 'net_weight_kg' => 990.5,
            'invoice_amount_fc' => 900, 'customs_amount_fc' => 950, 'statistical_amount_usd' => 11,
            'total_invoice_amount_fc' => 5000, 'total_customs_amount_fc' => 5100, 'total_tariffs' => 123.45,
            'contract_currency' => 'RUB', 'usd_exchange_rate' => 11801.23, 'fc_exchange_rate' => 136.73,
            'contract_char_code' => '01', 'delivery_place' => 'KARSHI', 'shipment_country' => 'RUSSIA', 'trading_country' => 'RUSSIA',
            'transport_country' => 'RUSSIA', 'transit_country' => 'KAZAKHSTAN', 'transit_date' => '2026-08-29',
            'declaring_unit' => 'LLC "DM BROKER SERVIS"', 'declaring_unit_code' => '311107721', 'applicant_address' => 'FERGANA CITY',
            'declarant_name' => 'ЮЛДАШЕВ Д.А.', 'applicant_email' => 'SHUKUZB@MAIL.RU', 'applicant_phone' => '+998999993292',
            'overseas_transport_type' => 'АВТО', 'border_vehicle_number' => '70R254EB', 'border_customs' => 'ЯЛЛАМА',
            'customs_office' => 'ДИРЕКТОР ...', 'responsible_person_position' => 'ДИРЕКТОР',
        ]);

        $line = $this->lines($this->export('import', ['scope' => 'all']))->first();

        $this->assertSame([
            'date' => '2026-08-31', 'declaration_date' => '2026-08-30', 'regime_type' => 'ИМ', 'regime_code' => '40', 'item_no' => '7',
            'importer_name' => 'LLC BLESSED SUGAR', 'importer_address' => 'REPUBLIC OF UZBEKISTAN, KASHKADARYA REGION, KARSHI, UL. MUSTAQILLIK 5;',
            'importer_region' => 'KASHKADARYA REGION', 'importer_district' => 'KARSHI', 'importer_village' => 'MUSTAQILLIK',
            'importer_inn' => '312883153', 'importer_phone' => '+998973109434',
            'exporter_standard_name' => 'LLC SAXAR', 'exporter_address' => '352040, RUSSIA, KRASNODAR KRAI, PAVLOVSKAYA STATION, UL. ZAVODSKAYA 1',
            'exporter_country' => 'RUSSIA', 'exporter_region' => 'KRASNODAR KRAI', 'exporter_city' => 'PAVLOVSKAYA', 'exporter_street' => 'UL. ZAVODSKAYA 1',
            'exporter_phone' => '+7 900', 'hs_code' => '1701991009', 'product_description' => 'CANE SUGAR', 'brand' => 'SAXAR',
            'quantity' => 48, 'quantity_unit' => 'PCS', 'net_weight_kg' => 990.5, 'gross_weight_kg' => 1000,
            'invoice_amount_fc' => 900, 'customs_amount_usd' => 10, 'customs_amount_fc' => 950, 'statistical_amount_usd' => 11,
            'total_invoice_amount_fc' => 5000, 'total_customs_amount_fc' => 5100, 'total_tariffs' => 123.45,
            'contract_currency' => 'RUB', 'usd_exchange_rate' => 11801.23, 'fc_exchange_rate' => 136.73,
            'trade_mode' => 'FCA', 'trade_mode_code' => '02', 'contract_char_code' => '01', 'origin_country' => 'RUSSIA',
            'delivery_place' => 'KARSHI', 'shipment_country' => 'RUSSIA', 'trading_country' => 'RUSSIA', 'transport_country' => 'RUSSIA',
            'transit_country' => 'KAZAKHSTAN', 'transit_date' => '2026-08-29',
            'declaring_unit' => 'LLC "DM BROKER SERVIS"', 'declaring_unit_code' => '311107721', 'applicant_address' => 'FERGANA CITY',
            'declarant_name' => 'ЮЛДАШЕВ Д.А.', 'applicant_email' => 'SHUKUZB@MAIL.RU', 'applicant_phone' => '+998999993292',
            'transport_type' => 'АВТО', 'overseas_transport_type' => 'АВТО', 'vehicle_number' => '70R254EB', 'trailer_number' => '707628AA',
            'border_vehicle_number' => '70R254EB', 'customs_code' => '03015', 'border_customs' => 'ЯЛЛАМА', 'customs_office' => 'ДИРЕКТОР ...',
            'responsible_person_name' => 'МАТКАРИМОВ ФАХРИДДИН', 'responsible_person_position' => 'ДИРЕКТОР', 'responsible_person_phone' => '+998995773577',
        ], array_map(fn ($v) => is_float($v) && floor($v) === $v ? (int) $v : $v, $line));
    }

    public function test_merged_quantities_and_declaration_totals(): void
    {
        // bitta ГТД ning 3 ta tovari: jami summalar har qatorda takrorlanadi — bir marta olinadi
        $totals = ['declaration_number' => '0001', 'total_invoice_amount_fc' => 5000, 'total_customs_amount_fc' => 5100];
        $this->importRow(['item_no' => '1', 'quantity' => 100, 'quantity_unit' => 'KG', 'total_tariffs' => 10] + $totals);
        $this->importRow(['item_no' => '2', 'quantity' => 50, 'quantity_unit' => 'KG', 'total_tariffs' => 5] + $totals);
        $this->importRow(['item_no' => '3', 'quantity' => 20, 'quantity_unit' => 'PCS'] + $totals);
        // o'sha mashina, boshqa ГТД
        $this->importRow(['item_no' => '1', 'declaration_number' => '0002', 'quantity' => 1, 'quantity_unit' => 'KG',
            'total_invoice_amount_fc' => 700, 'total_customs_amount_fc' => 800]);

        $line = $this->lines($this->export('import', ['scope' => 'all', 'merge' => true]))->sole();

        $this->assertSame('151; 20', $line['quantity']);      // KG: 100 + 50 + 1, PCS: 20
        $this->assertSame('KG; PCS', $line['quantity_unit']);
        $this->assertEquals(5700, $line['total_invoice_amount_fc']);
        $this->assertEquals(5900, $line['total_customs_amount_fc']);
        $this->assertEquals(15, $line['total_tariffs']);
        $this->assertEquals(4000, $line['gross_weight_kg']);
        $this->assertSame('1; 2; 3', $line['item_no']);
        $this->assertSame('CANE SUGAR', $line['product_description']);
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
        $line = $this->lines($rows)->first();
        $this->assertSame('1; 2; 3; 9', $line['item_no']);
        $this->assertSame('APPLES, PEARS AND QUINCES, FRESH: APPLES; GRAPES, FRESH OR DRIED', $line['product_description']);
    }

    public function test_by_default_every_row_is_exported_separately(): void
    {
        // bitta mashina, bitta kun — lekin birlashtirilmaydi
        $this->importRow(['item_no' => '1', 'hs_code' => '1701991009', 'gross_weight_kg' => 20000, 'customs_amount_usd' => 1000.5]);
        $this->importRow(['item_no' => '2', 'hs_code' => '1701140000', 'gross_weight_kg' => 23000, 'customs_amount_usd' => 2000.25]);
        $this->importRow(['item_no' => '3', 'vehicle_number' => '01A777AA', 'trailer_number' => null]);

        $rows = $this->export('import', ['scope' => 'all']);

        $this->assertCount(4, $rows); // sarlavha + 3 qator — manbadagi qatorlar soni bilan bir xil
        $lines = $this->lines($rows);
        $this->assertEqualsCanonicalizing(['1', '2', '3'], $lines->pluck('item_no')->all());
        $this->assertEqualsCanonicalizing(['1701991009', '1701140000', '1701991009'], $lines->pluck('hs_code')->all());
        $this->assertEqualsCanonicalizing([20000.0, 23000.0, 1000.0], $lines->pluck('gross_weight_kg')->map(fn ($t) => (float) $t)->all());

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
        $this->assertSame(['CHINA', 'TURKEY'], $this->lines($rows)->pluck('origin_country')->sort()->values()->all());
        $this->assertSame(['1; 2', '3'], $this->lines($rows)->sortBy('origin_country')->pluck('item_no')->values()->all());
    }

    public function test_export_rows_are_grouped_by_exporter_buyer_and_date(): void
    {
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 500, 'customs_value_usd' => 100, 'hs_code' => '6802931000']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'TOO BUYER', 'net_weight_kg' => 1500, 'customs_value_usd' => 200, 'hs_code' => '0806101001']);
        $this->exportRow(['exporter_inn' => '306803919', 'importer_name' => 'OTHER BUYER', 'net_weight_kg' => 10]);
        UzbekistanExport::where('exporter_inn', '306803919')->update([
            'responsible_person_name' => 'Karimov Aziz', 'responsible_person_phone' => '+998901234567',
            'declarant_name' => 'Yuldashev D.A.', 'declarant_company' => 'BROKER LLC', 'declarant_email' => 'broker@mail.uz',
        ]);

        $rows = $this->export('export', ['scope' => 'all', 'merge' => true]);

        $this->assertSame(array_values(ZanjeerExport::COLUMNS), $rows[0]); // eksport ham o'sha shablonda
        $this->assertCount(3, $rows);

        $buyer = $this->lines($rows)->firstWhere('importer_name', 'TOO BUYER');
        $this->assertSame('LLC INTERNET TECHNOLOGY', $buyer['exporter_standard_name']);
        $this->assertSame('306803919', $buyer['importer_inn']);            // O'zbek firma STIR'i "Importer code"da
        $this->assertSame('UZBEKISTAN', $buyer['exporter_country']);
        $this->assertSame('Karimov Aziz', $buyer['responsible_person_name']);
        $this->assertSame('+998901234567', $buyer['responsible_person_phone']);
        $this->assertSame('Yuldashev D.A.', $buyer['declarant_name']);
        $this->assertSame('BROKER LLC', $buyer['declaring_unit']);
        $this->assertSame('broker@mail.uz', $buyer['applicant_email']);
        $this->assertSame('RUSSIA', $buyer['trading_country']);             // boradigan davlat
        $this->assertSame('МОСКВА', $buyer['delivery_place']);              // boradigan joy
        $this->assertSame('CPT', $buyer['trade_mode']);
        $this->assertSame('07', $buyer['trade_mode_code']);
        $this->assertSame('ROAD', $buyer['transport_type']);
        $this->assertSame('ЭК', $buyer['regime_type']);
        $this->assertEquals(2000, $buyer['net_weight_kg']);
        $this->assertEquals(300, $buyer['customs_amount_usd']);
        $this->assertSame('0806101001; 6802931000', $buyer['hs_code']);
        $this->assertNull($buyer['vehicle_number']);                         // eksport faylida yo'q
        $this->assertNull($buyer['importer_address']);
    }

    public function test_transport_type_can_be_chosen(): void
    {
        $this->importRow(['item_no' => '1', 'transport_type' => 'АВТО']);
        $this->importRow(['item_no' => '2', 'transport_type' => 'ЖД']);
        $this->importRow(['item_no' => '3', 'transport_type' => 'ЖД:']); // faylda shunday yozuv ham bor
        $this->importRow(['item_no' => '4', 'transport_type' => 'АВИА']);

        $transports = fn (array $body) => $this->lines($this->export('import', ['scope' => 'all'] + $body))
            ->pluck('transport_type')->sort()->values()->all();

        $this->assertSame(['АВТО'], $transports(['transport' => 'auto']));
        $this->assertSame(['ЖД', 'ЖД:'], $transports(['transport' => 'rail']));
        $this->assertSame(['АВТО', 'ЖД', 'ЖД:'], $transports(['transport' => 'both']));
        $this->assertSame(['АВТО', 'ЖД', 'ЖД:'], $transports([])); // standart — ikkalasi

        // eksport: ROAD / RAIL / AIR
        $this->exportRow(['transportation_method' => 'ROAD']);
        $this->exportRow(['transportation_method' => 'RAIL']);
        $this->exportRow(['transportation_method' => 'AIR']);
        $exportTransports = fn (string $transport) => $this->lines($this->export('export', ['scope' => 'all', 'transport' => $transport]))
            ->pluck('transport_type')->sort()->values()->all();
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

        $lines = $this->lines($this->export('import', ['scope' => 'all', 'transport' => 'both']));

        $auto = $lines->where('vehicle_number', 'A1')->values();
        $this->assertEquals([24000, 24000, 24000, 24000, 24000, 5340], $auto->pluck('gross_weight_kg')->all());   // 125.34 t => 5 × 24 t + 5.34 t
        $this->assertEquals(1000, round($auto->sum('customs_amount_usd'), 2));
        $this->assertEquals(191.48, $auto[0]['customs_amount_usd']);                                  // 1000 × 24 / 125.34 = 191.479…
        $this->assertSame(['1', '1', '1', '1', '1', '1'], $auto->pluck('item_no')->all());

        $rail = $lines->where('vehicle_number', 'W1')->values();
        $this->assertEquals([70000, 70000, 10000], $rail->pluck('gross_weight_kg')->all());           // 150 t => 2 × 70 t + 10 t
        $this->assertEquals(300, round($rail->sum('customs_amount_usd'), 2));

        $this->assertEquals([24000], $lines->where('vehicle_number', 'A2')->pluck('gross_weight_kg')->all());

        // avia bo'linmaydi (transport=both avia'ni olmaydi — shuning uchun alohida tekshiramiz)
        $this->assertCount(0, $lines->where('vehicle_number', 'P1'));
    }

    public function test_export_rows_are_split_by_net_weight(): void
    {
        $this->exportRow(['net_weight_kg' => 50000, 'quantity' => 100, 'quantity_unit' => 'PCS', 'customs_value_usd' => 999.99]);

        $lines = $this->lines($this->export('export', ['scope' => 'all']));

        $this->assertEquals([24000, 24000, 2000], $lines->pluck('net_weight_kg')->all());
        $this->assertEquals([48, 48, 4], $lines->pluck('quantity')->all());
        $this->assertEquals(999.99, round($lines->sum('customs_amount_usd'), 2));
    }

    public function test_all_scope_respects_table_filters(): void
    {
        $this->importRow(['item_no' => '1']);
        $this->importRow(['item_no' => '2', 'importer_inn' => '300000001', 'importer_name' => 'LLC OTHER']);

        $rows = $this->export('import', ['scope' => 'all', 'filters' => ['importer_inn' => ['values' => ['300000001']]]]);

        $this->assertCount(2, $rows);
        $this->assertSame('LLC OTHER', $this->lines($rows)->first()['importer_name']);
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
        $this->assertSame('TODAY LLC', $this->lines($rows)->first()['importer_name']);
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

        $this->assertSame(['Sheet1'], $names); // shablondagidek
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

    /** Sarlavhasiz qatorlar: maydon => qiymat */
    private function lines(array $rows): \Illuminate\Support\Collection
    {
        return collect(array_slice($rows, 1))->map(fn ($r) => array_combine(array_keys(ZanjeerExport::COLUMNS),
            array_map(fn ($v) => $v === '' ? null : $v, $r)));
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
            'regime_type' => 'ЭК',
        ])->save();
    }
}
