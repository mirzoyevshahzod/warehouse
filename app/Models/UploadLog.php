<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Fayl yuklash jurnali (App\Http\Middleware\LogUpload yozadi) */
class UploadLog extends Model
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source',
        'user_id',
        'file_name',
        'file_size',
        'status',
        'rows_before',
        'rows_after',
        'error',
        'duration_ms',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Yuklash natijasida qo'shilgan qatorlar (yangilanganlari hisobga kirmaydi) */
    public function addedRows(): ?int
    {
        if ($this->rows_before === null || $this->rows_after === null) {
            return null;
        }

        return max(0, $this->rows_after - $this->rows_before);
    }
}
