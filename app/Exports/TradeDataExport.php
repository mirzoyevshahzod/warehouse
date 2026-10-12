<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use PDO;

/**
 * Filtrlangan va saralangan ma'lumotlarni XLSX faylga yozadi (jadvalda ko'ringan tartibda).
 *
 * Katta hajm uchun: MySQL'da unbuffered cursor bilan qatorma-qator o'qiladi va OpenSpout bilan
 * oqim (stream) sifatida yoziladi — na natija, na fayl xotirada to'planmaydi. Excel varag'i
 * 1 048 576 qatordan oshmasligi uchun har 1 000 000 qatorda yangi varaq ochiladi.
 * Queue job ichidan chaqiriladi.
 */
class TradeDataExport
{
    private const PROGRESS_EVERY = 2000;
    private const ROWS_PER_SHEET = 1000000;
    private const EXPORT_CONNECTION = 'uzbekistan_export_unbuffered';

    /**
     * @param  class-string<Model>  $model  HasExcelColumns trait'li model (UzbekistanImport, UzbekistanExport)
     */
    public function __construct(private string $model, private array $params = [])
    {
    }

    public function count(): int
    {
        return $this->model::query()->filter($this->params)->count();
    }

    /**
     * @param  callable(int):void|null  $onProgress  har PROGRESS_EVERY qatorda yozilgan qatorlar soni bilan chaqiriladi
     * @return int yozilgan qatorlar soni
     */
    public function store(string $path, ?callable $onProgress = null): int
    {
        $columns = array_keys($this->model::LABELS);
        $numeric = array_flip($this->model::NUMERIC_COLUMNS);
        $header = Row::fromValues(array_values($this->model::LABELS), (new Style())->setFontBold());

        $writer = new Writer();
        $writer->openToFile($path);

        $written = 0;
        $onSheet = 0;

        try {
            $writer->addRow($header);

            foreach ($this->cursorQuery()->select($columns)->toBase()->cursor() as $item) {
                if ($onSheet >= self::ROWS_PER_SHEET) {
                    $writer->addNewSheetAndMakeItCurrent();
                    $writer->addRow($header);
                    $onSheet = 0;
                }

                $values = [];
                foreach ($columns as $column) {
                    $value = $item->$column;
                    $values[] = $value !== null && isset($numeric[$column]) ? $value + 0 : $value;
                }

                $writer->addRow(Row::fromValues($values));
                $written++;
                $onSheet++;

                if ($onProgress && $written % self::PROGRESS_EVERY === 0) {
                    $onProgress($written);
                }
            }
        } finally {
            $writer->close();
            DB::purge(self::EXPORT_CONNECTION);
        }

        $onProgress && $onProgress($written);

        return $written;
    }

    /**
     * MySQL'da cursor() odatda butun natijani PHP xotirasiga yuklaydi (buffered).
     * Shuning uchun alohida unbuffered ulanish ishlatiladi — progress yangilash asosiy ulanishda qoladi.
     */
    private function cursorQuery(): Builder
    {
        $default = config('database.default');
        $config = config("database.connections.$default");

        $query = $this->model::query();

        if (($config['driver'] ?? null) === 'mysql' || ($config['driver'] ?? null) === 'mariadb') {
            $config['options'] = array_replace($config['options'] ?? [], [PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false]);
            config(["database.connections.".self::EXPORT_CONNECTION => $config]);
            $query = $this->model::on(self::EXPORT_CONNECTION);
        }

        return $query->filter($this->params)->sorted($this->params);
    }
}
