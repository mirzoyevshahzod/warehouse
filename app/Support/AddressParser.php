<?php

namespace App\Support;

/**
 * Bojxona eksport fayllaridagi erkin yozilgan manzilni bo'laklarga ajratadi:
 * davlat, viloyat, tuman, shahar, mahalla/qishloq, ko'cha (qolgan qism).
 *
 * Manzillar ingliz/rus tilida, turli tartibda va xatolar bilan keladi,
 * shuning uchun ajratish kalit so'zlarga asoslangan va 100% aniq emas —
 * xom manzil ham har doim alohida saqlanadi.
 */
class AddressParser
{
    /** Kalit so'z (regex) => davlat nomi */
    private const COUNTRIES = [
        'UZBEKISTAN|УЗБЕКИСТАН|УЗБЕКСИТАН|O\'ZBEKISTON|RUZ\b' => 'UZBEKISTAN',
        'CHINA|КИТАЙ|\bPRC\b|P\.R\.C' => 'CHINA',
        'HONG\s*KONG|ГОНКОНГ' => 'HONG KONG',
        'KAZAKHSTAN|КАЗАХСТАН|\bRK\b' => 'KAZAKHSTAN',
        'KYRGYZ|КЫРГЫЗ|КИРГИЗ' => 'KYRGYZSTAN',
        'RUSSIA|РОССИ|\bRF\b' => 'RUSSIA',
        'BELARUS|БЕЛАРУС|БЕЛОРУС' => 'BELARUS',
        'SPAIN|ИСПАНИЯ' => 'SPAIN',
        'TURKIYE|TURKEY|ТУРЦИЯ' => 'TURKEY',
        'S\.\s*KOREA|SOUTH KOREA|\bKOREA\b|КОРЕЯ' => 'SOUTH KOREA',
        'TAJIKISTAN|ТАДЖИКИСТАН' => 'TAJIKISTAN',
        'TURKMENISTAN|ТУРКМЕНИСТАН' => 'TURKMENISTAN',
        'GERMANY|ГЕРМАНИЯ' => 'GERMANY',
        'INDIA\b|ИНДИЯ' => 'INDIA',
        '\bIRAN\b|ИРАН' => 'IRAN',
        '\bU\.?A\.?E\b|EMIRATES|ОАЭ' => 'UAE',
        'POLAND|ПОЛЬША' => 'POLAND',
        'LATVIA|ЛАТВИЯ' => 'LATVIA',
        'LITHUANIA|ЛИТВА' => 'LITHUANIA',
        'ITALY|ИТАЛИЯ' => 'ITALY',
        'JAPAN|ЯПОНИЯ' => 'JAPAN',
    ];

    /** O'zbekiston viloyatlari: kalit so'z (regex) => kanonik nom */
    private const UZ_REGIONS = [
        'TASHKENT|TOSHKENT|ТАШКЕНТ|ТОШКЕНТ' => 'TOSHKENT',
        'ANDIJAN|ANDIZHAN|ANZHIZHAN|ANDIJON|ANDIJ\.|АНДИЖАН|АНДИЖОН' => 'ANDIJON VILOYATI',
        'FERGANA|FARGONA|FARG\'ONA|ФЕРГАН|ФАРГОНА|ФАРҒОНА' => 'FARG\'ONA VILOYATI',
        'NAMANGAN|НАМАНГАН' => 'NAMANGAN VILOYATI',
        'SAMARKAND|SAMARQAND|САМАРКАНД|САМАРҚАНД' => 'SAMARQAND VILOYATI',
        'BUKHARA|BUXORO|БУХАР|БУХОРО' => 'BUXORO VILOYATI',
        'NAVOI|NAVOIY|НАВОИ|НАВОИЙ' => 'NAVOIY VILOYATI',
        'KASHKADAR|QASHQADARYO|КАШКАДАР|ҚАШҚАДАРЁ' => 'QASHQADARYO VILOYATI',
        'SURKHANDAR|SURXONDARYO|СУРХАНДАР|СУРХОНДАРЁ' => 'SURXONDARYO VILOYATI',
        'SYRDAR|SIRDARYO|СЫРДАР|СИРДАР' => 'SIRDARYO VILOYATI',
        'JIZZAKH|JIZZAX|DJIZAK|DZHIZAK|ДЖИЗАК|ЖИЗЗАХ' => 'JIZZAX VILOYATI',
        'KHOREZM|XORAZM|ХОРЕЗМ|ХОРАЗМ' => 'XORAZM VILOYATI',
        'KARAKALPAK|QORAQALPOG|КАРАКАЛПАК|ҚОРАҚАЛПОҒ|\bRK\b' => 'QORAQALPOG\'ISTON RESPUBLIKASI',
    ];

