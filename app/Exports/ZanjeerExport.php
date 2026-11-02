<?php

namespace App\Exports;

use App\Support\DashboardStats;
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
 * O'zbekiston savdo ma'lumotlarini Zanjeer saytiga yuklash shablonida yozadi
 * ("Шаблон импорта - Импортеры.xlsx" / "Шаблон импорта - Экспортеры.xlsx" — 63 ta ustun, ikkalasida bir xil).
 *
 * Import qatorlari shablonga deyarli to'g'ridan-to'g'ri tushadi (maydon nomlari — bazadagi ustunlar).
 * Eksport faylida ustunlar kam: O'zbek firma — "Exporter's standard name" (STIR'i "Importer code"ga ham yoziladi),
 * xorijiy xaridor — "Importer name", boradigan davlat — "Trading country", shahar — "Place of delivery".
 *
 * Standart holatda har bir tovar qatori alohida yoziladi. $merge = true bo'lsa — bitta yuk bitta qatorga birlashtiriladi:
 *   importyor + eksportyor + sana + mashina raqami bir xil qatorlar qo'shiladi;
 *   mashina raqami bo'lmasa — mashina o'rniga davlat (importda kelib chiqish, eksportda boradigan davlat).
 * Birlashtirilganda: vazn va summalar qo'shiladi, miqdor o'lchov birligi bo'yicha qo'shiladi,
 * deklaratsiya jami summalari (Total ...) har bir ГТД uchun bir marta qo'shiladi, tovar nomi umumiylashtiriladi
 * (generalProducts), qolgan ustunlar takrorlarsiz "; " bilan birlashadi.
 * Bitta transport sig'imidan og'ir qator qismlarga bo'linadi (splitByCapacity): avto — 24 t, vagon — 70 t.
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

    /** Shablon ustunlari: maydon => sarlavha. Maydon nomlari — uzbekistan_imports ustunlari */
    public const COLUMNS = [
        'date' => 'Date',
        'declaration_date' => 'Declaration Date',
        'regime_type' => 'Import & Export Type',
        'regime_code' => 'Import and export type code',
        'item_no' => 'Item No.',
        'importer_name' => 'Importer name',
        'importer_address' => "Importer's address",
        'importer_region' => 'Importer region',
        'importer_district' => 'Importer district',
        'importer_village' => 'Importer village',
        'importer_inn' => 'Importer code',
        'importer_phone' => "Importer's phone number",
        'exporter_standard_name' => "Exporter's standard name",
        'exporter_address' => "Exporter's address",
        'exporter_country' => 'Exporter country',
        'exporter_region' => 'Exporter region',
        'exporter_city' => 'Exporter city',
        'exporter_street' => 'Exporter street',
        'exporter_phone' => "Exporter's phone number",
        'hs_code' => 'HS Code',
        'product_description' => 'Product description',
        'brand' => 'Brand',
        'quantity' => 'Quantity',
        'quantity_unit' => 'Unit of quantity',
        'net_weight_kg' => 'Net weight(kg)',
        'gross_weight_kg' => 'Gross weight(kg)',
        'invoice_amount_fc' => 'Invoice amount(foreign currency)',
        'customs_amount_usd' => 'Customs amount(USD)',
        'customs_amount_fc' => 'Customs amount(foreign currency)',
        'statistical_amount_usd' => 'Statistical amount(USD)',
        'total_invoice_amount_fc' => 'Total invoice amount(foreign currency)',
        'total_customs_amount_fc' => 'Total customs amount(foreign currency)',
        'total_tariffs' => 'Total tariffs',
        'contract_currency' => 'Contract currency',
        'usd_exchange_rate' => 'Exchange rate against the US dollar',
        'fc_exchange_rate' => 'Exchange rate for foreign currency',
        'trade_mode' => 'Mode of trade',
        'trade_mode_code' => 'Trade mode code',
        'contract_char_code' => 'Digital contract character code',
        'origin_country' => 'Country of Origin',
        'delivery_place' => 'Place of delivery',
        'shipment_country' => 'Country of shipment',
        'trading_country' => 'Trading country',
        'transport_country' => 'Transportation country',
        'transit_country' => 'Transit country',
        'transit_date' => 'Transit date',
        'declaring_unit' => 'Declarant company',
        'declaring_unit_code' => 'Declarant company code',
        'applicant_address' => 'Declarant address',
        'declarant_name' => 'Declarant name',
        'applicant_email' => 'Declarant email',
        'applicant_phone' => 'Declarant phone',
        'transport_type' => 'Commodity transportation type',
        'overseas_transport_type' => 'Overseas transportation mode',
        'vehicle_number' => 'Vehicle number',
        'trailer_number' => 'Trailer number',
        'border_vehicle_number' => 'Border transportation vehicle identification number',
        'customs_code' => 'Customs code',
        'border_customs' => 'Border customs',
        'customs_office' => 'Customs Office',
        'responsible_person_name' => 'Responsible person',
        'responsible_person_position' => 'Responsible person position',
        'responsible_person_phone' => 'Responsible person phone',
    ];

    /** Birlashtirilganda qo'shiladigan ustunlar => kasr xonalari; capacity bo'yicha bo'linganda ham nisbatda bo'linadi */
    private const SUM_COLUMNS = [
        'net_weight_kg' => 3,
        'gross_weight_kg' => 3,
        'invoice_amount_fc' => 2,
        'customs_amount_usd' => 2,
        'customs_amount_fc' => 2,
        'statistical_amount_usd' => 2,
        'total_tariffs' => 2,
    ];

    /** Deklaratsiya bo'yicha jami summalar — har bir tovar qatorida takrorlanadi, shuning uchun ГТД bo'yicha bir marta qo'shiladi */
    private const DECLARATION_TOTALS = ['total_invoice_amount_fc', 'total_customs_amount_fc'];

    private const DATE_COLUMNS = ['date', 'declaration_date', 'transit_date'];

    private const NUMBER_COLUMNS = ['quantity', 'usd_exchange_rate', 'fc_exchange_rate', ...self::DECLARATION_TOTALS];

    /** Keng ustunlar (nomlar, manzillar, tovar) */
    private const WIDE_COLUMNS = [
        'importer_name', 'importer_address', 'exporter_standard_name', 'exporter_address',
        'product_description', 'declaring_unit', 'applicant_address', 'customs_office',
    ];

    /**
     * @param  array  $params  jadval filtrlari (ExcelFilter formati) — "barcha ma'lumotlar" uchun
     * @param  array<int, array{0: string, 1: string}>|null  $windows  "bugun yuklangan" — updated_at oraliqlari (fayl yuklangan vaqtlar)
     */
    public function __construct(
        private TradeDataset $dataset,
        private array $params = [],
        private ?array $windows = null,
        private bool $merge = false,
        private ?string $transport = null,
    ) {
    }

    /** Varaq nomi shablondagidek */
    private const SHEET_NAME = 'Sheet';

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
        $columns = self::COLUMNS;
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
            // bitta transportga sig'maydigan yuk bir nechta qatorga bo'linadi
            foreach ($this->splitByCapacity($this->finalize($group)) as $values) {
                if ($onSheet >= self::ROWS_PER_SHEET) {
                    $this->finishSheet($writer, $columns, $onSheet);
                    $writer->addNewSheetAndMakeItCurrent();
                    $this->startSheet($writer, $columns, ++$part);
                    $onSheet = 0;
                }
                $writer->addRow(Row::fromValues(array_values($values)));
                $written++;
                $onSheet++;
            }
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
                    $this->add($group, $mapped);
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

    /** Import qatori: shablon ustunlari bazadagi bilan bir xil */
    private function mapImport(object $r): array
    {
        $row = [];
        foreach (array_keys(self::COLUMNS) as $column) {
            $row[$column] = $r->{$column} ?? null;
        }
        $row['date'] = $r->date ?? $r->registration_date;

        return $this->typed($row) + ['_declaration' => $this->join('|', $r->customs_code, $r->registration_date, $r->declaration_number)];
    }

    /**
     * Eksport qatori: O'zbek firma — eksportyor (STIR'i "Importer code"ga ham yoziladi), xorijiy xaridor — importyor.
     * Eksport faylida yo'q ustunlar bo'sh qoladi.
     */
    private function mapExport(object $r): array
    {
        $row = array_fill_keys(array_keys(self::COLUMNS), null);

        return $this->typed(array_replace($row, [
            'date' => $r->date,
            'regime_type' => $r->regime_type,
            'regime_code' => $r->regime_code,
            'importer_name' => $r->importer_name,
            'importer_inn' => $r->exporter_inn,
            'exporter_standard_name' => $r->exporter_name,
            'exporter_country' => 'UZBEKISTAN',
            'hs_code' => $r->hs_code,
            'product_description' => $r->product_description,
            'quantity' => $r->quantity,
            'quantity_unit' => $r->quantity_unit,
            'net_weight_kg' => $r->net_weight_kg,
            'invoice_amount_fc' => $r->invoice_amount_usd,
            'customs_amount_usd' => $r->customs_value_usd,
            'statistical_amount_usd' => $r->statistical_value_usd,
            'contract_currency' => $r->invoice_amount_usd !== null ? 'USD' : null,   // invoys summasi faylda USD'da
            'trade_mode' => $r->transaction_method,
            'trade_mode_code' => $r->delivery_condition_code,
            'delivery_place' => $r->destination ?: $r->destination_country,
            'trading_country' => $r->destination_country,
            'transit_country' => $r->transit_country,
            'declaring_unit' => $r->declarant_company,                               // "Xodim qo'shish" → "Deklarant"
            'declarant_name' => $r->declarant_name,
            'applicant_email' => $r->declarant_email,
            'applicant_phone' => $r->declarant_phone,
            'transport_type' => $r->transportation_method,
            'responsible_person_name' => $r->responsible_person_name,                // "Xodim qo'shish" → "Xodim"
            'responsible_person_position' => $r->responsible_person_position,
            'responsible_person_phone' => $r->responsible_person_phone,
        ])) + ['_declaration' => null];
    }

    /** Sanalar — "2026-08-31", sonlar — son, matn — bo'sh joylarsiz (bo'sh matn — null) */
    private function typed(array $row): array
    {
        foreach ($row as $column => $value) {
            if ($value === null) {
                continue;
            }
            $row[$column] = match (true) {
                in_array($column, self::DATE_COLUMNS, true) => substr((string) $value, 0, 10),
                isset(self::SUM_COLUMNS[$column]), in_array($column, self::NUMBER_COLUMNS, true) => is_numeric($value) ? (float) $value : null,
                default => trim((string) $value) === '' ? null : trim((string) $value),
            };
        }

        return $row;
    }

    // ================= Birlashtirish =================

    private function newGroup(array $mapped): array
    {
        $group = ['items_count' => 0, 'lists' => [], 'sums' => [], 'quantities' => [], 'totals' => [], 'products' => []];
        $this->add($group, $mapped);

        return $group;
    }

    private function add(array &$group, array $mapped): void
    {
        $group['items_count']++;
        $declaration = $mapped['_declaration'] ?? null;
        unset($mapped['_declaration']);

        foreach ($mapped as $column => $value) {
            if ($column === 'product_description' && $this->merge) {
                $this->putProduct($group, $value, $mapped['hs_code'] ?? null);
                $this->put($group, $column, $value);
            } elseif (isset(self::SUM_COLUMNS[$column])) {
                if ($value !== null) {
                    $group['sums'][$column] = ($group['sums'][$column] ?? 0) + $value;
                }
            } elseif ($column === 'quantity') {
                // miqdor o'lchov birligi bo'yicha: 100 KG + 20 PCS — alohida
                if ($value !== null) {
                    $unit = (string) ($mapped['quantity_unit'] ?? '');
                    $group['quantities'][$unit] = ($group['quantities'][$unit] ?? 0) + $value;
                }
            } elseif (in_array($column, self::DECLARATION_TOTALS, true)) {
                // har bir ГТД uchun bir marta (deklaratsiya raqami bo'lmasa — har bir qator)
                if ($value !== null) {
                    $group['totals'][$column][$declaration ?? 'row'.$group['items_count']] = $value;
                }
            } else {
                $this->put($group, $column, $value);
            }
        }
    }

    /** Takrorlanmas qiymatlar (birinchi uchragan tartibda), qiymat turi saqlanadi */
    private function put(array &$group, string $column, mixed $value): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $group['lists'][$column][(string) $value] ??= $value;
    }

    /**
     * Tovar tavsifi ТН ВЭД pozitsiyasi (4 raqam) bo'yicha yig'iladi: har bir pozitsiya uchun
     * tavsiflarning umumiy boshlang'ich bo'laklari (":" bilan ajratilgan) saqlanadi — xotira tovarlar soniga bog'liq emas.
     */
    private function putProduct(array &$group, ?string $description, ?string $hsCode): void
    {
        $segments = $this->segments($description);
        if (! $segments) {
            return;
        }

        $heading = substr(preg_replace('/\D/', '', (string) $hsCode), 0, 4) ?: implode(':', array_slice($segments, 0, 1));

        if (! isset($group['products'][$heading])) {
            $group['products'][$heading] = ['common' => $segments, 'count' => 1, 'first' => $segments];

            return;
        }

        $product = &$group['products'][$heading];
        $product['count']++;
        $common = [];
        foreach ($product['common'] as $i => $segment) {
            if (($segments[$i] ?? null) !== $segment) {
                break;
            }
            $common[] = $segment;
        }
        $product['common'] = $common;
    }

    /** "APPLES, FRESH:  OTHER:RED APPLES - WEIGHT: 20 T" => ["APPLES, FRESH", "OTHER", "RED APPLES - WEIGHT", "20 T"] */
    private function segments(?string $description): array
    {
        $description = trim(preg_replace('/\s+/u', ' ', (string) $description));
        if ($description === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(':', $description)), fn ($s) => $s !== ''));
    }

    /** Nom sifatida yolg'iz ma'nosiz bo'laklar ("OTHER", "PARTS" ...) */
    private const GENERIC_SEGMENT = '/^(OTHERS?|OTHER ARTICLES|PARTS|ПРОЧИЕ|ПРОЧИЕ ТОВАРЫ|ЧАСТИ|-)$/u';

    /**
     * Yukdagi tovarlarning umumiy nomlari: har bir ТН ВЭД pozitsiyasi uchun bitta nom.
     *   - pozitsiyada bir nechta tovar bo'lsa — tavsiflarning umumiy qismi ("qizil olma", "yashil olma" => "olma");
     *   - pozitsiyada bitta tovar bo'lsa — uning bosh nomi (tavsifning birinchi bo'lagi).
     * Oxiridagi "OTHER" kabi ma'nosiz bo'laklar tashlanadi; nom shunday bo'laklardan boshlansa, keyingisi qo'shiladi.
     */
    private function generalProducts(array $products): ?string
    {
        $names = [];
        foreach ($products as $product) {
            $parts = $product['count'] > 1 ? $product['common'] : [];
            while ($parts && preg_match(self::GENERIC_SEGMENT, end($parts))) {
                array_pop($parts);
            }
            if (! $parts) {
                // bosh nom: birinchi ma'noli bo'lakkacha ("PARTS: PLOW SPARE PARTS" — ikkalasi)
                foreach ($product['first'] as $segment) {
                    $parts[] = $segment;
                    if (! preg_match(self::GENERIC_SEGMENT, $segment)) {
                        break;
                    }
                }
            }
            $names[implode(': ', $parts)] = true;
        }

        if (! $names) {
            return null;
        }

        $names = array_keys($names);
        sort($names, SORT_STRING);

        return mb_substr(implode('; ', $names), 0, self::MAX_LIST_LENGTH);
    }

    /** @return array<string, mixed> maydon => qiymat */
    private function finalize(array $group): array
    {
        $row = [];
        foreach (array_keys(self::COLUMNS) as $column) {
            $list = $group['lists'][$column] ?? [];

            $row[$column] = match (true) {
                isset(self::SUM_COLUMNS[$column]) => isset($group['sums'][$column]) ? round($group['sums'][$column], self::SUM_COLUMNS[$column]) : null,
                in_array($column, self::DECLARATION_TOTALS, true) => isset($group['totals'][$column]) ? round(array_sum($group['totals'][$column]), 2) : null,
                $column === 'quantity' => $this->quantity($group['quantities']),
                // bitta tovar — to'liq tavsif; bir nechta — umumiy nom
                $column === 'product_description' && $this->merge && $group['items_count'] > 1 => $this->generalProducts($group['products']),
                count($list) === 1 => reset($list),
                count($list) > 1 => mb_substr(implode('; ', $this->sorted(array_keys($list))), 0, self::MAX_LIST_LENGTH),
                default => null,
            };
        }

        return $row;
    }

    /** Bitta o'lchov birligi — son; bir nechta — "1500; 20" ("Unit of quantity" — "KG; PCS", tartibi bir xil) */
    private function quantity(array $quantities): float|string|null
    {
        if (! $quantities) {
            return null;
        }
        if (count($quantities) === 1) {
            return round(reset($quantities), 3);
        }
        ksort($quantities, SORT_STRING);

        return implode('; ', array_map(fn ($q) => (string) round($q, 3), $quantities));
    }

    /** Ro'yxat tartibi: o'lchov birligi miqdor bilan bir xil tartibda bo'lishi uchun — alifbo bo'yicha */
    private function sorted(array $items): array
    {
        sort($items, SORT_STRING);

        return $items;
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

        // Bitta yuk: importyor + eksportyor + sana + mashina raqami (mashina bo'lmasa — davlat)
        if ($this->dataset === TradeDataset::Export) {
            $date = $grammar->wrap('date');
            $company = "COALESCE(NULLIF({$grammar->wrap('exporter_inn')}, ''), {$grammar->wrap('exporter_name')}, '')";
            $partner = $w('importer_name');
            // eksport faylida mashina raqami yo'q — boradigan davlat
            $vehicle = $concat("'C'", $w('destination_country'));
        } else {
            $date = "COALESCE({$grammar->wrap('date')}, {$grammar->wrap('registration_date')})";
            $company = "COALESCE(NULLIF({$grammar->wrap('importer_inn')}, ''), {$grammar->wrap('importer_name')}, '')";
            $partner = "COALESCE(NULLIF({$grammar->wrap('exporter_standard_name')}, ''), {$grammar->wrap('exporter_local_name')}, '')";
            // mashina raqami (tyagach/tirkama, faylda qanday bo'lsa); bo'lmasa — kelib chiqish davlati
            $vehicle = "CASE WHEN COALESCE({$grammar->wrap('vehicle_number')}, '') <> '' THEN ".$concat("'V'", $w('vehicle_number'), $w('trailer_number'))
                .' ELSE '.$concat("'C'", $w('origin_country')).' END';
        }

        return $query
            ->select('*')
            // birlashtirilmasa — har bir qator o'z guruhi (id)
            ->selectRaw(($this->merge ? $concat("COALESCE({$date}, '')", $company, $partner, $vehicle) : $grammar->wrap('id')).' as group_key')
            ->orderByRaw("{$date} desc")
            ->orderByRaw($company)
            ->orderByRaw($partner)
            ->orderByRaw($vehicle)
            ->orderBy('id');
    }

    /** Bitta transport ko'taradigan eng ko'p yuk (tonna): avto — 24 t, vagon — 70 t */
    private const CAPACITY = [
        'auto' => 24,
        'rail' => 70,
    ];

    /**
     * Og'irligi bitta mashina/vagon sig'imidan katta qatorni sig'im bo'yicha qismlarga bo'ladi:
     * 125 t avto => 5 × 24 t + 1 × 5 t. Og'irlik — brutto (bo'lmasa netto). Vazn, summa va miqdor
     * og'irlik nisbatida bo'linadi (oxirgi qismga qoldiq — jami o'zgarmaydi), qolgan ustunlar har bir qismda
     * bir xil. Bitta ГТД bir nechta transportni qamrasa ham faylda faqat bitta raqam bo'lgani uchun shunday bo'linadi.
     * Avia va boshqa turlar bo'linmaydi.
     *
     * @return array<int, array> yoziladigan qatorlar
     */
    private function splitByCapacity(array $values): array
    {
        $weightColumn = is_numeric($values['gross_weight_kg']) ? 'gross_weight_kg' : 'net_weight_kg';
        $tonnage = is_numeric($values[$weightColumn]) ? $values[$weightColumn] / 1000 : null;
        $capacity = self::CAPACITY[$this->transportKind((string) $values['transport_type'])] ?? null;

        if ($capacity === null || $tonnage === null || round($tonnage, 6) <= $capacity) {
            return [$values];
        }

        $parts = (int) ceil(round($tonnage / $capacity, 6));
        $shares = [];
        for ($i = 1; $i < $parts; $i++) {
            $shares[] = $capacity / $tonnage;
        }

        $split = [...array_keys(self::SUM_COLUMNS), ...(is_float($values['quantity']) ? ['quantity'] : [])];
        $rows = array_fill(0, $parts, $values);
        foreach ($split as $column) {
            if (! is_numeric($values[$column])) {
                continue;
            }
            $decimals = self::SUM_COLUMNS[$column] ?? 3;
            $left = $values[$column];
            foreach ($rows as $i => &$row) {
                // oxirgi qismga qoldiq — yaxlitlash tufayli jami o'zgarmasin
                $part = $i < $parts - 1 ? round($values[$column] * $shares[$i], $decimals) : round($left, $decimals);
                $row[$column] = $part;
                $left -= $part;
            }
            unset($row);
        }

        return $rows;
    }

    /** "АВТО" / "ROAD" => auto, "ЖД" / "RAIL" => rail */
    private function transportKind(string $transport): ?string
    {
        $config = self::TRANSPORT[$this->dataset->value];

        foreach (['auto', 'rail'] as $kind) {
            if ($transport !== '' && str_starts_with(mb_strtoupper(trim($transport)), $config[$kind])) {
                return $kind;
            }
        }

        return null;
    }

    /** "Barcha ma'lumotlar" — jadval filtrlari; "bugun yuklangan" — bugungi fayllar yozgan qatorlar */
    private function baseQuery(Builder $query): Builder
    {
        $this->transportScope($query);

        if ($this->windows !== null) {
            return $query->where(function (Builder $q) {
                foreach ($this->windows as [$from, $to]) {
                    $q->orWhereBetween('updated_at', [$from, $to]);
                }
            });
        }

        return $query->filter($this->params);
    }

    /** Transport turlari: qaysi ustun va qiymat boshlanishi (importda "ЖД:" kabi yozuvlar ham uchraydi) */
    private const TRANSPORT = [
        'import' => ['column' => 'transport_type', 'auto' => 'АВТО', 'rail' => 'ЖД'],
        'export' => ['column' => 'transportation_method', 'auto' => 'ROAD', 'rail' => 'RAIL'],
    ];

    /** Faqat avto, faqat vagonlar (temir yo'l) yoki ikkalasi birga; null — cheklovsiz */
    private function transportScope(Builder $query): void
    {
        if ($this->transport === null) {
            return;
        }

        $config = self::TRANSPORT[$this->dataset->value];
        $kinds = $this->transport === 'both' ? ['auto', 'rail'] : [$this->transport];

        $query->where(function (Builder $q) use ($config, $kinds) {
            foreach ($kinds as $kind) {
                $q->orWhere($config['column'], 'like', $config[$kind].'%');
            }
        });
    }

    // ================= Excel varag'i =================

    private function startSheet(Writer $writer, array $columns, int $part): void
    {
        $sheet = $writer->getCurrentSheet();
        $sheet->setName(self::SHEET_NAME.$part);   // shablondagidek: Sheet1, Sheet2 ...
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));

        foreach (array_keys($columns) as $index => $column) {
            $width = match (true) {
                in_array($column, self::WIDE_COLUMNS, true) => 40,
                isset(self::SUM_COLUMNS[$column]) => 16,
                default => 20,
            };
            $sheet->setColumnWidth($width, $index + 1);
        }

        $writer->addRow(Row::fromValues(array_values($columns), (new Style())->setFontBold()));
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
}
