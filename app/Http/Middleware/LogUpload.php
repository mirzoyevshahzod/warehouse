<?php

namespace App\Http\Middleware;

use App\Models\UploadLog;
use App\Support\DataSources;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Fayl yuklash so'rovini jurnalga yozadi: manba, foydalanuvchi, fayl, natija,
 * jadvaldagi qatorlar soni yuklashdan oldin va keyin.
 *
 * Route'da: ->middleware('log.upload:turkey') — parametr App\Support\DataSources kaliti.
 */
class LogUpload
{
    public function handle(Request $request, Closure $next, string $source): Response
    {
        $model = DataSources::get($source)['model'] ?? null;
        $file = $request->file('file');
        $started = microtime(true);
        $before = $model ? $model::count() : null;

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            // validatsiya xatosi (noto'g'ri fayl turi va h.k.) — fayl yuklanmagan, jurnalga yozilmaydi
            if (! $e instanceof ValidationException) {
                $this->write($request, $source, $file, $started, $before, $model, $e->getMessage());
            }
            throw $e;
        }

        // Laravel istisnolarni javobga aylantiradi — natijani javob kodidan bilamiz
        $status = $response->getStatusCode();
        if ($status !== 422) {
            $error = null;
            if ($status >= 400) {
                $error = json_decode((string) $response->getContent(), true)['message'] ?? "HTTP $status";
            }
            $this->write($request, $source, $file, $started, $before, $model, $error);
        }

        return $response;
    }

    private function write(Request $request, string $source, mixed $file, float $started, ?int $before, ?string $model, ?string $error): void
    {
        try {
            UploadLog::create([
                'source' => $source,
                'user_id' => $request->user()?->id,
                'file_name' => $file instanceof UploadedFile ? mb_substr($file->getClientOriginalName(), 0, 255) : null,
                'file_size' => $file instanceof UploadedFile ? $file->getSize() : null,
                'status' => $error === null ? UploadLog::STATUS_SUCCESS : UploadLog::STATUS_FAILED,
                'rows_before' => $before,
                'rows_after' => $model ? $model::count() : null,
                'error' => $error === null ? null : mb_substr($error, 0, 2000),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ]);
        } catch (Throwable $e) {
            // jurnal yozilmasa ham yuklash natijasi foydalanuvchiga qaytishi kerak
            report($e);
        }
    }
}