    /** Viloyat ko'rsatilmaganda shahar nomidan viloyatni topish */
    private const UZ_CITY_REGION = [
        'SERGELI|CHILANZ|CHILONZOR|YUNUSABAD|YUNUSOBOD|YAKKASAR|MIRABAD|MIROBOD|MIRZO.?ULUG|SHAYKH|SHAYX|ALMAZAR|OLMAZOR|UCHTEP|YASHNABAD|YASHNOBOD|BEKTEMIR|YANGI.?K?HAYOT|YAGIHAYOT' => 'TOSHKENT SHAHRI',
        'ZANGIAT|ZANGIOT|KIBRAY|QIBRAY|PARKENT|URTACHIRCHIK|AKHANGARAN|OHANGARON|BOSTANLYK|BO\'STONLIQ' => 'TOSHKENT VILOYATI',
        'KOKAND|QO\'QON|КОКАНД|MARGILAN|МАРГИЛАН|KUVASAY|QUVASOY' => 'FARG\'ONA VILOYATI',
        'URGENCH|URGANCH|УРГЕНЧ|KHIVA|XIVA' => 'XORAZM VILOYATI',
        'KARSHI|KARSHY|QARSHI|КАРШИ|SHAKHRISABZ|SHAHRISABZ' => 'QASHQADARYO VILOYATI',
        'TERMEZ|TERMIZ|ТЕРМЕЗ|DENAU|DENOV' => 'SURXONDARYO VILOYATI',
        'CHIRCHIK|CHIRCHIQ|ЧИРЧИК|ANGREN|ОHANGARON|ALMALYK|OLMALIQ|YANGIYUL|YANGIYO\'L|BEKABAD|BEKOBOD|NURAFSHON' => 'TOSHKENT VILOYATI',
        'NUKUS|НУКУС' => 'QORAQALPOG\'ISTON RESPUBLIKASI',
        'GULISTAN|GULISTON|ГУЛИСТАН|YANGIYER' => 'SIRDARYO VILOYATI',
        'ZARAFSHAN|ZARAFSHON' => 'NAVOIY VILOYATI',
    ];

