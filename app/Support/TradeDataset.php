<?php

namespace App\Support;

use App\Imports\TradeFileReader;
use App\Imports\UzbekistanExportImport;
use App\Imports\UzbekistanImportImport;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanImport;
use Illuminate\Database\Eloquent\Model;
use OpenSpout\Reader\XLSX\Reader;

/**
 * O'zbekiston savdo ma'lumotlari turi: import yoki eksport.
 * Har biri o'z jadvali, fayl tuzilishi va o'quvchi klassiga ega.
 */
enum TradeDataset: string
{
    case Import = 'import';
    case Export = 'export';

    /** @return class-string<Model> */
    public function model(): string
    {
        return match ($this) {
            self::Import => UzbekistanImport::class,
            self::Export => UzbekistanExport::class,
        };
    }

    /** @return class-string<TradeFileReader> */
    public function readerClass(): string
    {
        return match ($this) {
            self::Import => UzbekistanImportImport::class,
            self::Export => UzbekistanExportImport::class,
        };
    }

    public function reader(): TradeFileReader
    {
        $class = $this->readerClass();

        return new $class;
    }

    /** Ikkinchi yo'nalish: import uchun — eksport, eksport uchun — import */
    public function other(): self
    {
        return $this === self::Import ? self::Export : self::Import;
    }

    public function label(): string
    {
        return match ($this) {
            self::Import => 'Import',
            self::Export => 'Eksport',
        };
    }

    /**
     * Fayl turini ustun nomlari qatoriga qarab aniqlaydi (birinchi 10 qator ichidan).
     * Ikkala tuzilishga ham mos kelmasa — null.
     */
    public static function detect(string $path): ?self
    {
        $reader = new Reader();

        try {
            $reader->open($path);
        } catch (\Throwable $e) {
            return null;
        }

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                $rowNumber = 0;
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();

                    foreach (self::cases() as $dataset) {
                        if ($dataset->readerClass()::isHeaderRow($values)) {
                            return $dataset;
                        }
                    }

                    if (++$rowNumber >= 10) {
                        break;
                    }
                }

                // faqat birinchi varaq
                break;
            }
        } catch (\Throwable $e) {
            return null;
        } finally {
            $reader->close();
        }

        return null;
    }
}
