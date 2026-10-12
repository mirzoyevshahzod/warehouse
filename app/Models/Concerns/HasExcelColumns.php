<?php

namespace App\Models\Concerns;

use App\Support\ExcelFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Excel'dagidek filtr va saralash bilan ishlaydigan jadval modeli.
 *
 * Model quyidagi konstantalarni belgilaydi:
 *   LABELS          — ustun => sarlavha (tartibi — jadval va export tartibi)
 *   DATE_COLUMNS    — sana ustunlari
 *   NUMERIC_COLUMNS — raqam ustunlari
 *   MAIN_FILTERS    — frontend'da tepada ko'rsatiladigan asosiy filtrlar
 *   DEFAULT_SORT    — saralash berilmaganda
 */
trait HasExcelColumns
{
    public function getFillable()
    {
        return array_keys(static::LABELS);
    }

    /** @return array<string, string> ustun => text | number | date */
    public static function columnTypes(): array
    {
        $types = array_fill_keys(array_keys(static::LABELS), ExcelFilter::TEXT);

        foreach (static::DATE_COLUMNS as $column) {
            $types[$column] = ExcelFilter::DATE;
        }
        foreach (static::NUMERIC_COLUMNS as $column) {
            $types[$column] = ExcelFilter::NUMBER;
        }

        return $types;
    }

    public static function excelFilter(): ExcelFilter
    {
        return new ExcelFilter(static::columnTypes(), static::DEFAULT_SORT);
    }

    /**
     * Excel'dagidek filtrlar (qiymatlar ro'yxati, shartlar). Parametrlar formati — App\Support\ExcelFilter.
     *
     * @param  string|null  $except  shu ustun filtri hisobga olinmaydi (filtr variantlari uchun)
     */
    public function scopeFilter(Builder $query, array $params, ?string $except = null): Builder
    {
        return static::excelFilter()->apply($query, $params, $except);
    }

    public function scopeSorted(Builder $query, array $params): Builder
    {
        return static::excelFilter()->sort($query, $params);
    }
}