    private const REGION_WORDS = 'REGION|ОБЛАСТЬ|ОБЛ\.?|VILOYATI|VILOYAT|ВИЛОЯТИ|PROVINCE|TERRITORY|KRAI|КРАЙ|OBLAST';
    private const DISTRICT_WORDS = 'DISTRICT|DISTRICTS|РАЙОН|Р-Н|RN|RAYON|TUMANI|TUMAN|ТУМАНИ|-GU|COUNTY';
    private const CITY_WORDS = 'CITY|ГОРОД|SHAHRI|SHAKHRI|SHAHAR|ШАХРИ|ШАҲРИ|ША-РИ';
    /** "G.TASHKENT", "Г. НАВОИ", "D. TASHKENT" (г. ning xato tarjimasi) */
    private const CITY_PREFIX = '/^(G|Г|D)\.\s?(?=[A-ZА-ЯЁ]{3})/u';
    private const STREET_WORDS = 'STREET|STR|ST|UL|УЛ|УЛИЦА|KUCHA|KUCHASI|KOCHASI|КУЧАСИ|КЎЧАСИ|K-CHASY|AVENUE|AVE|PR|PROSPECT|ПРОСПЕКТ|ROAD|RD|HIGHWAY|BULV|HOUSE|BUILDING|APARTMENT|APT|ДОМ|Д|KV|КВ|ROOM|OFFICE|FLOOR|UNIT|NO|SQUARE|LANE|TUPIK|PROEZD|ПРОЕЗД|QUARTER|BLOCK|BLDG|CALLE|CAD|MAH|BULVARI|SH';
    private const MAHALLA_WORDS = 'MFY|MFI|MFB|MFE|MFU|MSG|SMG|SSG|KFY|MKP|SGP|MUNICIPAL GROUP|MFM|OFY|МФЙ|МСГ|ССГ|MAHALLA|MAHALLASI|МАХАЛЛЯ|МАХАЛЛА|MAKHALLA|VILLAGE|QISHLOQ|КИШЛАК|АУЛ|AUL|NEIGHBORHOOD|RURAL|POS\.|ПОС\.|SHAKHARCHASI|SHAHARCHA|MASSIV|МАССИВ|TOWN|SETTLEMENT|ASSEMBLY OF CITIZENS';

    /** Xitoy provinsiyalari — "REGION" so'zisiz keladi */
    private const CN_PROVINCES = 'XINJIANG|ZHEJIANG|GUANGDONG|JIANGSU|SHANDONG|FUJIAN|HEBEI|HENAN|HUBEI|HUNAN|SICHUAN|SHAANXI|SHANXI|ANHUI|JIANGXI|LIAONING|JILIN|HEILONGJIANG|YUNNAN|GUANGXI|GANSU|CHONGQING|SHANGHAI|BEIJING|TIANJIN|INNER MONGOLIA';

    /**
     * @param  string|null  $raw
     * @param  bool  $uzbek  manzil O'zbekistonda ekanligi ma'lum bo'lsa — viloyat nomi kanoniklashtiriladi
     * @return array{country:?string, region:?string, district:?string, city:?string, mahalla:?string, street:?string}
     */
    public static function parse(?string $raw, bool $uzbek = false): array
    {
        $result = [
            'country' => null,
            'region' => null,
            'district' => null,
            'city' => null,
            'mahalla' => null,
            'street' => null,
        ];

        $text = self::clean($raw);
        if ($text === '') {
            return $result;
        }

        // importyor / shartnoma egasi / deklarant manzili doim O'zbekistonda ("RK" = Qoraqalpog'iston)
        $result['country'] = $uzbek ? 'UZBEKISTAN' : self::detectCountry($text);

        $rest = [];
        foreach (self::segments($text) as $segment) {
            $segment = self::stripCountry($segment);
            if ($segment === '') {
                continue;
            }

            if ($result['region'] === null && self::isRegion($segment)) {
                $result['region'] = $segment;
            } elseif ($result['district'] === null && self::matches($segment, self::DISTRICT_WORDS)) {
                $result['district'] = preg_replace('/^\d+[.\s]+/u', '', $segment);
            } elseif ($result['mahalla'] === null && self::matches($segment, self::MAHALLA_WORDS)) {
                $result['mahalla'] = $segment;
            } elseif ($result['city'] === null && preg_match(self::CITY_PREFIX, $segment)) {
                $result['city'] = $segment;
            } elseif (self::matches($segment, self::STREET_WORDS)) {
                $rest[] = $segment;
            } elseif ($result['city'] === null && self::matches($segment, self::CITY_WORDS)) {
                $result['city'] = $segment;
            } elseif ($result['city'] === null && $result['district'] === null && empty($rest) && self::isBareName($segment)) {
                // "TASHKENT, ...", "ANDIJAN REGION, ANDIJAN, UL..." — ko'chadan oldingi yolg'iz nom shahar bo'ladi
                $result['city'] = $segment;
            } else {
                $rest[] = $segment;
            }
        }

        if (! $uzbek && $result['city'] === null) {
            // chet el manzillarida shahar ko'pincha davlatdan oldingi oxirgi yolg'iz nom: "..., GUANGZHOU, CHINA"
            for ($i = count($rest) - 1; $i >= 0; $i--) {
                if (self::isBareName($rest[$i]) && ! self::matches($rest[$i], self::STREET_WORDS)) {
                    $result['city'] = $rest[$i];
                    array_splice($rest, $i, 1);
                    break;
                }
            }
        }

        $result['street'] = $rest ? implode(', ', $rest) : null;

        if ($result['country'] === 'UZBEKISTAN') {
            $result = self::normalizeUzbek($result);
        }

        return array_map(fn ($v) => $v === null || $v === '' ? null : mb_substr($v, 0, 255), $result);
    }

