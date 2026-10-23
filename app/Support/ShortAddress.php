<?php

namespace App\Support;

/**
 * To'liq manzildan qisqa ko'rinish: "DAVLAT, VILOYAT, SHAHAR".
 * Zanjeer formatidagi eksport (App\Exports\ZanjeerExport) uchun — uz-trade-scraper loyihasidagi
 * App\Services\Dqxx\AddressParser bilan bir xil, shunda ikkala tizimdagi "Место погрузки/выгрузки" mos keladi.
 * Indeks, ko'cha, uy, ofis, vositachi (";" dan keyingi "VIA ...") kabi tafsilotlar tashlanadi.
 *
 * Misollar:
 *   "141401, RUSSIA, KHIMKI, MOSCOW REGION, POBEDY ST., 11;"        => "RUSSIA, MOSCOW REGION, KHIMKI"
 *   "630110, NOVOSIBIRSK, PISEMSKOGO ST., 24/4, 2ND FLOOR, RUSSIAN FEDERATION.;" => "RUSSIA, NOVOSIBIRSK"
 *   "TASHKENT, ALMAZAR DISTRICT, YANGI ALMAZAR STR.;"               => "UZBEKISTAN, TASHKENT, ALMAZAR"
 */
class ShortAddress
{
    /** Davlat nomlari => standart nom. */
    private const COUNTRIES = [
        'RUSSIAN FEDERATION' => 'RUSSIA', 'RUSSIA' => 'RUSSIA', 'R/F' => 'RUSSIA', 'RF' => 'RUSSIA', 'РОССИЯ' => 'RUSSIA',
        'REPUBLIC OF UZBEKISTAN' => 'UZBEKISTAN', 'UZBEKISTAN' => 'UZBEKISTAN', 'УЗБЕКИСТАН' => 'UZBEKISTAN',
        'PEOPLE\'S REPUBLIC OF CHINA' => 'CHINA', 'P.R. CHINA' => 'CHINA', 'PRC' => 'CHINA', 'CHINA' => 'CHINA',
        'REPUBLIC OF KAZAKHSTAN' => 'KAZAKHSTAN', 'KAZAKHSTAN' => 'KAZAKHSTAN',
        'REPUBLIC OF TURKEY' => 'TURKEY', 'TURKIYE' => 'TURKEY', 'TÜRKIYE' => 'TURKEY', 'TURKEY' => 'TURKEY',
        'KYRGYZ REPUBLIC' => 'KYRGYZSTAN', 'KYRGYZSTAN' => 'KYRGYZSTAN', 'GERMANY' => 'GERMANY',
        'REPUBLIC OF BELARUS' => 'BELARUS', 'BELARUS' => 'BELARUS', 'BELORUSSIA' => 'BELARUS', 'BYELORUSSIA' => 'BELARUS', 'БЕЛАРУСЬ' => 'BELARUS', 'БЕЛОРУССИЯ' => 'BELARUS',
        'REPUBLIC OF LITHUANIA' => 'LITHUANIA', 'LITHUANIA' => 'LITHUANIA', 'LIETUVA' => 'LITHUANIA', 'ЛИТВА' => 'LITHUANIA',
        'REPUBLIC OF LATVIA' => 'LATVIA', 'LATVIA' => 'LATVIA', 'LATVIJA' => 'LATVIA', 'ЛАТВИЯ' => 'LATVIA',
        'REPUBLIC OF SINGAPORE' => 'SINGAPORE', 'SINGAPORE' => 'SINGAPORE', 'СИНГАПУР' => 'SINGAPORE',
        'REPUBLIC OF KOREA' => 'KOREA', 'SOUTH KOREA' => 'KOREA', 'KOREA' => 'KOREA', 'INDIA' => 'INDIA', 'IRAN' => 'IRAN',
        'UNITED ARAB EMIRATES' => 'UAE', 'UAE' => 'UAE', 'TAJIKISTAN' => 'TAJIKISTAN', 'TURKMENISTAN' => 'TURKMENISTAN',
    ];

    /** Bunday bo'laklar shahar/viloyat emas (ko'cha, bino, xona, tashkilot...). */
    private const DETAIL_PATTERN = '/(*UCP)\b(ST|STR|STREET|UL|ULITSA|AVE|AVENUE|PROSPEKT|PR|BOULEVARD|BLVD|SHOSSE|HIGHWAY|LANE|PASSAGE|TRACT|ROAD|RD|SQUARE|EMBANKMENT|MASSIF|MFY|KFY|KFI|QFY|SSG|MSG|MAHALLA|MAHALLASI|KO\x27CHASI|KOCHASI|KO\x27CHA|UY|DOM|MAVZESI|MASSIVI|BERK|QUARTER|MICRODISTRICT|HOUSE|BUILDING|BLDG|FLOOR|ROOM|OFFICE|OFF|APT|POM|KV|LETTER|WAREHOUSE|P\.?O\.? BOX|BOX|ZONE|TERRITORY|SEZ|SUBDIVISION|COMPANY|JSC|LLC|LTD|VIA|THROUGH|POSTAL|TEL|DEAD END|D|УЛ|УЛИЦА|ПР|ПРОСПЕКТ|ПЕР|ПЕРЕУЛОК|Д|ДОМ|КВ|ОФИС|ПОМ|СТР|КОРП|ШОССЕ|БУЛЬВАР)\b\.?/u';

