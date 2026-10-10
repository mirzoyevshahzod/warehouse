<?php

namespace App\Models;

use App\Support\ExcelFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UzbekistanImport extends Model
{
    protected $table = 'uzbekistan_imports';

    /**
     * Ustun => sarlavha (asl Excel fayldagi nomi; ajratib olingan ustunlar uchun shunga o'xshash nom).
     * Ustunlar tartibi — jadval va export tartibi.
     */
    public const LABELS = [
        'date' => 'Date',
        'registration_date' => 'Registration Date',
        'declaration_date' => 'Declaration Date',
        'regime_type' => 'Import & Export Type',
        'regime_code' => 'Import and export type code',
        'declaration_number' => 'Declaration number',
        'item_no' => 'Item No.',
        'importer_name' => 'Importer name',
        'importer_name_2' => 'Importer 2',
        'importer_address' => "Importer's address",
        'importer_region' => 'Importer region',
        'importer_district' => 'Importer district',
        'importer_village' => 'Importer village',
        'importer_inn' => 'Importer code',
        'importer_phone' => "Importer's phone number",
        'contract_holder' => 'Contract holder',
        'contract_holder_code' => 'Contract holder code',
        'contract_holder_address' => 'Address of the contract holder',
        'contract_holder_region' => 'Contract holder region',
        'contract_holder_district' => 'Contract holder district',
        'contract_holder_street' => 'Contract holder street',
        'exporter_standard_name' => "Exporter's standard name",
        'exporter_local_name' => 'Local name of the exporter',
        'exporter_address' => "Exporter's address",
        'exporter_country' => 'Exporter country',
        'exporter_region' => 'Exporter region',
        'exporter_city' => 'Exporter city',
        'exporter_street' => 'Exporter street',
        'exporter_code' => 'Exporter code',
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
        'contract_date' => 'Contract Date',
        'contract_no' => 'Contract No.',
        'contract_signing_country' => 'Contract signing country',
        'contract_char_code' => 'Digital contract character code',
        'origin_country' => 'Country of Origin',
        'delivery_place' => 'Place of delivery',
        'shipment_country' => 'Country of shipment',
        'trading_country' => 'Trading country',
        'transport_country' => 'Transportation country',
        'transit_country' => 'Transit country',
        'destination_country' => 'Destination country',
        'transit_date' => 'Transit date',
        'declaring_unit' => 'Declaring unit',
        'declaring_unit_code' => 'Declaring unit code',
        'applicant_address' => 'Address of the applicant',
        'applicant_region' => 'Applicant region',
        'applicant_district' => 'Applicant district',
        'applicant_street' => 'Applicant street',
        'declarant_name' => "Declarant's name",
        'applicant_code' => 'Applicant code',
        'applicant_email' => "Applicant's email",
        'applicant_phone' => "Applicant's phone number",
        'applicant_contract_no' => "Applicant's contract number",
        'applicant_city' => 'Applicant city',
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

    public const DATE_COLUMNS = [
        'date', 'registration_date', 'declaration_date', 'contract_date', 'transit_date',
    ];

    public const NUMERIC_COLUMNS = [
        'item_no', 'quantity', 'net_weight_kg', 'gross_weight_kg',
        'invoice_amount_fc', 'customs_amount_usd', 'customs_amount_fc', 'statistical_amount_usd',
        'total_invoice_amount_fc', 'total_customs_amount_fc', 'total_tariffs',
        'usd_exchange_rate', 'fc_exchange_rate',
    ];

    /** Asosiy filtrlar (frontend'da tepada alohida ko'rsatiladi) */
    public const MAIN_FILTERS = [
        'importer_name',
        'importer_inn',
        'exporter_standard_name',
        'transport_type',
        'origin_country',
        'trade_mode',
    ];

    /** Saralash berilmaganda */
    public const DEFAULT_SORT = ['-registration_date'];

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'registration_date' => 'date:Y-m-d',
        'declaration_date' => 'date:Y-m-d',
        'contract_date' => 'date:Y-m-d',
        'transit_date' => 'date:Y-m-d',
    ];

    public function getFillable()
    {
        return array_keys(self::LABELS);
    }

    /** @return array<string, string> ustun => text | number | date */
    public static function columnTypes(): array
    {
        $types = array_fill_keys(array_keys(self::LABELS), ExcelFilter::TEXT);

        foreach (self::DATE_COLUMNS as $column) {
            $types[$column] = ExcelFilter::DATE;
        }
        foreach (self::NUMERIC_COLUMNS as $column) {
            $types[$column] = ExcelFilter::NUMBER;
        }

        return $types;
    }

    public static function excelFilter(): ExcelFilter
    {
        return new ExcelFilter(self::columnTypes(), self::DEFAULT_SORT);
    }

    /**
     * Excel'dagidek filtrlar (qiymatlar ro'yxati, shartlar). Parametrlar formati — App\Support\ExcelFilter.
     *
     * @param  string|null  $except  shu ustun filtri hisobga olinmaydi (filtr variantlari uchun)
     */
    public function scopeFilter(Builder $query, array $params, ?string $except = null): Builder
    {
        return self::excelFilter()->apply($query, $params, $except);
    }

    public function scopeSorted(Builder $query, array $params): Builder
    {
        return self::excelFilter()->sort($query, $params);
    }
}
