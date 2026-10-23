<?php

namespace App\Exports;

use App\Support\DashboardStats;
use App\Support\ShortAddress;
use App\Support\TradeDataset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use PDO;

/**
 * O'zbekiston savdo ma'lumotlarini Zanjeer saytiga yuklash formatida (uz_import_*.xlsx / uz_export_*.xlsx) yozadi.
 *
 * Ustunlar uz-trade-scraper loyihasidagi bilan bir xil. Standart holatda har bir tovar qatori alohida
 * yoziladi ("Товарных позиций" = 1). $merge = true bo'lsa — o'sha loyihadagidek birlashtiriladi:
 *   1 qator = bitta O'zbek kompaniyasi (INN) + bitta mashina + bitta sana   (import)
 *   1 qator = bitta O'zbek eksportyori (INN) + bitta xaridor + bitta sana   (eksport — faylda mashina va ГТД yo'q)
 * Tonnaj va narx qo'shiladi, "Товарных позиций" — birlashgan tovar qatorlari soni,
 * tovarlar / ТН ВЭД / ГТД va boshqa ro'yxat ustunlari takrorlarsiz "; " bilan birlashadi.
 *
 * Qatorlar baza tomonida guruh kaliti bo'yicha saralanib, oqim (stream) bilan o'qiladi va
 * ketma-ket kelgan bir guruh qatorlari PHP'da birlashtiriladi — xotira hajmga bog'liq emas.
 */
class ZanjeerExport
{
    private const PROGRESS_EVERY = 2000;
    private const ROWS_PER_SHEET = 1000000;
    private const EXPORT_CONNECTION = 'uzbekistan_export_unbuffered';

    /** Excel katagi chegarasi (32 767 belgi) */
    private const MAX_LIST_LENGTH = 32000;

    /** Import: maydon => sarlavha (uz_import_*.xlsx) */
    public const IMPORT_COLUMNS = [
        'importer_name' => 'Название импортера',
        'importer_contact_person' => 'Контактное лицо (импортер)',
        'importer_contact_phone' => 'Телефон контактного лица',
        'importer_company_std' => 'Company Name',
        'importer_phone' => 'Телефон (импортер)',
        'unloading_place' => 'Место выгрузки',
        'importer_email' => 'Email',
        'importer_inn' => 'ИНН импортера',
        'importer_country' => 'Country',
        'exporter_name' => 'Название экспортера',
        'exporter_inn' => 'ИНН экспортера',
        'exporter_contact_person' => 'Контактное лицо (экспортер)',
        'exporter_phone' => 'Телефон (экспортер)',
        'loading_place' => 'Место погрузки',
        'delivery_terms' => 'Условия поставки',
        'vehicle_number' => 'Номер транспорта',
        'transport_type' => 'Вид перевозки',
        'tonnage' => 'Тоннаж (т)',
        'date' => 'Дата',
        'product' => 'Товар',
        'origin_country' => 'Страна происхождения',
        'value_usd' => 'Стоимость (USD)',
        'items_count' => 'Товарных позиций',
        'hs_code' => 'ТН ВЭД',
        'declaration_number' => '№ ГТД',
    ];

    /** Eksport: O'zbek eksportyori chapda, xorijiy xaridor keyin (uz_export_*.xlsx) */
    public const EXPORT_COLUMNS = [
        'exporter_name' => 'Название экспортера',
        'exporter_contact_person' => 'Контактное лицо (экспортер)',
        'exporter_contact_phone' => 'Телефон контактного лица',
        'exporter_company_std' => 'Company Name',
        'exporter_phone' => 'Телефон (экспортер)',
        'loading_place' => 'Место погрузки',
        'exporter_email' => 'Email',
        'exporter_inn' => 'ИНН экспортера',
        'exporter_country' => 'Country',
        'importer_name' => 'Название импортера',
        'importer_phone' => 'Телефон (импортер)',
        'unloading_place' => 'Место выгрузки',
        'importer_country' => 'Страна импортера',
        'delivery_terms' => 'Условия поставки',
        'vehicle_number' => 'Номер транспорта',
        'transport_type' => 'Вид перевозки',
        'tonnage' => 'Тоннаж (т)',
        'date' => 'Дата',
        'product' => 'Товар',
        'destination_country' => 'Страна назначения',
        'value_usd' => 'Стоимость (USD)',
        'items_count' => 'Товарных позиций',
        'hs_code' => 'ТН ВЭД',
        'declaration_number' => '№ ГТД',
    ];

