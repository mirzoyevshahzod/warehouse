<?php

namespace App\Models;

use App\Support\TradeDataset;
use Illuminate\Database\Eloquent\Model;

class UzbekistanImportTask extends Model
{
    public const TYPE_IMPORT = 'import';
    public const TYPE_EXPORT = 'export';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $table = 'uzbekistan_import_tasks';

    protected $fillable = [
        'type',
        'dataset',
        'status',
        'user_id',
        'original_name',
        'file_path',
        'params',
        'total_rows',
        'processed_rows',
        'error',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $hidden = [
        'file_path',
    ];

    protected $appends = [
        'progress',
    ];

    /** Foizda bajarilish darajasi (frontend progress bar uchun) */
    public function getProgressAttribute(): ?int
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return 100;
        }

        if (! $this->total_rows) {
            return null;
        }

        return (int) min(99, floor($this->processed_rows * 100 / $this->total_rows));
    }

    /** Vazifa qaysi ma'lumotlarga tegishli (import / eksport) */
    public function tradeDataset(): TradeDataset
    {
        return TradeDataset::from($this->dataset ?: TradeDataset::Import->value);
    }

    public function markProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING, 'started_at' => now(), 'error' => null]);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED, 'finished_at' => now()]);
    }

    public function markFailed(\Throwable $e): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'finished_at' => now(),
            'error' => mb_substr($e->getMessage(), 0, 2000),
        ]);
    }
}
