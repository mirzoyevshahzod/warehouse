<?php

namespace Tests\Feature;

use App\Jobs\ImportUzbekistanFile;
use App\Models\User;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanExporterContact;
use App\Models\UzbekistanExporterDeclarant;
use App\Models\UzbekistanImport;
use App\Models\UzbekistanImportTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/** Eksportyor firmalarga mas'ul xodim va deklarantni qo'lda biriktirish */
class UzbekistanExporterContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Sanctum::actingAs(User::factory()->create());

        // 305048781 — bitta firma uch xil nom bilan (5 qator); 306803919 — boshqa firma (2 qator)
        $this->runImport([
            $this->row('305048781', 'JSC UZKIMYOIMPEKS', '1'),
            $this->row('305048781', 'JSC UZKIMYOIMPEKS', '2'),
            $this->row('305048781', 'JSC UZKIMYOIMPEKS', '3'),
            $this->row('305048781', 'LLC UZKIMYOIMPEKS ЭЛ ПОЧТА', '4'),
            $this->row('305048781', 'UZKIMYOIMPEKS', '5'),
            $this->row('306803919', 'LLC INTERNET TECHNOLOGY', '6'),
            $this->row('306803919', 'LLC INTERNET TECHNOLOGY', '7'),
        ]);
    }

    public function test_exporters_are_unique_by_inn_with_most_common_name(): void
    {
        $firms = $this->getJson('/api/uzbekistan-export/exporters')->assertOk()->json('data');

        // qatorlar soni bo'yicha kamayish tartibida: 5 > 2
        $this->assertCount(2, $firms);
        $this->assertSame('305048781', $firms[0]['inn']);
        $this->assertSame('JSC UZKIMYOIMPEKS', $firms[0]['name']);
        $this->assertSame(5, $firms[0]['rows']);
        $this->assertCount(3, $firms[0]['names']);
        $this->assertSame(['inn' => '306803919', 'name' => 'LLC INTERNET TECHNOLOGY', 'rows' => 2, 'contact' => null],
            array_intersect_key($firms[1], array_flip(['inn', 'name', 'rows', 'contact'])));

        // qidiruv nomning istalgan yozilishi va STIR bo'yicha
        $this->assertCount(1, $this->getJson('/api/uzbekistan-export/exporters?q=почта')->json('data'));
        $this->assertCount(1, $this->getJson('/api/uzbekistan-export/exporters?q=3068')->json('data'));
    }

    public function test_exporters_are_sorted_by_rows_desc_then_name(): void
    {
        // alifboda birinchi bo'lsa ham, qatori kam firma oxirida turadi
        $this->runImport([
            $this->row('300000001', 'AAA FIRST LLC', '8'),
            $this->row('300000002', 'ZZZ LAST LLC', '9'),
            $this->row('300000002', 'ZZZ LAST LLC', '10'),
        ]);

        $inns = array_column($this->getJson('/api/uzbekistan-export/exporters')->json('data'), 'inn');

        // 5 qator, keyin 2 ta qatorlilar (LLC INTERNET < ZZZ), oxirida 1 qatorli
        $this->assertSame(['305048781', '306803919', '300000002', '300000001'], $inns);
    }

    public function test_firms_endpoint_shows_exporters_with_assigned_contact(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/306803919/contact', ['full_name' => 'Valiyev Bobur', 'position' => 'Direktor'])->assertOk();

        $result = $this->getJson('/api/uzbekistan-export/firms')->assertOk()->json('data');

        $this->assertSame(2, $result['total']);
        $this->assertSame(1, $result['with_contacts']);
        $this->assertSame(['305048781', '306803919'], array_column($result['data'], 'inn'));
        $this->assertSame('JSC UZKIMYOIMPEKS', $result['data'][0]['name']);
        $this->assertSame([], $result['data'][0]['contacts']);
        $this->assertNull($result['data'][0]['address']);
        $this->assertSame([['full_name' => 'Valiyev Bobur', 'position' => 'Direktor', 'phone' => null, 'rows' => 2]], $result['data'][1]['contacts']);
        $this->assertSame([['value' => 'RUSSIA', 'rows' => 2]], $result['data'][1]['countries']);
    }

    public function test_firms_summary_counts_import_and_export_firms(): void
    {
        // 305048781 ham import, ham eksport qilgan; 200000001 — faqat import
        UzbekistanImport::create(['importer_inn' => '305048781', 'importer_name' => 'UZKIMYOIMPEKS', 'item_no' => '1']);
        UzbekistanImport::create(['importer_inn' => '305048781', 'importer_name' => 'UZKIMYOIMPEKS', 'item_no' => '2']);
        UzbekistanImport::create(['importer_inn' => '200000001', 'importer_name' => 'ONLY IMPORT LLC', 'item_no' => '3']);
        UzbekistanImport::create(['importer_inn' => null, 'importer_name' => 'NO INN', 'item_no' => '4']);

        $this->getJson('/api/uzbekistan-firms/summary')->assertOk()->assertJson(['data' => [
            'import' => ['total' => 2, 'rows' => 3],
            'export' => ['total' => 2, 'rows' => 7],
            'unique_total' => 3,
            'both' => 1,
        ]]);

        $export = $this->getJson('/api/uzbekistan-export/firms')->json('data.data');
        $this->assertSame([true, false], array_column($export, 'in_other'));

        $import = $this->getJson('/api/uzbekistan-import/firms')->json('data.data');
        $this->assertSame(['305048781' => true, '200000001' => false], array_column($import, 'in_other', 'inn'));
    }

    public function test_saving_contact_writes_it_to_all_rows_of_the_firm(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/305048781/contact', [
            'full_name' => 'Karimov Aziz',
            'position' => 'Direktor',
            'phone' => '+998 90 123-45-67',
        ])->assertOk()->assertJsonPath('updated_rows', 5);

        // barcha nom yozilishidagi 5 qator yangilandi, boshqa firma tegilmadi
        $this->assertSame(5, UzbekistanExport::where('responsible_person_name', 'Karimov Aziz')->count());
        $this->assertSame(2, UzbekistanExport::whereNull('responsible_person_name')->count());

        $row = UzbekistanExport::where('exporter_inn', '305048781')->first();
        $this->assertSame('Direktor', $row->responsible_person_position);
        $this->assertSame('+998901234567', $row->responsible_person_phone); // normallashtirilgan

        $this->assertSame('Karimov Aziz', $this->getJson('/api/uzbekistan-export/exporters?q=305048781')->json('data.0.contact.full_name'));

        // jadvalda filtr bilan topiladi
        $this->getJson('/api/uzbekistan-export?'.http_build_query(['filters' => ['responsible_person_name' => ['values' => ['Karimov Aziz']]]]))
            ->assertJsonPath('data.total', 5);

        // tahrirlash — qayta saqlash; chet el raqami o'zgarishsiz qoladi
        $this->putJson('/api/uzbekistan-export/exporters/305048781/contact', [
            'full_name' => 'Karimov Aziz',
            'position' => 'Bosh direktor',
            'phone' => '+7 999 123 45 67',
        ])->assertOk();
        $this->assertSame(1, UzbekistanExporterContact::count());
        $this->assertSame(5, UzbekistanExport::where('responsible_person_position', 'Bosh direktor')->where('responsible_person_phone', '+7 999 123 45 67')->count());
    }

    public function test_contact_is_applied_to_newly_uploaded_files(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/306803919/contact', ['full_name' => 'Valiyev Bobur'])->assertOk();

        $this->runImport([
            $this->row('306803919', 'LLC INTERNET TECHNOLOGY', '8'),
            $this->row('309590756', 'LLC DICLE TEKS', '9'),
        ]);

        $this->assertSame('Valiyev Bobur', UzbekistanExport::where('hs_code', '8')->value('responsible_person_name'));
        $this->assertNull(UzbekistanExport::where('hs_code', '9')->value('responsible_person_name'));
        $this->assertSame(3, UzbekistanExport::where('responsible_person_name', 'Valiyev Bobur')->count());
    }

    public function test_deleting_contact_clears_rows(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/306803919/contact', ['full_name' => 'Valiyev Bobur', 'phone' => '901234567'])->assertOk();

        $this->deleteJson('/api/uzbekistan-export/exporters/306803919/contact')
            ->assertOk()
            ->assertJsonPath('updated_rows', 2);

        $this->assertSame(0, UzbekistanExporterContact::count());
        $this->assertSame(7, UzbekistanExport::whereNull('responsible_person_name')->whereNull('responsible_person_phone')->count());

        // qayta yuklashda ham qaytib kelmaydi
        $this->runImport([$this->row('306803919', 'LLC INTERNET TECHNOLOGY', '6')]);
        $this->assertSame(7, UzbekistanExport::whereNull('responsible_person_name')->count());
    }

    public function test_validation(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/305048781/contact', ['position' => 'Direktor'])
            ->assertStatus(422)->assertJsonValidationErrors('full_name');
        $this->putJson('/api/uzbekistan-export/exporters/305048781/contact', ['full_name' => 'A', 'phone' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->putJson('/api/uzbekistan-export/exporters/999999999/contact', ['full_name' => 'A'])
            ->assertNotFound();

        $this->assertSame(0, UzbekistanExporterContact::count());
    }

    public function test_saving_declarant_writes_it_to_all_rows_of_the_firm(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/305048781/declarant', [
            'full_name' => 'Yuldashev D.A.',
            'phone' => '90 123 45 67',
            'email' => 'Broker@Mail.UZ',
            'company' => 'LLC "DM BROKER SERVIS"',
        ])->assertOk()->assertJsonPath('updated_rows', 5);

        $this->assertSame(5, UzbekistanExport::where('declarant_name', 'Yuldashev D.A.')->count());
        $this->assertSame(2, UzbekistanExport::whereNull('declarant_name')->count());

        $row = UzbekistanExport::where('exporter_inn', '305048781')->first();
        $this->assertSame('+998901234567', $row->declarant_phone);
        $this->assertSame('broker@mail.uz', $row->declarant_email);
        $this->assertSame('LLC "DM BROKER SERVIS"', $row->declarant_company);
        // xodim ustunlariga tegilmaydi
        $this->assertNull($row->responsible_person_name);

        // oynada xodim va deklarant alohida keladi
        $firm = $this->getJson('/api/uzbekistan-export/exporters?q=305048781')->json('data.0');
        $this->assertNull($firm['contact']);
        $this->assertSame('Yuldashev D.A.', $firm['declarant']['full_name']);
        $this->assertSame('broker@mail.uz', $firm['declarant']['email']);

        // jadval ustunlarida ham, filtrda ham bor
        $this->assertContains('declarant_company', array_column($this->getJson('/api/uzbekistan-export/columns')->json('columns'), 'name'));
        $this->getJson('/api/uzbekistan-export?'.http_build_query(['filters' => ['declarant_name' => ['values' => ['Yuldashev D.A.']]]]))
            ->assertJsonPath('data.total', 5);

        // "Firmalar" oynasida ham ko'rinadi
        $firms = $this->getJson('/api/uzbekistan-export/firms')->json('data.data');
        $this->assertSame([[
            'full_name' => 'Yuldashev D.A.', 'phone' => '+998901234567', 'email' => 'broker@mail.uz',
            'company' => 'LLC "DM BROKER SERVIS"', 'rows' => 5,
        ]], $firms[0]['declarants']);
        $this->assertSame([], $firms[1]['declarants']);

        // tahrirlash — bitta yozuv qoladi
        $this->putJson('/api/uzbekistan-export/exporters/305048781/declarant', ['full_name' => 'Mukhtorov J.'])->assertOk();
        $this->assertSame(1, UzbekistanExporterDeclarant::count());
        $this->assertSame(5, UzbekistanExport::where('declarant_name', 'Mukhtorov J.')->whereNull('declarant_email')->count());
    }

    public function test_declarant_is_applied_to_newly_uploaded_files_and_removed_on_delete(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/306803919/contact', ['full_name' => 'Valiyev Bobur'])->assertOk();
        $this->putJson('/api/uzbekistan-export/exporters/306803919/declarant', ['full_name' => 'Yuldashev D.A.', 'company' => 'BROKER LLC'])->assertOk();

        $this->runImport([
            $this->row('306803919', 'LLC INTERNET TECHNOLOGY', '8'),
            $this->row('309590756', 'LLC DICLE TEKS', '9'),
        ]);

        $row = UzbekistanExport::where('hs_code', '8')->first();
        $this->assertSame('Valiyev Bobur', $row->responsible_person_name);
        $this->assertSame('Yuldashev D.A.', $row->declarant_name);
        $this->assertSame('BROKER LLC', $row->declarant_company);
        $this->assertNull(UzbekistanExport::where('hs_code', '9')->value('declarant_name'));

        // deklarant o'chiriladi, xodim qoladi
        $this->deleteJson('/api/uzbekistan-export/exporters/306803919/declarant')
            ->assertOk()
            ->assertJsonPath('updated_rows', 3);
        $this->assertSame(0, UzbekistanExporterDeclarant::count());
        $this->assertSame(9, UzbekistanExport::whereNull('declarant_name')->whereNull('declarant_company')->count());
        $this->assertSame(3, UzbekistanExport::where('responsible_person_name', 'Valiyev Bobur')->count());
    }

    public function test_declarant_validation(): void
    {
        $this->putJson('/api/uzbekistan-export/exporters/305048781/declarant', ['company' => 'BROKER LLC'])
            ->assertStatus(422)->assertJsonValidationErrors('full_name');
        $this->putJson('/api/uzbekistan-export/exporters/305048781/declarant', ['full_name' => 'A', 'phone' => 'abc'])
            ->assertStatus(422)->assertJsonValidationErrors('phone');
        $this->putJson('/api/uzbekistan-export/exporters/305048781/declarant', ['full_name' => 'A', 'email' => 'not-an-email'])
            ->assertStatus(422)->assertJsonValidationErrors('email');
        $this->putJson('/api/uzbekistan-export/exporters/999999999/declarant', ['full_name' => 'A'])
            ->assertNotFound();

        $this->assertSame(0, UzbekistanExporterDeclarant::count());
    }

    public function test_import_declarant_columns_are_labelled_declarant(): void
    {
        $labels = array_column($this->getJson('/api/uzbekistan-import/columns')->json('columns'), 'label', 'name');

        $this->assertSame('Declarant company', $labels['declaring_unit']);
        $this->assertSame('Declarant name', $labels['declarant_name']);
        $this->assertSame('Declarant phone', $labels['applicant_phone']);
        $this->assertSame('Declarant email', $labels['applicant_email']);
        $this->assertEmpty(array_filter($labels, fn ($label) => stripos($label, 'applicant') !== false));
    }

    private function runImport(array $rows): void
    {
        $path = tempnam(sys_get_temp_dir(), 'uzbexp').'.xlsx';
        $headers = array_fill(0, 29, 'col');
        $headers[3] = 'Exporter ID';
        $headers[4] = 'HS code';
        $headers[13] = 'Destination country';

        $writer = new Writer();
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(['[UZBEKISTAN] country (export) trade country download data']));
        $writer->addRow(Row::fromValues($headers));
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        $relative = 'uzbekistan/imports/'.uniqid().'.xlsx';
        Storage::disk('local')->put($relative, file_get_contents($path));
        $task = UzbekistanImportTask::create(['type' => 'import', 'dataset' => 'export', 'file_path' => $relative]);

        (new ImportUzbekistanFile($task))->handle();
        $this->assertSame('completed', $task->fresh()->status);
    }

    private function row(string $inn, string $name, string $hs): array
    {
        $row = array_fill(0, 29, '');
        $row[0] = '2026-08-31';
        $row[1] = 'BUYER LLC';
        $row[2] = $name;
        $row[3] = $inn;
        $row[4] = $hs;
        $row[5] = 'PRODUCT';
        $row[8] = 10;
        $row[13] = 'RUSSIA';

        return $row;
    }
}
