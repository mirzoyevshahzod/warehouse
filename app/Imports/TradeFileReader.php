<?php

namespace App\Imports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use OpenSpout\Reader\XLSX\Reader;
use RuntimeException;
use ZipArchive;

/**
 * "Download_UZBEKISTAN_*_Trade_Data_*.xlsx" fayllarini o'qib bazaga yozadi (umumiy qism).
 *
 * 1-qator — sarlavha, 2-qator — ustun nomlari, keyin ma'lumot. Ustunlar nomi bo'yicha emas,
 * indeksi bo'yicha o'qiladi (nomlarda dublikat bo'lishi mumkin).
 *
 * Katta fayllar uchun OpenSpout bilan qatorma-qator (stream) o'qiladi — fayl to'liq xotiraga
 * yuklanmaydi. Queue job ichidan chaqiriladi.
 *
 * Har bir fayl turi uchun voris klass belgilaydi:
 *   EXPECTED_HEADERS — ustun nomlari qatorini tanish uchun [indeks => nom]
 *   UNIQUE_BY        — qayta yuklashda yozuvni yangilash uchun kalit ustunlar
 *   modelClass()     — qaysi jadvalga yoziladi
 *   mapRow()         — Excel qatori => baza yozuvi
 */
abstract class TradeFileReader
{
    protected const BATCH_SIZE = 500;

    /** @var array<int, string> */
    protected const EXPECTED_HEADERS = [];

    /** @var string[] */
    protected const UNIQUE_BY = [];

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Excel qatorini baza yozuviga o'giradi; bo'sh qator uchun null */
    abstract protected function mapRow(array $row): ?array;

    /**
     * Faylni o'qib bazaga yozadi.
     *
     * @param  callable(int):void|null  $onProgress  har bir batch'dan keyin yozilgan qatorlar soni bilan chaqiriladi
     * @return int yozilgan (qo'shilgan yoki yangilangan) qatorlar soni
     */
    public function import(string $path, ?callable $onProgress = null): int
    {
        $reader = new Reader();
        $reader->open($path);

        $processed = 0;
        $batch = [];
        $headerFound = false;
        $rowNumber = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    $rowNumber++;

                    if (! $headerFound) {
                        $headerFound = static::isHeaderRow($values);
                        if (! $headerFound && $rowNumber >= 10) {
                            throw new RuntimeException(
                                "Fayl formati noto'g'ri: ustun nomlari qatori topilmadi (kutilgan: \""
                                .implode('", "', static::EXPECTED_HEADERS).'")'
                            );
                        }
                        continue;
                    }

                    $record = $this->mapRow($values);
                    if ($record === null) {
                        continue;
                    }

                    // bitta batch ichida takror bo'lsa — oxirgisi qoladi
                    $batch[implode('|', array_map(fn ($k) => $record[$k], static::UNIQUE_BY))] = $record;

                    if (count($batch) >= static::BATCH_SIZE) {
                        $processed += $this->save($batch);
                        $batch = [];
                        $onProgress && $onProgress($processed);
                    }
                }

                // faqat birinchi varaq
                break;
            }

            if ($batch) {
                $processed += $this->save($batch);
                $onProgress && $onProgress($processed);
            }
        } finally {
            $reader->close();
        }

        if (! $headerFound) {
            throw new RuntimeException("Fayl formati noto'g'ri yoki fayl bo'sh");
        }

        return $processed;
    }

    /** Shu qator — ushbu fayl turining ustun nomlari qatorimi */
    public static function isHeaderRow(array $values): bool
    {
        foreach (static::EXPECTED_HEADERS as $index => $name) {
            if (strcasecmp(trim((string) ($values[$index] ?? '')), $name) !== 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Progress uchun ma'lumot qatorlari soni: varaq XML'idagi "<row " teglari sanaladi
     * (kataklar parse qilinmaydi, shuning uchun tez). Sarlavha va ustun nomlari qatori ayiriladi.
     */
    public static function estimateRows(string $path): ?int
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $stream = $zip->getStream('xl/worksheets/sheet1.xml');
        if (! $stream) {
            $zip->close();

            return null;
        }

        $count = 0;
        $tail = '';
        while (! feof($stream)) {
            $chunk = $tail.fread($stream, 1048576);
            $count += substr_count($chunk, '<row ');
            // bo'lak chegarasida kesilgan tegni keyingi bo'lakda sanash uchun
            $tail = substr($chunk, -4);
            $count -= substr_count($tail, '<row ');
        }

        fclose($stream);
        $zip->close();

        return max(0, $count - 2);
    }

    protected function save(array $records): int
    {
        $now = now();
        $records = array_values(array_map(
            fn ($r) => $r + ['created_at' => $now, 'updated_at' => $now],
            $records
        ));

        $update = array_values(array_diff(array_keys($records[0]), [...static::UNIQUE_BY, 'created_at']));

        $this->modelClass()::upsert($records, static::UNIQUE_BY, $update);

        return count($records);
    }

    protected function text($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = trim(preg_replace('/[ \t]+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    protected function number($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (float) $value : null;
    }

    protected function date($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            // "2026-08-31T00:00:00" / "2026-08-31"
            return Carbon::parse(trim($value))->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}