    /** Bir guruhda bir nechta qiymat bo'lsa — ro'yxat bo'lib birlashadi */
    private const LIST_COLUMNS = [
        'importer_name', 'exporter_name', 'loading_place', 'unloading_place', 'delivery_terms', 'product',
        'origin_country', 'destination_country', 'importer_country', 'hs_code', 'declaration_number', 'vehicle_number',
    ];

    /** Qo'shiladigan ustunlar */
    private const SUM_COLUMNS = ['tonnage' => 3, 'value_usd' => 2];

    /** Keng ustunlar (nomlar, manzillar, ro'yxatlar) */
    private const WIDE_COLUMNS = [
        'importer_name', 'exporter_name', 'importer_company_std', 'exporter_company_std',
        'unloading_place', 'loading_place', 'importer_contact_person', 'exporter_contact_person', 'product',
    ];

    /**
     * @param  array  $params  jadval filtrlari (ExcelFilter formati) — "barcha ma'lumotlar" uchun
     * @param  array<int, array{0: string, 1: string}>|null  $windows  "bugun yuklangan" — updated_at oraliqlari (fayl yuklangan vaqtlar)
     */
    private ShortAddress $addresses;

    public function __construct(
        private TradeDataset $dataset,
        private array $params = [],
        private ?array $windows = null,
        private bool $merge = false,
    ) {
        $this->addresses = new ShortAddress();
    }

    public static function columns(TradeDataset $dataset): array
    {
        return $dataset === TradeDataset::Export ? self::EXPORT_COLUMNS : self::IMPORT_COLUMNS;
    }

    public static function sheetName(TradeDataset $dataset): string
    {
        return $dataset === TradeDataset::Export ? 'Экспорт' : 'Импорт';
    }

    /** Fayl nomi Zanjeer kutganidek: uz_import_2026-10-23_101530.xlsx (Toshkent vaqti) */
    public static function fileName(TradeDataset $dataset): string
    {
        return 'uz_'.$dataset->value.'_'.now(DashboardStats::TIMEZONE)->format('Y-m-d_His').'.xlsx';
    }

    /** Manba (tovar) qatorlari soni — progress uchun */
    public function count(): int
    {
        return $this->baseQuery($this->dataset->model()::query())->count();
    }

    /**
     * @param  callable(int):void|null  $onProgress  o'qilgan manba qatorlari soni bilan chaqiriladi
     * @return int yozilgan (birlashtirilgan) qatorlar soni
     */
    public function store(string $path, ?callable $onProgress = null): int
    {
        $columns = self::columns($this->dataset);
        $writer = new Writer();
        $writer->openToFile($path);

        $written = 0;
        $onSheet = 0;
        $read = 0;
        $part = 1;
        $group = null;
        $groupKey = null;

        $flush = function () use (&$group, &$written, &$onSheet, &$part, $writer, $columns) {
            if ($group === null) {
                return;
            }
            if ($onSheet >= self::ROWS_PER_SHEET) {
                $this->finishSheet($writer, $columns, $onSheet);
                $writer->addNewSheetAndMakeItCurrent();
                $this->startSheet($writer, $columns, ++$part);
                $onSheet = 0;
            }
            $writer->addRow(Row::fromValues($this->finalize($group, $columns)));
            $written++;
            $onSheet++;
            $group = null;
        };

        try {
            $this->startSheet($writer, $columns, $part);

            foreach ($this->cursorQuery()->toBase()->cursor() as $item) {
                $read++;
                $mapped = $this->dataset === TradeDataset::Export ? $this->mapExport($item) : $this->mapImport($item);

                if ((string) $item->group_key !== $groupKey) {
                    $flush();
                    $groupKey = (string) $item->group_key;
                    $group = $this->newGroup($mapped);
                } else {
                    $this->merge($group, $mapped);
                }

                if ($onProgress && $read % self::PROGRESS_EVERY === 0) {
                    $onProgress($read);
                }
            }
            $flush();
            $this->finishSheet($writer, $columns, $onSheet);
        } finally {
            $writer->close();
            DB::purge(self::EXPORT_CONNECTION);
        }

        $onProgress && $onProgress($read);

        return $written;
    }

    // ================= Maydonlar =================