    private static function clean(?string $raw): string
    {
        $text = mb_strtoupper(trim((string) $raw));
        $text = preg_replace('/\s+/u', ' ', $text);
        // email va telefonlar manzilga tegishli emas
        $text = preg_replace('/(E-?MAIL|EL\.?\s*MAIL)?\s*:?\s*[\w.\-]+@[\w.\-]+/u', '', $text);
        $text = preg_replace('/(ТЕЛ|TEL)\.?\s*:?\s*[+\d\s\-()]{7,}/u', '', $text);
        $text = preg_replace('/^(ADDRESS|ADD)\s*:\s*/u', '', $text);
        // "ЧЕРЕЗ "FLOWDALE TRADING", ..., DUBAI" — vositachi kompaniya manzili, asosiy manzilga kirmaydi
        $text = preg_replace('/(ЧЕРЕЗ|Ч-З|Ч\/З|C\/O|VIA)\b.*$/u', '', $text);
        // pochta indeksi: "100070, ..." / "247767. ..."
        $text = preg_replace('/^\d{5,6}[\s,.]+/u', '', $text);
        $text = preg_replace('/,\s*\d{6}\s*,/u', ',', $text);

        // "ANDIJAN REGION. ASAKINSKY..." / "NAMANGAN REGION NAMANGAN DISTRICT ..." — vergul qo'yamiz
        $text = preg_replace('/(?<![A-ZА-ЯЁ])(REGION|DISTRICT|ОБЛАСТЬ|РАЙОН|ТУМАНИ|ВИЛОЯТИ)(?![A-ZА-ЯЁ])\.?\s+(?=[A-ZА-ЯЁ])/u', '$1, ', $text);
        // "APARTMENT 3. SYRDARYA REGION" — nuqtadan keyin viloyat/tuman boshlansa ajratamiz
        $text = preg_replace('/\.\s+(?=[A-ZА-ЯЁ\'\-]+ (REGION|DISTRICT|CITY)\b)/u', ', ', $text);
        // "RN.KHUDZHAOBOD" -> "RN KHUDZHAOBOD"
        $text = preg_replace('/\bRN\.(?=\S)/u', 'RN ', $text);
        // "FAYZ MFY STREET X" / "GULBOZOR MFY. STR. BERUNI" — mahalla va ko'chani ajratamiz
        $text = preg_replace('/\b(MFY|MFI|MSG|SSG|KFY)\b\.?\s+(?=(STREET|STR|ST|UL)\b)/u', '$1, ', $text);

        return trim($text, " ;,.");
    }

    /** @return string[] */
    private static function segments(string $text): array
    {
        $parts = preg_split('/[;,]|\s\/\s?|\/(?=[A-ZА-Я]{3,})/u', $text);

        return array_values(array_filter(array_map(
            fn ($p) => trim($p, " .;:-\"'"),
            $parts
        ), fn ($p) => $p !== ''));
    }

