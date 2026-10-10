<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Excel'dagi AutoFilter kabi filtr va saralash.
 *
 * Parametrlar (query string yoki JSON):
 *
 *   filters[<ustun>][values][]=A&filters[<ustun>][values][]=B   — belgilangan qiymatlar (galochkalar)
 *   filters[<ustun>][mode]=in | not_in                          — in: faqat belgilanganlar (default),
 *                                                                not_in: belgilanganlardan tashqari hammasi
 *   filters[<ustun>][blanks]=1                                  — (Blanks) ham belgilangan
 *
 *   filters[<ustun>][conditions][0][op]=contains                — Custom Filter: 2 tagacha shart
 *   filters[<ustun>][conditions][0][value]=coco
 *   filters[<ustun>][conditions][1][op]=between&...[value]=1&...[value2]=5
 *   filters[<ustun>][logic]=and | or                            — shartlar orasida (default and)
 *
 *   sort[]=-net_weight_kg&sort[]=importer_name                  — ko'p darajali saralash, "-" kamayish tartibi
 *
 * Turli ustunlar filtrlari o'zaro AND bilan birlashadi (Excel'dagidek).
 * Matnli shartlarda "*" — istalgan belgilar, "?" — bitta belgi (Excel wildcard).
 * Bo'sh katak — NULL yoki '' (matn ustunlarida).
 */
class ExcelFilter
{
    public const TEXT = 'text';
    public const NUMBER = 'number';
    public const DATE = 'date';

    public const OPERATORS = [
        'equals', 'not_equals',
        'contains', 'not_contains',
        'starts_with', 'not_starts_with',
        'ends_with', 'not_ends_with',
        'gt', 'gte', 'lt', 'lte',
        'between', 'not_between',
        'blank', 'not_blank',
    ];

    private const NO_VALUE = ['blank', 'not_blank'];
    private const TWO_VALUES = ['between', 'not_between'];
    private const PATTERN_OPS = ['contains', 'not_contains', 'starts_with', 'not_starts_with', 'ends_with', 'not_ends_with'];

    /** LIKE uchun escape belgisi (MySQL va SQLite'da bir xil ishlaydi) */
    private const ESCAPE = '!';

    /**
     * @param  array<string, string>  $columns  ustun => text | number | date
     * @param  string[]  $defaultSort
     */
    public function __construct(private array $columns, private array $defaultSort = [])
    {
    }

