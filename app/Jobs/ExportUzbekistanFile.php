<?php

namespace App\Jobs;

use App\Exports\UzbekistanImportExport;
use App\Models\UzbekistanImportTask;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Filtrlangan ma'lumotlarni fonda (queue) Excel faylga yozadi.
 * Tayyor bo'lgach fayl /api/uzbekistan-import/tasks/{id}/download orqali yuklab olinadi.
 */
class ExportUzbekistanFile implements ShouldQueue
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
        $export = new UzbekistanImportExport($task->params ?? []);

        $relative = 'uzbekistan/exports/'.$task->original_name;
        Storage::disk('local')->makeDirectory('uzbekistan/exports');

        $task->markProcessing();
        $task->update(['total_rows' => $export->count(), 'file_path' => $relative]);

        $rows = $export->store(Storage::disk('local')->path($relative), function (int $written) use ($task) {
            $task->update(['processed_rows' => $written]);
        });

        $task->update(['total_rows' => $rows, 'processed_rows' => $rows]);
        $task->markCompleted();
    }

    public function failed(Throwable $e): void
    {
        // chala yozilgan faylni o'chiramiz
        if ($this->task->file_path) {
            Storage::disk('local')->delete($this->task->file_path);
        }

        $this->task->markFailed($e);
    }
}
