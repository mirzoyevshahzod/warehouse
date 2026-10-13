<?php

namespace App\Models;

use App\Models\Concerns\HasExcelColumns;
use Illuminate\Database\Eloquent\Model;

class UzbekistanExport extends Model
{
    use HasExcelColumns;

    protected $table = 'uzbekistan_exports';

    /**
     * Ustun => sarlavha (asl Excel fayldagi nomi). Ustunlar tartibi — jadval va export tartibi.
     */
    public const LABELS = [
        'date' => 'Date',
        'exporter_name' => 'Exporter',
        'exporter_inn' => 'Exporter ID',
        'importer_name' => 'Importer',
        'hs_code' => 'HS code',
        'product_description' => 'Product Description',
        'quantity' => 'Quantity',
        'quantity_unit' => 'Quantity unit',
        'net_weight_kg' => 'Net weight (kg)',
        'invoice_amount_usd' => 'Invoice amount (USD)',
        'invoice_unit_price_usd' => 'Invoice unit price (USD)',
        'customs_value_usd' => 'Customs Value (USD)',
        'statistical_value_usd' => 'Statistical Value (USD)',
        'destination_country' => 'Destination country',
        'destination' => 'Destination',
        'transit_country' => 'Transit country',
        'customs_system' => 'Customs system',
        'supervision_method' => 'Supervision methods',
        'transaction_method' => 'Transaction method',
        'transportation_method' => 'Transportation method',
        'regime_type' => 'Import and export types',
        'regime_code' => 'Import and export type code',
        'declaration_type_name' => 'Declaration type name',
        'previous_declaration_type' => 'Previous declaration type',
        'freight_transport_type_code' => 'Freight transportation type code',
        'previous_declaration_type_name' => 'Previous declaration type name',
        'delivery_condition_code' => 'Delivery condition number code',
        'delivery_condition_name' => 'Delivery condition name',
        'transportation_method_code' => 'Transportation method code',
        // qo'lda kiritiladi ("Xodim qo'shish") — App\Models\UzbekistanExporterContact
        'responsible_person_name' => 'Responsible person',
        'responsible_person_position' => 'Responsible person position',
        'responsible_person_phone' => 'Responsible person phone',
    ];

    public const DATE_COLUMNS = ['date'];

    public const NUMERIC_COLUMNS = [
        'quantity', 'net_weight_kg',
        'invoice_amount_usd', 'invoice_unit_price_usd', 'customs_value_usd', 'statistical_value_usd',
    ];

    /** Asosiy filtrlar (frontend'da tepada alohida ko'rsatiladi) */
    public const MAIN_FILTERS = [
        'exporter_name',
        'exporter_inn',
        'importer_name',
        'destination_country',
        'transportation_method',
        'transaction_method',
    ];

    /** Saralash berilmaganda */
    public const DEFAULT_SORT = ['-date'];

    protected $guarded = ['id'];

    protected $hidden = ['row_key'];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];
}