    private const REGION_PATTERN = '/(*UCP)\b(REGION|OBLAST|OBL|ОБЛ|ОБЛАСТЬ|КРАЙ|РЕСПУБЛИКА|KRAI|KRAY|PROVINCE|VILOYATI|AUTONOMOUS|REPUBLIC OF \p{L}+|TATARSTAN|BASHKORTOSTAN)\b/u';

    private const DISTRICT_PATTERN = '/(*UCP)\s*\b(URBAN DISTRICT|DISTRICT|RAYON|TUMANI|RN|R-N|РАЙОН|Р-Н)\b\.?/u';

    private const SETTLEMENT_PATTERN = "/(*UCP)\\b(VILLAGE|SETTLEMENT|TOWNSHIP|SELO|POSELOK|QISHLOG'I|QISHLOGI|QISHLOQ|OVULI)\\b/u";

    private const MAX_PARTS = 3;

    public function short(?string $address, ?string $defaultCountry = null): ?string
    {
        if ($address === null || trim($address) === '') {
            return $defaultCountry ? strtoupper($defaultCountry) : null;
        }

        $address = mb_strtoupper(str_replace(['`', '’', 'ʻ', 'ʼ', '‘'], "'", $address));
        $country = $this->country(explode(';', $address)[0])
            ?? $this->country($address)
            ?? ($defaultCountry ? strtoupper($defaultCountry) : null);

        $region = $city = $district = $settlement = null;

        foreach ($this->parts($address) as $part) {
            if (preg_match(self::REGION_PATTERN, $part)) {
                $region ??= $part;
            } elseif (preg_match(self::DISTRICT_PATTERN, $part)) {
                $district ??= trim(preg_replace(self::DISTRICT_PATTERN, '', $part));
            } elseif (preg_match(self::SETTLEMENT_PATTERN, $part)) {
                $settlement ??= trim(preg_replace([self::SETTLEMENT_PATTERN, '/^OF\s+/u', '/\s+(RURAL|URBAN)$/u'], '', trim(preg_replace(self::SETTLEMENT_PATTERN, '', $part)))) ?: null;
            } else {
                $city ??= $part;
            }
        }

        // "IRKUTSK REGION, IRKUTSK" => "IRKUTSK"
        if ($region && $city && preg_replace('/\s+(REGION|OBLAST|KRAI|KRAY)$/u', '', $region) === $city) {
            $region = null;
        }

        $parts = array_values(array_unique(array_filter([$country, $region, $city ?? $settlement, $district])));

        return $parts ? implode(', ', array_slice($parts, 0, self::MAX_PARTS)) : null;
    }

    private function country(string $address): ?string
    {
        foreach (self::COUNTRIES as $name => $standard) {
            if (preg_match('/(^|[^\p{L}])'.preg_quote($name, '/').'([^\p{L}]|$)/u', $address)) {
                return $standard;
            }
        }

        return null;
    }

    /**
     * Asosiy manzil (birinchi ";" gacha) bo'laklari — tozalangan, faqat joy nomlari.
     *
     * @return list<string>
     */
    private function parts(string $address): array
    {
        $main = trim(explode(';', $address)[0]) ?: $address;

        $main = preg_replace([
            '/\([^)]*\)|"[^"]*"/u',                                 // qavs va qo'shtirnoq ichidagilar
            '/\bST\.?\s+PETERSBURG\b/u',                            // "ST. PETERSBURG" ko'cha emas
            '/\b(REGION|OBLAST|KRAI|DISTRICT|VILLAGE|FEDERATION)\b(?!\s*,)/u', // vergulsiz yozilgan manzillarni bo'laklash
        ], [' ', 'SAINT PETERSBURG', '$1,'], $main);

        $countryNames = array_keys(self::COUNTRIES);

        return collect(preg_split('/[,;]/u', $main))
            ->map(fn (string $part) => trim(preg_replace(['/\b[A-Z]{2}-\d{4,6}\b/u', '/\b\d{5,6}\b/u'], '', $part), " .\t\n"))
            ->map(fn (string $part) => trim(preg_replace(['/^(CITY OF|TOWN OF|OF|G\.|Г\.)\s*/u', '/\s+(CITY|TOWN|SHAHRI|SHAHARCHASI|SHAHARCHA|SHAHAR|SH)$/u'], '', $part)))
            ->reject(fn (string $part) => $part === ''
                || preg_match('/\d/', $part)
                || preg_match(self::DETAIL_PATTERN, $part)
                || in_array($part, $countryNames, true)
                || preg_match('/^(VILLAGE|SETTLEMENT|DISTRICT|REGION)$/u', $part))
            ->values()
            ->all();
    }
}
