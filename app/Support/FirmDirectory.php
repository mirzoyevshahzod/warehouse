<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

/**
 * Takrorlanmas firmalar (STIR bo'yicha) va ularning ma'lumotlari: nomlari, manzili, telefonlari,
 * mas'ul xodimlari, davlatlari. Jadvaldagi filtrlar hisobga olinadi.
 *
 * Model FIRM konstantasida qaysi ustunlar ishlatilishini belgilaydi:
 *   inn       — firma STIR ustuni (firma shu bo'yicha aniqlanadi)
 *   name      — firma nomi
 *   date      — oxirgi yozuv sanasi uchun
 *   address   — [address, region, district] (ixtiyoriy)
 *   phone     — firma telefoni (ixtiyoriy)
 *   contacts  — [ism, lavozim, telefon]
 *   country   — davlat ustuni (import — kelib chiqish, eksport — boradigan davlat)
 */
class FirmDirectory
{
    private const ESCAPE = '!';

    /** Har bir firma uchun ko'rsatiladigan ro'yxatlar uzunligi */
    private const LIST_LIMIT = 5;

    private array $firm;

    /** @param class-string<Model> $model */
    public function __construct(private string $model)
    {
        $this->firm = $model::FIRM;
    }

    /**
     * @return array{total: int, with_contacts: int, rows: int, current_page: int, last_page: int, per_page: int, data: Collection}
     */
    public function paginate(array $params, ?string $search, int $page, int $perPage): array
    {
        $inn = $this->firm['inn'];
        $base = $this->scope($params)->where(fn (Builder $w) => $this->search($w, $search));

        $total = (clone $base)->distinct()->count($inn);
        $withContacts = (clone $base)->whereNotNull($this->firm['contacts'][0])->distinct()->count($inn);
        $rows = (clone $base)->count();

        // eng ko'p qatorli firmalar birinchi
        $firms = (clone $base)
            ->select($inn)
            ->selectRaw('count(*) as rows_count')
            ->selectRaw('max('.$base->getGrammar()->wrap($this->firm['date']).') as last_date')
            ->groupBy($inn)
            ->orderByDesc('rows_count')
            ->orderBy($inn)
            ->forPage($page, $perPage)
            ->get();

        return [
            'total' => $total,
            'with_contacts' => $withContacts,
            'rows' => $rows,
            'current_page' => $page,
            'last_page' => max(1, (int) ceil($total / $perPage)),
            'per_page' => $perPage,
            'data' => $this->details($firms, $params),
        ];
    }

    /** Sahifadagi firmalarning batafsil ma'lumotlari — bir nechta guruhlangan so'rov bilan */
    private function details(Collection $firms, array $params): Collection
    {
        $inn = $this->firm['inn'];
        $inns = $firms->pluck($inn)->all();
        if (! $inns) {
            return collect();
        }

        $names = $this->top($params, $inns, [$this->firm['name']]);
        $contacts = $this->top($params, $inns, $this->firm['contacts']);
        $countries = $this->top($params, $inns, [$this->firm['country']]);
        $addresses = isset($this->firm['address']) ? $this->top($params, $inns, $this->firm['address']) : collect();
        $phones = isset($this->firm['phone']) ? $this->top($params, $inns, [$this->firm['phone']]) : collect();

        return $firms->map(function ($firm) use ($inn, $names, $contacts, $countries, $addresses, $phones) {
            $key = (string) $firm->{$inn};
            [$name, $position, $phone] = $this->firm['contacts'];
            $address = $addresses->get($key)?->first();

            return [
                'inn' => $key,
                'name' => $names->get($key)?->first()?->{$this->firm['name']},
                'names' => $names->get($key, collect())->pluck($this->firm['name'])->values(),
                'rows' => (int) $firm->rows_count,
                'last_date' => $firm->last_date ? substr((string) $firm->last_date, 0, 10) : null,
                'address' => $address ? [
                    'address' => $address->{$this->firm['address'][0]},
                    'region' => $address->{$this->firm['address'][1]},
                    'district' => $address->{$this->firm['address'][2]},
                ] : null,
                'phones' => $phones->get($key, collect())->pluck($this->firm['phone'] ?? 'phone')->take(self::LIST_LIMIT)->values(),
                'contacts' => $contacts->get($key, collect())->take(self::LIST_LIMIT)->map(fn ($c) => [
                    'full_name' => $c->{$name},
                    'position' => $c->{$position},
                    'phone' => $c->{$phone},
                    'rows' => (int) $c->rows_count,
                ])->values(),
                'countries' => $countries->get($key, collect())->take(self::LIST_LIMIT)->map(fn ($c) => [
                    'value' => $c->{$this->firm['country']},
                    'rows' => (int) $c->rows_count,
                ])->values(),
            ];
        });
    }

    /**
     * Ustun(lar) qiymatlari firmalar bo'yicha, ko'p uchraganidan kamiga. Birinchi ustuni bo'sh qatorlar olinmaydi.
     *
     * @return Collection<string, Collection> STIR => [{...ustunlar, rows_count}]
     */
    private function top(array $params, array $inns, array $columns): Collection
    {
        $inn = $this->firm['inn'];

        return $this->scope($params)
            ->whereIn($inn, $inns)
            ->whereNotNull($columns[0])
            ->where($columns[0], '!=', '')
            ->select([$inn, ...$columns])
            ->selectRaw('count(*) as rows_count')
            ->groupBy([$inn, ...$columns])
            ->orderByDesc('rows_count')
            ->get()
            ->groupBy(fn ($row) => (string) $row->{$inn});
    }

    /** Jadval filtrlari qo'llangan, STIR'i bor qatorlar */
    private function scope(array $params): Builder
    {
        $inn = $this->firm['inn'];

        return $this->model::query()
            ->filter($params)
            ->whereNotNull($inn)
            ->where($inn, '!=', '')
            ->toBase();
    }

    /** Firma nomi yoki STIR bo'yicha qidirish */
    private function search(Builder $query, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $e = self::ESCAPE;
        $pattern = '%'.str_replace([$e, '%', '_'], [$e.$e, $e.'%', $e.'_'], $search).'%';
        $grammar = $query->getGrammar();

        $query->whereRaw($grammar->wrap($this->firm['name'])." like ? escape '$e'", [$pattern])
            ->orWhereRaw($grammar->wrap($this->firm['inn'])." like ? escape '$e'", [$pattern]);
    }
}