    /** Import qatori: O'zbek kompaniyasi — importyor, xorijiy — eksportyor */
    private function mapImport(object $r): array
    {
        $vehicle = $r->vehicle_number
            ? $this->join('/', $r->vehicle_number, $r->trailer_number)
            : $r->border_vehicle_number;

        return [
            'importer_name' => $r->importer_name,
            'importer_contact_person' => $r->responsible_person_name,   // bojxona yozuvidagi vakil
            'importer_contact_phone' => $r->responsible_person_phone,
            'importer_company_std' => null,                             // Zanjeer kompaniyalar bazasida
            'importer_phone' => $r->importer_phone,
            'unloading_place' => $this->addresses->short($r->importer_address, 'UZBEKISTAN'),       // "UZBEKISTAN, TASHKENT, MIRABAD"
            'importer_email' => null,
            'importer_inn' => $r->importer_inn,
            'importer_country' => null,
            'exporter_name' => $r->exporter_standard_name ?: $r->exporter_local_name,
            'exporter_inn' => $r->exporter_code,
            'exporter_contact_person' => null,
            'exporter_phone' => $r->exporter_phone,
            'loading_place' => $this->addresses->short($r->exporter_address, $r->exporter_country), // "RUSSIA, MOSCOW REGION, KHIMKI"
            'delivery_terms' => $this->join(', ', $r->trade_mode, $r->trade_mode_code),
            'vehicle_number' => $vehicle,
            'transport_type' => $r->transport_type,
            'tonnage' => $this->tons($r->gross_weight_kg ?? $r->net_weight_kg),
            'date' => $this->date($r->date ?? $r->registration_date),
            'product' => $r->product_description,
            'origin_country' => $r->origin_country,
            'value_usd' => $this->number($r->customs_amount_usd ?? $r->statistical_amount_usd),
            'hs_code' => $r->hs_code,
            'declaration_number' => $r->declaration_number,
        ];
    }

    /** Eksport qatori: O'zbek kompaniyasi — eksportyor, xorijiy — xaridor */
    private function mapExport(object $r): array
    {
        return [
            'exporter_name' => $r->exporter_name,
            'exporter_contact_person' => $r->responsible_person_name,   // "Xodim qo'shish" orqali kiritilgan
            'exporter_contact_phone' => $r->responsible_person_phone,
            'exporter_company_std' => null,
            'exporter_phone' => null,
            'loading_place' => 'UZBEKISTAN',
            'exporter_email' => null,
            'exporter_inn' => $r->exporter_inn,
            'exporter_country' => null,
            'importer_name' => $r->importer_name,
            'importer_phone' => null,
            'unloading_place' => $this->join(', ', $r->destination_country, $r->destination),
            'importer_country' => $r->destination_country,
            'delivery_terms' => $this->join(', ', $r->transaction_method, $r->delivery_condition_code),
            'vehicle_number' => null,                                   // eksport faylida yo'q
            'transport_type' => $r->transportation_method,
            'tonnage' => $this->tons($r->net_weight_kg),
            'date' => $this->date($r->date),
            'product' => $r->product_description,
            'destination_country' => $r->destination_country,
            'value_usd' => $this->number($r->customs_value_usd ?? $r->invoice_amount_usd),
            'hs_code' => $r->hs_code,
            'declaration_number' => null,                               // eksport faylida yo'q
        ];
    }

    // ================= Birlashtirish =================

    private function newGroup(array $mapped): array
    {
        $group = ['items_count' => 1, 'lists' => [], 'values' => []];
        foreach ($mapped as $column => $value) {
            $this->put($group, $column, $value);
        }

        return $group;
    }

    private function merge(array &$group, array $mapped): void
    {
        $group['items_count']++;
        foreach ($mapped as $column => $value) {
            $this->put($group, $column, $value);
        }
    }

