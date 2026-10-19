<?php

namespace App\Support;

use App\Models\AVIAEOmbor;
use App\Models\BelarusBenyakoni;
use App\Models\BelarusBrest;
use App\Models\BelarusGrigorovschina;
use App\Models\BelarusKomenni;
use App\Models\BelarusKozlovichi;
use App\Models\CheckedQozoq;
use App\Models\Driver;
use App\Models\EOmbor;
use App\Models\ImportEksportQozoq;
use App\Models\ImportQozoq;
use App\Models\Mintrans;
use App\Models\Qozoq;
use App\Models\RWEOmbor;
use App\Models\Turkey;
use App\Models\UzbekistanExport;
use App\Models\UzbekistanImport;
use Illuminate\Database\Eloquent\Model;

/**
 * Saytdagi barcha ma'lumot manbalari — yagona ro'yxat.
 * Sidebar, Dashboard statistikasi va yuklashlar jurnali shu ro'yxatdan foydalanadi.
 * Yangi manba qo'shilsa — shu yerga bitta qator qo'shiladi.
 */
class DataSources
{
    /** Manba ma'lumotlari shu muddatdan (kun) beri yangilanmasa — eskirgan deb belgilanadi */
    public const STALE_DAYS = 7;

    /**
     * key => [label, group, model, route (web sahifa nomi), icon]
     *
     * @return array<string, array{label: string, group: string, model: class-string<Model>, route: string, icon: string}>
     */
    public static function all(): array
    {
        return [
            'uzbekistan-import' => ['label' => "O'zbekiston import", 'group' => "O'zbekiston", 'model' => UzbekistanImport::class, 'route' => 'uzbekistanImport', 'icon' => 'download'],
            'uzbekistan-export' => ['label' => "O'zbekiston eksport", 'group' => "O'zbekiston", 'model' => UzbekistanExport::class, 'route' => 'uzbekistanExport', 'icon' => 'upload'],

            'qozoq' => ['label' => 'Umumiy', 'group' => "Qozog'iston", 'model' => Qozoq::class, 'route' => 'qozoqScraping', 'icon' => 'truck'],
            'checked-qozoq' => ['label' => 'Tekshirilganlar (eksport)', 'group' => "Qozog'iston", 'model' => CheckedQozoq::class, 'route' => 'checkedQozoqScraping', 'icon' => 'check'],
            'import-qozoq' => ['label' => 'Import', 'group' => "Qozog'iston", 'model' => ImportQozoq::class, 'route' => 'importQozoqScraping', 'icon' => 'download'],
            'import-eksport-qozoq' => ['label' => 'Import-eksport', 'group' => "Qozog'iston", 'model' => ImportEksportQozoq::class, 'route' => 'importEksportQozoqScraping', 'icon' => 'swap'],

            'belarus-benyakoni' => ['label' => 'Benyakoni', 'group' => 'Belarus', 'model' => BelarusBenyakoni::class, 'route' => 'belarusBenyakoni', 'icon' => 'flag'],
            'belarus-brest' => ['label' => 'Brest', 'group' => 'Belarus', 'model' => BelarusBrest::class, 'route' => 'belarusBrest', 'icon' => 'flag'],
            'belarus-gigorovschina' => ['label' => 'Grigorovschina', 'group' => 'Belarus', 'model' => BelarusGrigorovschina::class, 'route' => 'belarusGigorovschina', 'icon' => 'flag'],
            'belarus-kamenny-log' => ['label' => 'Kamenny Log', 'group' => 'Belarus', 'model' => BelarusKomenni::class, 'route' => 'belarusKeminnii', 'icon' => 'flag'],
            'belarus-kozlovichi' => ['label' => 'Kozlovichi', 'group' => 'Belarus', 'model' => BelarusKozlovichi::class, 'route' => 'belarusKozlovichi', 'icon' => 'flag'],

            'eombor-at' => ['label' => 'E-Ombor AT', 'group' => 'E-Ombor', 'model' => EOmbor::class, 'route' => 'eOmborATScraping', 'icon' => 'box'],
            'eombor-rw' => ['label' => 'E-Ombor RW', 'group' => 'E-Ombor', 'model' => RWEOmbor::class, 'route' => 'eOmborRWScraping', 'icon' => 'box'],
            'eombor-avia' => ['label' => 'E-Ombor AVIA', 'group' => 'E-Ombor', 'model' => AVIAEOmbor::class, 'route' => 'eOmborAVIAScraping', 'icon' => 'box'],

            'turkey' => ['label' => 'Turkiya', 'group' => 'Boshqalar', 'model' => Turkey::class, 'route' => 'turkeyScraping', 'icon' => 'globe'],
            'mintrans' => ['label' => 'Mintrans', 'group' => 'Boshqalar', 'model' => Mintrans::class, 'route' => 'mintransScraping', 'icon' => 'road'],
            'drivers' => ['label' => 'Haydovchilar', 'group' => 'Boshqalar', 'model' => Driver::class, 'route' => 'drivers', 'icon' => 'users'],
        ];
    }

    /** @return array{label: string, group: string, model: class-string<Model>, route: string, icon: string}|null */
    public static function get(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /** Sidebar uchun: guruh => [key => manba] */
    public static function grouped(): array
    {
        $groups = [];
        foreach (static::all() as $key => $source) {
            $groups[$source['group']][$key] = $source;
        }

        return $groups;
    }

    public static function table(string $key): string
    {
        return (new (static::all()[$key]['model']))->getTable();
    }
}
