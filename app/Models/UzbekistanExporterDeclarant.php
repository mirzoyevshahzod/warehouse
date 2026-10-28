<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eksportyor firmaning deklaranti (qo'lda kiritiladi). Firma STIR bo'yicha aniqlanadi —
 * xuddi mas'ul xodim kabi (App\Models\UzbekistanExporterContact).
 */
class UzbekistanExporterDeclarant extends Model
{
    protected $table = 'uzbekistan_exporter_declarants';

    protected $fillable = [
        'exporter_inn',
        'exporter_name',
        'full_name',
        'phone',
        'email',
        'company',
        'user_id',
    ];

    /** uzbekistan_exports qatorlariga yoziladigan qiymatlar */
    public function toExportColumns(): array
    {
        return [
            'declarant_name' => $this->full_name,
            'declarant_phone' => $this->phone,
            'declarant_email' => $this->email,
            'declarant_company' => $this->company,
        ];
    }

    /** Deklarant o'chirilganda qatorlardan tozalanadigan qiymatlar */
    public static function emptyExportColumns(): array
    {
        return [
            'declarant_name' => null,
            'declarant_phone' => null,
            'declarant_email' => null,
            'declarant_company' => null,
        ];
    }
}