    private static function detectCountry(string $text): ?string
    {
        $found = null;
        $foundPos = -1;
        foreach (self::COUNTRIES as $pattern => $country) {
            // oxirgi uchragan davlat ustun (manzil oxirida yoziladi)
            if (preg_match_all('/'.$pattern.'/u', $text, $m, PREG_OFFSET_CAPTURE)) {
                $pos = end($m[0])[1];
                if ($pos > $foundPos) {
                    $found = $country;
                    $foundPos = $pos;
                }
            }
        }

        if ($found === null && preg_match('/\b('.self::CN_PROVINCES.')\b/u', $text)) {
            $found = 'CHINA';
        }

        return $found;
    }

    private static function stripCountry(string $segment): string
    {
        $segment = preg_replace('/\b(REPUBLIC OF|РЕСПУБЛИКА|RESPUBLIKASI|РОССИЙСКАЯ ФЕДЕРАЦИЯ|FEDERATION)\b/u', '', $segment);
        foreach (array_keys(self::COUNTRIES) as $pattern) {
            if ($pattern === '\bRK\b') {
                continue;
            }
            $segment = preg_replace('/(?<![A-ZА-ЯЁ])('.$pattern.')[A-ZА-ЯЁ]*/u', '', $segment);
        }

        return trim(preg_replace('/\s+/u', ' ', $segment), " .;:-/\"'");
    }

    private static function isRegion(string $segment): bool
    {
        return self::matches($segment, self::REGION_WORDS)
            || preg_match('/^('.self::CN_PROVINCES.')( PROVINCE)?$/u', $segment)
            || preg_match('/KARAKALPAK|QORAQALPOG|КАРАКАЛПАК/u', $segment);
    }

    private static function isBareName(string $segment): bool
    {
        return (bool) preg_match('/^[A-ZА-ЯЁ\'\- ]{3,25}$/u', $segment) && substr_count($segment, ' ') <= 1;
    }

    private static function matches(string $segment, string $words): bool
    {
        return (bool) preg_match('/(?<![A-ZА-ЯЁ])('.$words.')(?![A-ZА-ЯЁ])/u', $segment);
    }

    private static function normalizeUzbek(array $r): array
    {
        $regionSource = $r['region'] ?? $r['city'] ?? $r['district'] ?? '';
        $canonical = null;

        foreach (self::UZ_REGIONS as $pattern => $name) {
            if (preg_match('/'.$pattern.'/u', $regionSource)) {
                $canonical = $name;
                break;
            }
        }

        if ($canonical === 'TOSHKENT') {
            // viloyat so'zi bo'lsa — Toshkent viloyati, aks holda Toshkent shahri
            $canonical = $r['region'] !== null && self::matches($r['region'], self::REGION_WORDS)
                ? 'TOSHKENT VILOYATI'
                : 'TOSHKENT SHAHRI';
        }

        if ($canonical === null && $r['street'] !== null) {
            // "TASHKENT ST. MUKIMI 22" — viloyat markazi ko'cha bilan bitta bo'lakda
            foreach (self::UZ_REGIONS as $pattern => $name) {
                if (preg_match('/^('.$pattern.')\b/u', $r['street'])) {
                    $canonical = $name === 'TOSHKENT' ? 'TOSHKENT SHAHRI' : $name;
                    break;
                }
            }
        }

        if ($canonical === null) {
            $haystack = implode(' ', array_filter([$r['city'], $r['district'], $r['street']]));
            foreach (self::UZ_CITY_REGION as $pattern => $name) {
                if (preg_match('/'.$pattern.'/u', $haystack)) {
                    $canonical = $name;
                    break;
                }
            }
        }

        // "TASHKENT CITY" — bu viloyat darajasi (Toshkent shahri), shahar/tuman emas
        if ($canonical === 'TOSHKENT SHAHRI' && $r['city'] !== null && preg_match('/TASHKENT|TOSHKENT|ТАШКЕНТ|ТОШКЕНТ/u', $r['city'])) {
            $r['city'] = null;
        }

        $r['region'] = $canonical ?? $r['region'];

        return $r;
    }
}
