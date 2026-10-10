<?php

namespace Tests\Feature;

use App\Jobs\ExportUzbekistanFile;
use App\Jobs\ImportUzbekistanFile;
use App\Models\User;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class UzbekistanImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_import_is_queued_and_saves_parsed_rows(): void
    {
        Queue::fake();

        $response = $this->post('/api/uzbekistan-import/import', [
            'file' => $this->excel([$this->row(), $this->row(['item' => '2', 'vehicle' => '94506219', 'transport' => 'ЖД'])]),
        ])->assertStatus(202);

        $task = UzbekistanImportTask::findOrFail($response->json('data.id'));
        $this->assertSame('pending', $task->status);
        Storage::disk('local')->assertExists($task->file_path);
        Queue::assertPushedOn('exports', ImportUzbekistanFile::class);

        (new ImportUzbekistanFile($task))->handle();

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertSame(2, $task->processed_rows);
        Storage::disk('local')->assertMissing($task->file_path);

        $row = UzbekistanImport::where('item_no', 1)->firstOrFail();
        $this->assertSame('0012612', $row->declaration_number);
        $this->assertSame('2026-08-31', $row->registration_date->format('Y-m-d'));
        $this->assertSame('НД', $row->regime_type);
        $this->assertSame('ANDIJON VILOYATI', $row->importer_region);
        $this->assertSame('CHINA', $row->exporter_country);
        $this->assertSame('08KG648ARH', $row->vehicle_number);
        $this->assertSame('06KG933PE', $row->trailer_number);
        $this->assertSame('08KG648ARH/06KG933PE', $row->border_vehicle_number);
        $this->assertSame('КАБИЛОВ ШЕРЗОД МАХАМАДЖОНОВИЧ', $row->responsible_person_name);
        $this->assertSame('ДИРЕКТОР', $row->responsible_person_position);
        $this->assertSame('+998911209099', $row->responsible_person_phone);
        $this->assertEquals(3100, $row->net_weight_kg);

        $rail = UzbekistanImport::where('item_no', 2)->firstOrFail();
        $this->assertSame('94506219', $rail->vehicle_number);
        $this->assertNull($rail->trailer_number);
    }

    public function test_reimporting_same_file_does_not_create_duplicates(): void
    {
        $this->runImport([$this->row(), $this->row(['item' => '2'])]);
        $this->runImport([$this->row(['brand' => 'NEW BRAND']), $this->row(['item' => '2'])]);

        $this->assertSame(2, UzbekistanImport::count());
        $this->assertSame('NEW BRAND', UzbekistanImport::where('item_no', 1)->value('brand'));
    }

    public function test_wrong_file_marks_task_as_failed(): void
    {
        $file = $this->excel([['foo', 'bar']], withHeader: false);
        $path = $file->storeAs('uzbekistan/imports', 'wrong.xlsx', 'local');
        $task = UzbekistanImportTask::create(['type' => 'import', 'file_path' => $path]);

        $job = new ImportUzbekistanFile($task);
        try {
            $job->handle();
            $this->fail('Exception kutilgan edi');
        } catch (\RuntimeException $e) {
            $job->failed($e);
        }

        $this->assertSame('failed', $task->fresh()->status);
        $this->assertStringContainsString("Fayl formati noto'g'ri", $task->fresh()->error);
    }

    /** Excel'dagi galochkali ro'yxat: belgilanganlar, "shulardan tashqari" va (Blanks) */
    public function test_value_list_filter_like_excel(): void
    {
        $this->seedRows();

        $this->assertTotal(5, []);
        $this->assertTotal(2, ['filters' => ['importer_name' => ['values' => ['LLC MAX', 'JSC THE ONE']]]]);
        // not_in: belgilanganlardan tashqari hammasi, bo'sh kataklar ham qoladi
        $this->assertTotal(3, ['filters' => ['importer_name' => ['mode' => 'not_in', 'values' => ['LLC MAX', 'JSC THE ONE']]]]);
        // (Blanks) faqat
        $this->assertTotal(1, ['filters' => ['brand' => ['blanks' => 1]]]);
        // qiymat + (Blanks)
        $this->assertTotal(2, ['filters' => ['brand' => ['values' => ['BYD'], 'blanks' => 1]]]);
        // not_in + (Blanks) belgisi olib tashlangan — bo'shlar ham chiqib ketadi
        $this->assertTotal(3, ['filters' => ['brand' => ['mode' => 'not_in', 'values' => ['BYD'], 'blanks' => 1]]]);
        $this->assertTotal(4, ['filters' => ['brand' => ['mode' => 'not_in', 'values' => ['BYD']]]]);
        // ustunlar filtrlari AND bilan
        $this->assertTotal(1, ['filters' => [
            'transport_type' => ['values' => ['АВТО']],
            'origin_country' => ['values' => ['RUSSIA']],
            'trade_mode' => ['values' => ['FCA']],
        ]]);
        // raqam va sana ustunlarida ham galochka ishlaydi
        $this->assertTotal(1, ['filters' => ['net_weight_kg' => ['values' => ['50']]]]);
        $this->assertTotal(2, ['filters' => ['transit_date' => ['values' => ['2026-08-20']]]]);
    }

    /** Excel'dagi Text / Number / Date Filters va Custom AutoFilter (2 shart, AND/OR) */
    public function test_condition_filters_like_excel(): void
    {
        $this->seedRows();

        $f = fn (string $column, array ...$conditions) => ['filters' => [$column => ['conditions' => $conditions]]];

        // matn
        $this->assertTotal(1, $f('importer_name', ['op' => 'contains', 'value' => 'max']));
        $this->assertTotal(4, $f('importer_name', ['op' => 'not_contains', 'value' => 'max']));
        $this->assertTotal(2, $f('importer_name', ['op' => 'starts_with', 'value' => 'JSC']));
        $this->assertTotal(3, $f('importer_name', ['op' => 'not_starts_with', 'value' => 'JSC']));
        $this->assertTotal(1, $f('importer_name', ['op' => 'ends_with', 'value' => 'PRO']));
        $this->assertTotal(1, $f('importer_name', ['op' => 'equals', 'value' => 'LLC MAX']));
        $this->assertTotal(4, $f('importer_name', ['op' => 'not_equals', 'value' => 'LLC MAX']));
        // Excel wildcard: * va ?
        $this->assertTotal(2, $f('importer_name', ['op' => 'equals', 'value' => 'JSC*']));
        $this->assertTotal(1, $f('importer_name', ['op' => 'equals', 'value' => 'LLC MA?']));
        // % va _ oddiy belgi sifatida qidiriladi
        $this->assertTotal(1, $f('importer_name', ['op' => 'contains', 'value' => '100%']));
        $this->assertTotal(0, $f('importer_name', ['op' => 'contains', 'value' => 'L_C']));
        // bo'sh / bo'sh emas; "o'z ichiga olmaydi" bo'sh kataklarni ham ko'rsatadi
        $this->assertTotal(1, $f('brand', ['op' => 'blank']));
        $this->assertTotal(4, $f('brand', ['op' => 'not_blank']));
        $this->assertTotal(4, $f('brand', ['op' => 'not_contains', 'value' => 'BYD']));

        // raqam
        $this->assertTotal(2, $f('net_weight_kg', ['op' => 'gt', 'value' => '1000']));
        $this->assertTotal(3, $f('net_weight_kg', ['op' => 'lte', 'value' => '1000']));
        $this->assertTotal(1, $f('net_weight_kg', ['op' => 'between', 'value' => '50', 'value2' => '1000']));
        $this->assertTotal(1, $f('net_weight_kg', ['op' => 'between', 'value' => '1000', 'value2' => '50'])); // tartib muhim emas
        $this->assertTotal(4, $f('net_weight_kg', ['op' => 'not_between', 'value' => '50', 'value2' => '1000']));
        // raqamda matn kabi qidirish ham ishlaydi
        $this->assertTotal(2, $f('hs_code', ['op' => 'starts_with', 'value' => '84']));

        // sana
        $this->assertTotal(3, $f('transit_date', ['op' => 'gte', 'value' => '2026-08-25']));
        $this->assertTotal(2, $f('transit_date', ['op' => 'between', 'value' => '2026-08-01', 'value2' => '2026-08-24']));

        // ikki shart: AND / OR
        $this->assertTotal(2, ['filters' => ['importer_name' => ['logic' => 'or', 'conditions' => [
            ['op' => 'equals', 'value' => 'LLC MAX'],
            ['op' => 'ends_with', 'value' => 'PRO'],
        ]]]]);
        $this->assertTotal(1, ['filters' => ['net_weight_kg' => ['logic' => 'and', 'conditions' => [
            ['op' => 'gt', 'value' => '10'],
            ['op' => 'lt', 'value' => '1000'],
        ]]]]);

        // galochka va shart bir ustunda birga
        $this->assertTotal(1, ['filters' => ['importer_name' => [
            'values' => ['JSC THE ONE', 'JSC CHIG ATOY'],
            'conditions' => [['op' => 'contains', 'value' => 'ONE']],
        ]]]);
    }

    public function test_invalid_filters_return_422(): void
    {
        $this->getJson('/api/uzbekistan-import?'.http_build_query(['filters' => ['password' => ['values' => ['x']]]]))->assertStatus(422);
        $this->getJson('/api/uzbekistan-import?'.http_build_query(['filters' => ['net_weight_kg' => ['conditions' => [['op' => 'gt', 'value' => 'abc']]]]]))->assertStatus(422);
        $this->getJson('/api/uzbekistan-import?'.http_build_query(['filters' => ['transit_date' => ['conditions' => [['op' => 'gt', 'value' => '31.08.2026']]]]]))->assertStatus(422);
        $this->getJson('/api/uzbekistan-import?'.http_build_query(['filters' => ['brand' => ['conditions' => [['op' => 'between', 'value' => 'a']]]]]))->assertStatus(422);
        $this->getJson('/api/uzbekistan-import?'.http_build_query(['filters' => ['brand' => ['conditions' => [['op' => 'regex', 'value' => 'a']]]]]))->assertStatus(422);
        $this->getJson('/api/uzbekistan-import?sort[]=password')->assertStatus(422);
    }

    /** Ko'p darajali saralash, bo'sh kataklar doim pastda */
    public function test_sorting_like_excel(): void
    {
        $this->seedRows();

        $names = fn (array $sort) => array_column($this->getJson('/api/uzbekistan-import?'.http_build_query(['sort' => $sort]))->json('data.data'), 'importer_name');
        $brands = fn (array $sort) => array_column($this->getJson('/api/uzbekistan-import?'.http_build_query(['sort' => $sort]))->json('data.data'), 'brand');

        $this->assertSame(['JSC CHIG ATOY', 'JSC THE ONE', 'LLC 100% FOODS', 'LLC COCO CERAMIC PRO', 'LLC MAX'], $names(['importer_name']));
        $this->assertSame(['LLC MAX', 'LLC COCO CERAMIC PRO', 'LLC 100% FOODS', 'JSC THE ONE', 'JSC CHIG ATOY'], $names(['-importer_name']));
        $this->assertSame('ARSA', $brands(['brand'])[0]);
        $this->assertNull($brands(['brand'])[4]);
        $this->assertNull($brands(['-brand'])[4]);
        // og'irlik bo'yicha kamayish, keyin nom bo'yicha
        $this->assertSame(['LLC COCO CERAMIC PRO', 'JSC THE ONE', 'JSC CHIG ATOY', 'LLC MAX', 'LLC 100% FOODS'], $names(['-net_weight_kg']));
        // ikki darajali: brand bo'yicha, bir xil brand ichida nom bo'yicha kamayish
        $this->assertSame(['LLC COCO CERAMIC PRO', 'JSC CHIG ATOY', 'LLC MAX', 'JSC THE ONE', 'LLC 100% FOODS'], $names(['brand', '-importer_name']));
    }

    public function test_filter_options_like_excel_dropdown(): void
    {
        $this->seedRows();

        $options = fn (array $q) => $this->getJson('/api/uzbekistan-import/filter-options?'.http_build_query($q))->assertOk()->json('data');

        // alifbo tartibida, soni bilan; bo'sh kataklar alohida
        $brand = $options(['field' => 'brand']);
        $this->assertEquals([
            ['value' => 'ARSA', 'count' => 1],
            ['value' => 'BYD', 'count' => 1],
            ['value' => 'RK', 'count' => 2],
        ], $brand['values']);
        $this->assertSame(1, $brand['blanks']);
        $this->assertFalse($brand['has_more']);

        // boshqa ustun filtri hisobga olinadi, shu ustunniki — yo'q
        $transport = $options(['field' => 'transport_type', 'filters' => [
            'transport_type' => ['values' => ['АВТО']],
            'importer_name' => ['values' => ['LLC MAX', 'JSC THE ONE']],
        ]]);
        $this->assertEquals([['value' => 'АВТО', 'count' => 1], ['value' => 'ЖД', 'count' => 1]], $transport['values']);

        // ro'yxat ichida qidirish va limit
        $this->assertCount(1, $options(['field' => 'importer_name', 'q' => 'coco'])['values']);
        $this->assertTrue($options(['field' => 'importer_name', 'limit' => 2])['has_more']);

        $this->getJson('/api/uzbekistan-import/filter-options?field=password')->assertStatus(422);
    }

    public function test_columns_endpoint_describes_table(): void
    {
        $response = $this->getJson('/api/uzbekistan-import/columns')->assertOk();

        $columns = collect($response->json('columns'))->keyBy('name');
        $this->assertSame(['name' => 'net_weight_kg', 'label' => 'Net weight(kg)', 'type' => 'number'], $columns['net_weight_kg']);
        $this->assertSame('date', $columns['transit_date']['type']);
        $this->assertSame('text', $columns['importer_name']['type']);
        $this->assertSame(UzbekistanImport::MAIN_FILTERS, $response->json('main_filters'));
    }

    public function test_export_is_queued_and_file_can_be_downloaded(): void
    {
        $this->runImport([
            $this->row(),
            $this->row(['item' => '2', 'importer' => 'LLC MAX']),
        ]);

        Queue::fake();
        $response = $this->postJson('/api/uzbekistan-import/export', [
            'filters' => ['importer_name' => ['values' => ['LLC MAX']]],
        ])->assertStatus(202);
        Queue::assertPushedOn('exports', ExportUzbekistanFile::class);

        $task = UzbekistanImportTask::findOrFail($response->json('data.id'));
        $this->getJson("/api/uzbekistan-import/tasks/{$task->id}/download")->assertNotFound();

        (new ExportUzbekistanFile($task))->handle();

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertSame(1, $task->total_rows);
        $this->getJson("/api/uzbekistan-import/tasks/{$task->id}")->assertJsonPath('data.progress', 100);

        $rows = $this->readExcel(Storage::disk('local')->path($task->file_path));
        $this->assertCount(2, $rows); // sarlavha + 1 qator
        $this->assertSame(array_values(UzbekistanImport::LABELS), $rows[0]);
        $this->assertSame('LLC MAX', $rows[1][array_search('Importer name', $rows[0])]);
        $this->assertEquals(3100, $rows[1][array_search('Net weight(kg)', $rows[0])]);

        $this->get("/api/uzbekistan-import/tasks/{$task->id}/download")
            ->assertOk()
            ->assertDownload($task->original_name);
    }

    public function test_export_keeps_table_sort_order(): void
    {
        $this->seedRows();

        $task = UzbekistanImportTask::create(['type' => 'export', 'original_name' => 'sorted.xlsx', 'params' => [
            'filters' => ['transport_type' => ['values' => ['АВТО']]],
            'sort' => ['-net_weight_kg'],
        ]]);
        (new ExportUzbekistanFile($task))->handle();

        $rows = $this->readExcel(Storage::disk('local')->path($task->fresh()->file_path));
        $weight = array_search('Net weight(kg)', $rows[0]);
        $this->assertSame([3100, 2000, 50, 5], array_map(fn ($r) => (int) $r[$weight], array_slice($rows, 1)));
    }

    /**
     * 5 ta qator:
     *  1 LLC COCO CERAMIC PRO  АВТО CHINA  CIF 3100kg brand ARSA  transit 08-28 hs 3214
     *  2 LLC MAX               ЖД   CHINA  CIF   10kg brand RK    transit 08-20 hs 8441
     *  3 JSC THE ONE           АВТО RUSSIA FCA 2000kg brand RK    transit 08-25 hs 8471
     *  4 JSC CHIG ATOY         АВТО CHINA  CIF   50kg brand BYD   transit 08-20 hs 3214
     *  5 LLC 100% FOODS        АВТО CHINA  CIF    5kg brand —     transit 08-30 hs 3214
     */
    private function seedRows(): void
    {
        $this->runImport([
            $this->row(['transit' => '2026-08-28T00:00:00']),
            $this->row(['item' => '2', 'importer' => 'LLC MAX', 'inn' => '207144815', 'transport' => 'ЖД', 'hs' => '8441107000', 'weight' => 10, 'brand' => 'RK', 'transit' => '2026-08-20T00:00:00']),
            $this->row(['item' => '3', 'importer' => 'JSC THE ONE', 'origin' => 'RUSSIA', 'trade_mode' => 'FCA', 'hs' => '8471300000', 'weight' => 2000, 'brand' => 'RK', 'transit' => '2026-08-25T00:00:00']),
            $this->row(['item' => '4', 'importer' => 'JSC CHIG ATOY', 'weight' => 50, 'brand' => 'BYD', 'transit' => '2026-08-20T00:00:00']),
            $this->row(['item' => '5', 'importer' => 'LLC 100% FOODS', 'weight' => 5, 'brand' => '', 'transit' => '2026-08-30T00:00:00']),
        ]);
    }

    private function assertTotal(int $expected, array $query): void
    {
        $this->getJson('/api/uzbekistan-import?'.http_build_query($query))
            ->assertOk()
            ->assertJsonPath('data.total', $expected);
    }

    private function runImport(array $rows): void
    {
        $path = $this->excel($rows)->storeAs('uzbekistan/imports', uniqid().'.xlsx', 'local');
        $task = UzbekistanImportTask::create(['type' => 'import', 'file_path' => $path]);

        (new ImportUzbekistanFile($task))->handle();
    }

    /** Asl fayl tuzilishidagi 72 ustunli qator */
    private function row(array $o = []): array
    {
        $row = array_fill(0, 72, '');
        $row[0] = '2026-08-31';
        $row[1] = '2026-08-31T00:00:00';
        $row[2] = 'НД  ';
        $row[3] = '40';
        $row[4] = '0012612';
        $row[5] = $o['importer'] ?? 'LLC COCO CERAMIC PRO';
        $row[7] = 'REPUBLIC OF UZBEKISTAN, ANZHIZHAN REGION, ANDIJAN, UL.SERKUYOSH 81;';
        $row[8] = $o['inn'] ?? '307281628';
        $row[15] = 'YIWU LIANGFEENG TRADING CO LTD';
        $row[16] = 'YIWU LIANGFEENG TRADING CO LTD';
        $row[17] = 'NO. 468 CHOUZHOU NORTH ROAD, YIWU CITY, ZHEJIANG, CHINA;';
        $row[20] = $o['hs'] ?? '3214101009';
        $row[21] = 'GLASS AND GARDEN PUTTIES';
        $row[22] = $o['brand'] ?? 'ARSA';
        $row[23] = $o['item'] ?? '1';
        $row[24] = 0;
        $row[26] = $o['weight'] ?? 3100;
        $row[27] = $o['weight'] ?? 3100;
        $row[37] = 5074174.863;
        $row[41] = $o['trade_mode'] ?? 'CIF';
        $row[47] = $o['origin'] ?? 'CHINA';
        $row[50] = 'CHINA';
        $row[65] = $o['transport'] ?? 'АВТО';
        $row[67] = $o['vehicle'] ?? '08KG648ARH/06KG933PE';
        $row[68] = $o['vehicle'] ?? '08KG648ARH/06KG933PE';
        $row[55] = $o['transit'] ?? '2026-08-28T00:00:00';
        $row[69] = '03015';
        $row[71] = 'ДИРЕКТОР КАБИЛОВ ШЕРЗОД МАХАМАДЖОНОВИЧ, ТЕЛ.:+998911209099. ОБЯЗУЮСЬ ПРЕДСТАВИТЬ ПОЛНУЮ ГТД ДО 29.10.2026;';

        return $row;
    }

    private function excel(array $rows, bool $withHeader = true): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uzb').'.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);
        if ($withHeader) {
            $header = array_fill(0, 72, 'col');
            $header[4] = 'declaration number';
            $header[20] = 'HS Code';
            $header[71] = 'Customs Office';
            $writer->addRow(Row::fromValues(['[UZBEKISTAN] country (import) trade country download data']));
            $writer->addRow(Row::fromValues($header));
        }
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'Download_UZBEKISTAN_Import_Trade_Data.xlsx', null, null, true);
    }

    private function readExcel(string $path): array
    {
        $reader = new Reader();
        $reader->open($path);
        $rows = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $rows[] = $row->toArray();
            }
        }
        $reader->close();

        return $rows;
    }
}
