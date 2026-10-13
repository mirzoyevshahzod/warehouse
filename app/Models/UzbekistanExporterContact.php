<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eksportyor firmaning mas'ul xodimi (qo'lda kiritiladi). Firma STIR bo'yicha aniqlanadi —
 * bitta firma nomi faylda turlicha yozilishi mumkin, STIR esa bitta.
 */
class UzbekistanExporterContact extends Model
{
    protected $table = 'uzbekistan_exporter_contacts';

    protected $fillable = [
        'exporter_inn',
        'exporter_name',
        'full_name',
        'position',
        'phone',
        'user_id',
    ];

    /** uzbekistan_exports qatorlariga yoziladigan qiymatlar */
    public function toExportColumns(): array
    {
        return [
            'responsible_person_name' => $this->full_name,
            'responsible_person_position' => $this->position,
            'responsible_person_phone' => $this->phone,
        ];
    }

    /** Xodim o'chirilganda qatorlardan tozalanadigan qiymatlar */
    public static function emptyExportColumns(): array
    {
        return [
            'responsible_person_name' => null,
            'responsible_person_position' => null,
            'responsible_person_phone' => null,
        ];
    }
}