    /**
     * Parametrlarni tekshiradi va faqat filters/sort qismini qaytaradi.
     *
     * @throws ValidationException
     */
    public function validate(array $params): array
    {
        $ops = implode(',', self::OPERATORS);

        $validated = Validator::make($params, [
            'filters' => 'nullable|array',
            'filters.*' => 'array',
            'filters.*.values' => 'nullable|array|max:10000',
            'filters.*.values.*' => 'nullable|string|max:2000',
            'filters.*.mode' => 'nullable|in:in,not_in',
            'filters.*.blanks' => 'nullable|in:0,1,true,false',
            'filters.*.logic' => 'nullable|in:and,or',
            'filters.*.conditions' => 'nullable|array|max:2',
            'filters.*.conditions.*.op' => "required|in:$ops",
            'filters.*.conditions.*.value' => 'nullable|string|max:1000',
            'filters.*.conditions.*.value2' => 'nullable|string|max:1000',
            'sort' => 'nullable|array|max:10',
            'sort.*' => 'string',
        ])->validate();

        $errors = [];

        foreach ($validated['filters'] ?? [] as $column => $filter) {
            if (! isset($this->columns[$column])) {
                $errors["filters.$column"] = "Noma'lum ustun: $column";
                continue;
            }

            foreach ($filter['conditions'] ?? [] as $i => $condition) {
                $key = "filters.$column.conditions.$i";
                $op = $condition['op'];

                if (in_array($op, self::NO_VALUE, true)) {
                    continue;
                }

                $values = in_array($op, self::TWO_VALUES, true) ? ['value', 'value2'] : ['value'];
                foreach ($values as $field) {
                    $value = $condition[$field] ?? null;

                    if ($value === null || $value === '') {
                        $errors["$key.$field"] = 'Qiymat kiritilmagan';
                    } elseif (! in_array($op, self::PATTERN_OPS, true) && ! $this->validValue($column, $value)) {
                        $errors["$key.$field"] = $this->columns[$column] === self::NUMBER
                            ? "Raqam bo'lishi kerak"
                            : "Sana YYYY-MM-DD formatida bo'lishi kerak";
                    }
                }
            }
        }

        foreach ($validated['sort'] ?? [] as $i => $sort) {
            if (! isset($this->columns[ltrim($sort, '-')])) {
                $errors["sort.$i"] = "Noma'lum ustun: $sort";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return array_intersect_key($validated, array_flip(['filters', 'sort']));
    }

    /**
     * @param  string|null  $except  shu ustun filtri qo'llanmaydi (ochiladigan ro'yxat variantlari uchun)
     */
    public function apply(Builder $query, array $params, ?string $except = null): Builder
    {
        foreach ($params['filters'] ?? [] as $column => $filter) {
            if ($column === $except || ! isset($this->columns[$column])) {
                continue;
            }

            $this->applyValues($query, $column, $filter);
            $this->applyConditions($query, $column, $filter);
        }

        return $query;
    }

    /**
     * Saralash: bo'sh kataklar Excel'dagidek har doim pastda. Oxirida id — sahifalash barqaror bo'lishi uchun.
     */
    public function sort(Builder $query, array $params): Builder
    {
        $sorts = ($params['sort'] ?? []) ?: $this->defaultSort;

        foreach ($sorts as $sort) {
            $column = ltrim($sort, '-');
            if (! isset($this->columns[$column])) {
                continue;
            }

            $query->orderByRaw($this->blankSql($query, $column).' asc')
                ->orderBy($column, str_starts_with($sort, '-') ? 'desc' : 'asc');
        }

        return $query->orderBy($query->getModel()->getQualifiedKeyName(), 'desc');
    }

    /**
     * Ochiladigan ro'yxat uchun ustunning qiymatlari (Excel'dagidek o'sish tartibida) va bo'sh kataklar soni.
     * Boshqa ustunlar filtrlari hisobga olinadi, shu ustunniki — yo'q.
     */
    public function options(Builder $query, string $column, array $params, ?string $search = null, int $limit = 1000): array
    {
        $this->apply($query, $params, $column);

        $blanks = (clone $query)->whereRaw($this->blankSql($query, $column))->count();

        $values = $query
            ->whereRaw('not '.$this->blankSql($query, $column))
            ->when($search !== null && $search !== '', fn ($q) => $this->whereLike($q, $column, '%'.$this->pattern($search).'%'))
            ->selectRaw($this->wrap($query, $column).' as value, count(*) as count')
            ->groupBy($column)
            ->orderBy($column)
            ->limit($limit + 1)
            ->toBase()
            ->get();

        return [
            'values' => $values->take($limit)->values(),
            'blanks' => $blanks,
            'has_more' => $values->count() > $limit,
        ];
    }

    private function applyValues(Builder $query, string $column, array $filter): void
    {
        $values = $filter['values'] ?? [];
        $blanks = filter_var($filter['blanks'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $exclude = ($filter['mode'] ?? 'in') === 'not_in';

        if (! $values && ! $blanks) {
            // in: hech narsa belgilanmagan — filtr yo'q; not_in: hech narsa chiqarilmagan — filtr yo'q
            return;
        }

        $blank = $this->blankSql($query, $column);

        if (! $exclude) {
            // belgilangan qiymatlar YOKI (Blanks)
            $query->where(function ($q) use ($column, $values, $blanks, $blank) {
                if ($values) {
                    $q->whereIn($column, $values);
                }
                if ($blanks) {
                    $q->orWhereRaw($blank);
                }
            });

            return;
        }

        // belgilanganlardan tashqari hammasi; (Blanks) chiqarilmagan bo'lsa bo'sh kataklar qoladi
        $query->where(function ($q) use ($column, $values, $blanks, $blank) {
            if ($values) {
                $q->whereNotIn($column, $values);
            } else {
                $q->whereRaw('1 = 1');
            }
            $blanks ? $q->whereRaw("not $blank") : $q->orWhereRaw($blank);
        });
    }

    private function applyConditions(Builder $query, string $column, array $filter): void
    {
        $conditions = $filter['conditions'] ?? [];
        if (! $conditions) {
            return;
        }

        $or = ($filter['logic'] ?? 'and') === 'or';

        $query->where(function ($q) use ($column, $conditions, $or) {
            foreach ($conditions as $condition) {
                $q->where(fn ($w) => $this->condition($w, $column, $condition), boolean: $or ? 'or' : 'and');
            }
        });
    }

    private function condition(Builder $q, string $column, array $c): void
    {
        $op = $c['op'];
        $value = $this->castValue($column, $c['value'] ?? null);
        $value2 = $this->castValue($column, $c['value2'] ?? null);
        $raw = (string) ($c['value'] ?? ''); // LIKE andozalari uchun (raqam ustunida ham matn sifatida)
        $blank = $this->blankSql($q, $column);
        $text = $this->columns[$column] === self::TEXT;

        match ($op) {
            'blank' => $q->whereRaw($blank),
            'not_blank' => $q->whereRaw("not $blank"),

            // matnda "*" / "?" bo'lsa — Excel wildcard
            'equals' => $text && $this->hasWildcard($value)
                ? $this->whereLike($q, $column, $this->pattern($raw))
                : $q->where($column, $value),
            'not_equals' => $q->where(fn ($w) => ($text && $this->hasWildcard($value)
                    ? $this->whereLike($w, $column, $this->pattern($raw), not: true)
                    : $w->where($column, '!=', $value)
                )->orWhereRaw($blank)),

            'contains' => $this->whereLike($q, $column, '%'.$this->pattern($raw).'%'),
            'starts_with' => $this->whereLike($q, $column, $this->pattern($raw).'%'),
            'ends_with' => $this->whereLike($q, $column, '%'.$this->pattern($raw)),

            // "... emas" shartlarida bo'sh kataklar ham ko'rinadi (Excel'dagidek)
            'not_contains' => $q->where(fn ($w) => $this->whereLike($w, $column, '%'.$this->pattern($raw).'%', not: true)->orWhereRaw($blank)),
            'not_starts_with' => $q->where(fn ($w) => $this->whereLike($w, $column, $this->pattern($raw).'%', not: true)->orWhereRaw($blank)),
            'not_ends_with' => $q->where(fn ($w) => $this->whereLike($w, $column, '%'.$this->pattern($raw), not: true)->orWhereRaw($blank)),

            'gt' => $q->where($column, '>', $value),
            'gte' => $q->where($column, '>=', $value),
            'lt' => $q->where($column, '<', $value),
            'lte' => $q->where($column, '<=', $value),
            'between' => $q->whereBetween($column, [min($value, $value2), max($value, $value2)]),
            'not_between' => $q->whereNotBetween($column, [min($value, $value2), max($value, $value2)]),
        };
    }

    private function whereLike(Builder $q, string $column, string $pattern, bool $not = false): Builder
    {
        return $q->whereRaw(
            $this->wrap($q, $column).($not ? ' not' : '')." like ? escape '".self::ESCAPE."'",
            [$pattern]
        );
    }

    /** Foydalanuvchi matnini LIKE andozasiga: %, _ oddiy belgi; *, ? — Excel wildcard */
    private function pattern(string $value): string
    {
        $e = self::ESCAPE;
        $value = str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $value);

        return str_replace(['*', '?'], ['%', '_'], $value);
    }

    private function hasWildcard(mixed $value): bool
    {
        return is_string($value) && (str_contains($value, '*') || str_contains($value, '?'));
    }

    private function blankSql(Builder $q, string $column): string
    {
        $wrapped = $this->wrap($q, $column);

        return $this->columns[$column] === self::TEXT
            ? "($wrapped is null or $wrapped = '')"
            : "($wrapped is null)";
    }

    private function wrap(Builder $q, string $column): string
    {
        return $q->getQuery()->getGrammar()->wrap($column);
    }

    private function validValue(string $column, string $value): bool
    {
        return match ($this->columns[$column]) {
            self::NUMBER => is_numeric($value),
            self::DATE => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) !== false,
            default => true,
        };
    }

    private function castValue(string $column, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return $this->columns[$column] === self::NUMBER ? $value + 0 : $value;
    }
}
