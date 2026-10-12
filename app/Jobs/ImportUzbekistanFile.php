<?php

namespace App\Jobs;

use App\Models\UzbekistanImportTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Yuklangan Excel faylni fonda (queue) bazaga yozadi.
 */
class ImportUzbekistanFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;
    public bool $failOnTimeout = true;

    public function __construct(public UzbekistanImportTask $task)
    {
        $this->onConnection('database-long');
        $this->onQueue('exports');
    }

    public function handle(): void
    {
        $task = $this->task;
        $path = Storage::disk('local')->path($task->file_path);

        $reader = $task->tradeDataset()->reader();

        $task->markProcessing();
        $task->update(['total_rows' => $reader::estimateRows($path)]);

        $rows = $reader->import($path, function (int $processed) use ($task) {
            $task->update(['processed_rows' => $processed]);
        });

        $task->update(['total_rows' => $rows, 'processed_rows' => $rows]);
        $task->markCompleted();

        // muvaffaqiyatli yuklangan fayl kerak emas (xatolikda tekshirish uchun qoladi)
        Storage::disk('local')->delete($task->file_path);
    }

    public function failed(Throwable $e): void
    {
        $this->task->markFailed($e);
    }
}