    private function put(array &$group, string $column, mixed $value): void
    {
        if (isset(self::SUM_COLUMNS[$column])) {
            if ($value !== null) {
                $group['values'][$column] = ($group['values'][$column] ?? 0) + $value;
            }

            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if (in_array($column, self::LIST_COLUMNS, true)) {
            $group['lists'][$column][trim((string) $value)] = true;

            return;
        }

        // boshqa ustunlar — guruhdagi birinchi bo'sh bo'lmagan qiymat
        $group['values'][$column] ??= $value;
    }

    private function finalize(array $group, array $columns): array
    {
        $row = [];
        foreach (array_keys($columns) as $column) {
            if ($column === 'items_count') {
                $row[] = $group['items_count'];
            } elseif (isset(self::SUM_COLUMNS[$column])) {
                $row[] = isset($group['values'][$column]) ? round($group['values'][$column], self::SUM_COLUMNS[$column]) : null;
            } elseif (isset($group['lists'][$column])) {
                $items = array_keys($group['lists'][$column]);
                sort($items, SORT_STRING);
                $row[] = mb_substr(implode('; ', $items), 0, self::MAX_LIST_LENGTH);
            } else {
                $row[] = $group['values'][$column] ?? null;
            }
        }

        return $row;
    }

    // ================= So'rov =================

    /**
     * Guruh kaliti bo'yicha saralangan so'rov: bir guruh qatorlari ketma-ket keladi.
     * Sana kalitning birinchi qismi — natija yangi sanadan eskisiga tartiblanadi.
     */
    private function cursorQuery(): Builder
    {
        $default = config('database.default');
        $config = config("database.connections.$default");
        $model = $this->dataset->model();
        $query = $model::query();

        if (in_array($config['driver'] ?? null, ['mysql', 'mariadb'], true)) {
            $config['options'] = array_replace($config['options'] ?? [], [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
            config(['database.connections.'.self::EXPORT_CONNECTION => $config]);
            $query = $model::on(self::EXPORT_CONNECTION);
        }

        $query = $this->baseQuery($query);
        $grammar = $query->getQuery()->getGrammar();
        $concat = fn (string ...$parts) => ($config['driver'] ?? null) === 'sqlite'
            ? implode(" || '|' || ", $parts)
            : 'CONCAT_WS(\'|\', '.implode(', ', $parts).')';
        $w = fn (string $column) => "COALESCE({$grammar->wrap($column)}, '')";

        if ($this->dataset === TradeDataset::Export) {
            $date = $grammar->wrap('date');
            $company = "COALESCE(NULLIF({$grammar->wrap('exporter_inn')}, ''), {$grammar->wrap('exporter_name')}, '')";
            $partner = $w('importer_name');
        } else {
            $date = "COALESCE({$grammar->wrap('date')}, {$grammar->wrap('registration_date')})";
            $company = "COALESCE(NULLIF({$grammar->wrap('importer_inn')}, ''), {$grammar->wrap('importer_name')}, '')";
            // mashina raqami; bo'lmasa — chegaradagi transport, u ham bo'lmasa — yuk (ГТД): post + raqam
            $partner = "COALESCE(NULLIF({$grammar->wrap('vehicle_number')}, ''), NULLIF({$grammar->wrap('border_vehicle_number')}, ''), "
                .$concat($w('customs_code'), $w('declaration_number')).')';
        }

        return $query
            ->select('*')
            // birlashtirilmasa — har bir qator o'z guruhi (id)
            ->selectRaw(($this->merge ? $concat("COALESCE({$date}, '')", $company, $partner) : $grammar->wrap('id')).' as group_key')
            ->orderByRaw("{$date} desc")
            ->orderByRaw($company)
            ->orderByRaw($partner)
            ->orderBy('id');
    }

    /** "Barcha ma'lumotlar" — jadval filtrlari; "bugun yuklangan" — bugungi fayllar yozgan qatorlar */
    private function baseQuery(Builder $query): Builder
    {
        if ($this->windows !== null) {
            return $query->where(function (Builder $q) {
                foreach ($this->windows as [$from, $to]) {
                    $q->orWhereBetween('updated_at', [$from, $to]);
                }
            });
        }

        return $query->filter($this->params);
    }

    // ================= Excel varag'i =================

    private function startSheet(Writer $writer, array $columns, int $part): void
    {
        $title = self::sheetName($this->dataset);
        $sheet = $writer->getCurrentSheet();
        $sheet->setName(mb_substr($part > 1 ? "{$title} ({$part})" : $title, 0, 31));
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));

        foreach (array_keys($columns) as $index => $column) {
            $width = match (true) {
                in_array($column, self::WIDE_COLUMNS, true) => 42,
                isset(self::SUM_COLUMNS[$column]) || $column === 'items_count' => 14,
                default => 20,
            };
            $sheet->setColumnWidth($width, $index + 1);
        }

        $style = (new Style())->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('0F766E')->setShouldWrapText();
        $writer->addRow(Row::fromValues(array_values($columns), $style));
    }

    private function finishSheet(Writer $writer, array $columns, int $rows): void
    {
        $writer->getCurrentSheet()->setAutoFilter(new AutoFilter(0, 1, count($columns) - 1, $rows + 1));
    }

    // ================= Yordamchilar =================

    private function join(string $separator, ?string ...$parts): ?string
    {
        $parts = array_filter(array_map(fn ($p) => $p === null ? '' : trim($p), $parts), fn ($p) => $p !== '');

        return $parts ? implode($separator, $parts) : null;
    }

    /** Har bir tovar avval yaxlitlanib, keyin qo'shiladi — Zanjeer'dagi hisob bilan bir xil */
    private function tons($kg): ?float
    {
        return is_numeric($kg) ? round($kg / 1000, 3) : null;
    }

    private function number($value): ?float
    {
        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function date($value): ?string
    {
        return $value ? substr((string) $value, 0, 10) : null;
    }
}
