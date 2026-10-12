<?php

namespace Tests\Feature;

use App\Jobs\ExportUzbekistanFile;
use App\Jobs\ImportUzbekistanFile;
use App\Models\User;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use App\Support\TradeDataset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/** O'zbekiston eksport savdo ma'lumotlari (uzbekistan_exports) */
class UzbekistanExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_file_type_is_detected_from_headers(): void
    {
        $this->assertSame(TradeDataset::Export, TradeDataset::detect($this->excel([$this->row()])->getRealPath()));
        $this->assertSame(TradeDataset::Import, TradeDataset::detect($this->importExcel()->getRealPath()));
        $this->assertNull(TradeDataset::detect($this->excel([['foo']], header: false)->getRealPath()));
    }

    public function test_export_file_uploaded_from_any_page_goes_to_exports_table(): void
    {
        Queue::fake();

        $response = $this->post('/api/uzbekistan-import/import', ['file' => $this->excel([$this->row(), $this->row(['hs' => '6208210000'])])])
            ->assertStatus(202)
            ->assertJsonPath('data.dataset', 'export');
        $this->assertStringContainsString('Eksport', $response->json('message'));

        $task = UzbekistanImportTask::findOrFail($response->json('data.id'));
        (new ImportUzbekistanFile($task))->handle();

        $this->assertSame('completed', $task->fresh()->status);
        $this->assertSame(2, UzbekistanExport::count());
        $this->assertSame(0, UzbekistanImport::count());

        $row = UzbekistanExport::where('hs_code', '9102120000')->firstOrFail();
        $this->assertSame('2026-08-31', $row->date->format('Y-m-d'));
        $this->assertSame('LLC INTERNET TECHNOLOGY', $row->exporter_name);
        $this->assertSame('306803919', $row->exporter_inn);
        $this->assertSame('LLC TTERMINAL FOR INDIVIDUALS', $row->importer_name);
        $this->assertSame('ЭК', $row->regime_type);
        $this->assertEquals(4.28998, $row->invoice_amount_usd);
        $this->assertEquals(0.182, $row->net_weight_kg);
        $this->assertSame('RUSSIA', $row->destination_country);
        $this->assertSame('ROAD', $row->transportation_method);
    }

    public function test_unknown_file_is_rejected(): void
    {
        Queue::fake();

        $this->post('/api/uzbekistan-import/import', ['file' => $this->excel([['foo', 'bar']], header: false)])
            ->assertStatus(422);

        Queue::assertNothingPushed();
        $this->assertSame(0, UzbekistanImportTask::count());
    }

    public function test_reimport_does_not_duplicate_but_keeps_identical_rows_of_file(): void
    {
        // faylning o'zida ikkita bir xil qator — alohida tovar pozitsiyalari
        $rows = [$this->row(), $this->row(), $this->row(['hs' => '6208210000'])];

        $this->runImport($rows);
        $this->assertSame(3, UzbekistanExport::count());

        $this->runImport($rows);
        $this->assertSame(3, UzbekistanExport::count());

        // boshqa oy fayli — yangi qatorlar qo'shiladi
        $this->runImport([$this->row(['date' => '2026-09-01'])]);
        $this->assertSame(4, UzbekistanExport::count());
    }

    public function test_export_data_has_its_own_list_columns_and_filters(): void
    {
        $this->seedRows();

        $this->getJson('/api/uzbekistan-export')->assertOk()->assertJsonPath('data.total', 4);
        $this->getJson('/api/uzbekistan-import')->assertOk()->assertJsonPath('data.total', 0);
        // row_key ichki kalit — javobda ko'rinmaydi
        $this->assertArrayNotHasKey('row_key', $this->getJson('/api/uzbekistan-export')->json('data.data.0'));

        $columns = $this->getJson('/api/uzbekistan-export/columns')->assertOk();
        $this->assertSame('export', $columns->json('dataset'));
        $this->assertSame(UzbekistanExport::MAIN_FILTERS, $columns->json('main_filters'));
        $this->assertCount(count(UzbekistanExport::LABELS), $columns->json('columns'));

        $total = fn (array $q) => $this->getJson('/api/uzbekistan-export?'.http_build_query($q))->assertOk()->json('data.total');

        $this->assertSame(2, $total(['filters' => ['destination_country' => ['values' => ['RUSSIA']]]]));
        $this->assertSame(1, $total(['filters' => ['transportation_method' => ['values' => ['AIR']]]]));
        $this->assertSame(3, $total(['filters' => ['exporter_inn' => ['mode' => 'not_in', 'values' => ['309590756']]]]));
        $this->assertSame(3, $total(['filters' => ['invoice_amount_usd' => ['conditions' => [['op' => 'gt', 'value' => '100']]]]]));
        $this->assertSame(1, $total(['filters' => ['invoice_amount_usd' => ['conditions' => [['op' => 'gt', 'value' => '1000'], ['op' => 'lt', 'value' => '5000']]]]]));
        $this->assertSame(1, $total(['filters' => ['product_description' => ['conditions' => [['op' => 'contains', 'value' => 'cotton']]]]]));

        $weights = array_column($this->getJson('/api/uzbekistan-export?sort[]=-net_weight_kg')->json('data.data'), 'net_weight_kg');
        $this->assertEquals([1744.92, 71.744, 6.92, 0.182], array_map('floatval', $weights));

        // import ustuni eksportda yo'q
        $this->getJson('/api/uzbekistan-export?'.http_build_query(['filters' => ['customs_office' => ['values' => ['x']]]]))->assertStatus(422);

        $options = $this->getJson('/api/uzbekistan-export/filter-options?field=destination_country')->assertOk()->json('data.values');
        $this->assertEquals([['value' => 'KAZAKHSTAN', 'count' => 2], ['value' => 'RUSSIA', 'count' => 2]], $options);
    }

    public function test_export_data_can_be_downloaded_as_excel(): void
    {
        $this->seedRows();

        Queue::fake();
        $response = $this->postJson('/api/uzbekistan-export/export', [
            'filters' => ['destination_country' => ['values' => ['KAZAKHSTAN']]],
            'sort' => ['net_weight_kg'],
        ])->assertStatus(202)->assertJsonPath('data.dataset', 'export');

        $task = UzbekistanImportTask::findOrFail($response->json('data.id'));
        $this->assertStringStartsWith('uzbekistan_export_', $task->original_name);
        (new ExportUzbekistanFile($task))->handle();

        $rows = $this->readExcel(Storage::disk('local')->path($task->fresh()->file_path));
        $this->assertSame(array_values(UzbekistanExport::LABELS), $rows[0]);
        $this->assertCount(3, $rows); // sarlavha + 2 qator
        $weight = array_search('Net weight (kg)', $rows[0]);
        $this->assertEquals([6.92, 1744.92], [$rows[1][$weight], $rows[2][$weight]]);

        $this->get("/api/uzbekistan-import/tasks/{$task->id}/download")->assertOk()->assertDownload($task->original_name);

        // vazifalar ro'yxatini ma'lumot turi bo'yicha filtrlash
        $this->getJson('/api/uzbekistan-import/tasks?dataset=export')->assertJsonPath('data.total', 2);
        $this->getJson('/api/uzbekistan-import/tasks?dataset=import')->assertJsonPath('data.total', 0);
    }

    /**
     * 4 ta qator:
     *  0.182 kg  4.29$     RUSSIA      ROAD  306803919
     *  71.744 kg 1164.27$  RUSSIA      ROAD  309590756  (cotton)
     *  6.92 kg   193.04$   KAZAKHSTAN  AIR   306803919
     *  1744.92kg 9923.25$  KAZAKHSTAN  RAIL  306803919
     */
    private function seedRows(): void
    {
        $this->runImport([
            $this->row(),
            $this->row(['hs' => '6208210000', 'inn' => '309590756', 'desc' => 'COTTON DRESSES', 'weight' => 71.744, 'amount' => 1164.26947]),
            $this->row(['hs' => '3305900009', 'country' => 'KAZAKHSTAN', 'transport' => 'AIR', 'weight' => 6.92, 'amount' => 193.04055]),
            $this->row(['hs' => '6004100000', 'country' => 'KAZAKHSTAN', 'transport' => 'RAIL', 'weight' => 1744.92, 'amount' => 9923.25]),
        ]);
    }

    private function runImport(array $rows): void
    {
        $path = $this->excel($rows)->storeAs('uzbekistan/imports', uniqid().'.xlsx', 'local');
        $task = UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'export', 'file_path' => $path]);

        (new ImportUzbekistanFile($task))->handle();
        $this->assertSame('completed', $task->fresh()->status);
    }

    /** Asl eksport fayli tuzilishidagi 29 ustunli qator */
    private function row(array $o = []): array
    {
        $row = array_fill(0, 29, '');
        $row[0] = $o['date'] ?? '2026-08-31';
        $row[1] = 'LLC TTERMINAL FOR INDIVIDUALS';
        $row[2] = 'LLC INTERNET TECHNOLOGY';
        $row[3] = $o['inn'] ?? '306803919';
        $row[4] = $o['hs'] ?? '9102120000';
        $row[5] = $o['desc'] ?? 'WRISTWATCHES, ELECTRICALLY OPERATED';
        $row[6] = 1;
        $row[8] = $o['weight'] ?? 0.182;
        $row[9] = $o['amount'] ?? 4.28998;
        $row[10] = 0;
        $row[11] = $o['amount'] ?? 4.28998;
        $row[12] = 4;
        $row[13] = $o['country'] ?? 'RUSSIA';
        $row[14] = 'МОСКВА';
        $row[17] = $o['transport'] ?? 'ROAD';
        $row[18] = 'CPT';
        $row[19] = $o['transport'] ?? 'ROAD';
        $row[20] = 'ЭК  ';
        $row[21] = '10';
        $row[26] = '07';

        return $row;
    }

    private function excel(array $rows, bool $header = true): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uzbexp').'.xlsx';

        $writer = new Writer();
        $writer->openToFile($path);
        if ($header) {
            $headers = array_fill(0, 29, 'col');
            $headers[3] = 'Exporter ID';
            $headers[4] = 'HS code';
            $headers[13] = 'Destination country';
            $writer->addRow(Row::fromValues(['[UZBEKISTAN] country (export) trade country download data']));
            $writer->addRow(Row::fromValues($headers));
        }
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'Download_UZBEKISTAN_Export_Trade_Data.xlsx', null, null, true);
    }

    private function importExcel(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'uzbimp').'.xlsx';

        $headers = array_fill(0, 72, 'col');
        $headers[4] = 'declaration number';
        $headers[20] = 'HS Code';
        $headers[71] = 'Customs Office';

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['[UZBEKISTAN] country (import) trade country download data']));
        $writer->addRow(Row::fromValues($headers));
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
