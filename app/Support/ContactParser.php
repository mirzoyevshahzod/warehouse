<?php

namespace App\Support;

/**
 * "Customs Office" ustunidagi erkin matndan mas'ul shaxsning
 * F.I.Sh., lavozimi va telefon raqamini ajratib oladi.
 *
 * Masalan: "ДИРЕКТОР КАБИЛОВ ШЕРЗОД МАХАМАДЖОНОВИЧ, ТЕЛ.:+998911209099. ОБЯЗУЮСЬ ..."
 *   => full_name: КАБИЛОВ ШЕРЗОД МАХАМАДЖОНОВИЧ, position: ДИРЕКТОР, phone: +998911209099
 */
class ContactParser
{
    /** Kalit so'z (regex) => kanonik lavozim. Uzunroqlari birinchi turadi. */
    private const POSITIONS = [
        'КОММЕРЧЕСКИЙ ДИРЕКТОР' => 'КОММЕРЧЕСКИЙ ДИРЕКТОР',
        'ГЕНЕРАЛЬНЫЙ ДИРЕКТОР|GENERAL DIRECTOR' => 'ГЕНЕРАЛЬНЫЙ ДИРЕКТОР',
        'ЗАМ\.?\s*ДИРЕКТОРА|ЗАМЕСТИТЕЛЬ ДИРЕКТОРА' => 'ЗАМ. ДИРЕКТОРА',
        'ГЛАВНЫЙ БУХГАЛТЕР|ГЛ\.\s*БУХГАЛТЕР' => 'ГЛАВНЫЙ БУХГАЛТЕР',
        'СПЕЦИАЛИСТ ПО СЕРТИФИКАЦИИ' => 'СПЕЦИАЛИСТ ПО СЕРТИФИКАЦИИ',
        'МЕНЕДЖЕР ВЭД|МЕНДЖЕР ВЭД' => 'МЕНЕДЖЕР ВЭД',
        'СПЕЦИАЛИСТ ПО ВЭД|СПЕЦ\.?\s*ВЭД|СПЕЦИАЛИСТ ВЭД' => 'СПЕЦИАЛИСТ ВЭД',
        'ДИРЕКТОР|DIRECTOR|DIREKTOR|РАХБАР|РУКОВОДИТЕЛЬ' => 'ДИРЕКТОР',
        'БУХГАЛТЕР' => 'БУХГАЛТЕР',
        'МЕНЕДЖЕР|МЕНДЖЕР|MANAGER' => 'МЕНЕДЖЕР',
        'ЧТЗ ПРЕДСТАВИТЕЛЬ|ПРЕДСТАВИТЕЛЬ|ПРЕДСТАИТЕЛЬ|ПРЕД-ТЕЛЬ|ПРЕДСТ-ЛЬ|ПРЕД\.|REPRESENTATIVE|ВАКИЛ|ЮК ВАКИЛИ' => 'ПРЕДСТАВИТЕЛЬ',
        'ВЛАДЕЛЕЦ|ВЛАДЕЛИЦ|ВЛАДЕЛЬЦА|OWNER' => 'ВЛАДЕЛЕЦ',
        'УЧРЕДИТЕЛЬ|FOUNDER|ТАЪСИСЧИ' => 'УЧРЕДИТЕЛЬ',
        'ДОВЕРЕННОЕ ЛИЦО|ДОВЕРЕННОСТЬ\s*:|ДОВЕРНОСТЬ\s*:|ПО ДОВЕРЕННОСТИ' => 'ДОВЕРЕННОЕ ЛИЦО',
        'ЭКСПЕДИТОР' => 'ЭКСПЕДИТОР',
        'ВОДИТЕЛЬ|ХАЙДОВЧИ|DRIVER' => 'ВОДИТЕЛЬ',
        'ЛОГИСТ' => 'ЛОГИСТ',
        'ФИЗ\.?\s*ЛИЦО|ФИЗИЧЕСКОЕ ЛИЦО|ЖИСМОНИЙ ШАХС' => 'ФИЗИЧЕСКОЕ ЛИЦО',
        '\bИП\b|ЯТТ' => 'ИП',
    ];

    /** Shu so'zdan boshlab bo'lak oxirigacha ism emas */
    private const NOISE = 'ПАСПОРТ|ПАССП|ПАСП|П-Т|ID\s*КАРТА|ID\s*CARD|\bID\b|ТЕЛЕФОН|ТЕЛ|TEL|PHONE|ОБЯЗУЮСЬ|ДОВЕРЕННОСТЬ|ДОВ\.|ДАТА|СОГ\b|E-?MAIL|ВЫДАН|СРОК|№';

    /** So'z (familiya/ism) yoki bosh harf, ortidan bosh harflar: "ХУСАНОВ", "Ж.", "SH.T.", "MALIKOV.B.N" */
    private const TOKEN = '[A-ZА-ЯЁЎҚҒҲ][A-ZА-ЯЁЎҚҒҲ\'`ʻʼ‘’?\-]*(?:\.\s?(?:[A-ZА-ЯЁЎҚҒҲ]{1,2}(?![A-ZА-ЯЁЎҚҒҲ\'])\.?)*)?';

    /** Ism ichida uchramaydigan so'zlar */
    private const STOP_WORDS = ['ООО', 'OOO', 'LLC', 'LTD', 'MCHJ', 'МЧЖ', 'ЧП', 'АО', 'АЖ', 'ГТД', 'ИМ', 'ОТ', 'ДО', 'Г', 'ЛИЦО', 'ВЭД', 'ПО', 'ЧЕРЕЗ', 'ГОВД', 'РОВД', 'IIV', 'ОБЛ', 'ДОВЕРЕННОСТИ', 'ПРЕДОСТАВИТЬ', 'ПРЕДСТАВИТЬ', 'ПОЛНУЮ', 'ТОВАРОВ'];

    /**
     * @return array{full_name:?string, position:?string, phone:?string}
     */
    public static function parse(?string $raw): array
    {
        $text = mb_strtoupper(trim(preg_replace('/\s+/u', ' ', (string) $raw)));

        if ($text === '') {
            return ['full_name' => null, 'position' => null, 'phone' => null];
        }

        return [
            'full_name' => self::name($text),
            'position' => self::position($text),
            'phone' => self::phone($text),
        ];
    }

    public static function phone(string $text): ?string
    {
        // +998 90 777-33-33 / +998(99)000-07-89 / +99897 735 59 50
        if (preg_match('/\+?\s*998[\s\-()]*(\d{2})[\s\-()]*(\d{3})[\s\-]*(\d{2})[\s\-]*(\d{2})/u', $text, $m)) {
            return '+998'.$m[1].$m[2].$m[3].$m[4];
        }

        // "ТЕЛ.905177755", "ТЕЛ. (90) 035 15 56", "КАДИРОВ Д.   883567777"
        if (preg_match('/(?<![\dA-ZА-Я])\(?((?:9\d|88|33|50|77|20|55|71))\)?[\s\-]?(\d{3})[\s\-]?(\d{2})[\s\-]?(\d{2})(?!\d)/u', $text, $m)) {
            return '+998'.$m[1].$m[2].$m[3].$m[4];
        }

        return null;
    }

    public static function position(string $text): ?string
    {
        $best = null;
        $bestPos = PHP_INT_MAX;

        foreach (self::POSITIONS as $pattern => $name) {
            if (preg_match('/'.$pattern.'/u', $text, $m, PREG_OFFSET_CAPTURE) && $m[0][1] < $bestPos) {
                // matnda birinchi uchragan lavozim olinadi
                $best = $name;
                $bestPos = $m[0][1];
            }
        }

        return $best;
    }

    public static function name(string $text): ?string
    {
        foreach (explode(';', $text) as $part) {
            $candidate = self::nameFromPart($part);
            if ($candidate !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private static function nameFromPart(string $part): ?string
    {
        $part = preg_replace('/ОТВЕТСТВЕННОСТЬ ЗА ПРЕДОСТАВЛЕННЫЕ СВЕДЕНИЯ НЕСЕТ/u', ',', $part);
        // "NET DRIP МЧЖ" — tirnoqsiz kompaniya nomi
        $part = preg_replace('/(?:[A-Z0-9\-]+\s){1,3}(МЧЖ|MCHJ|XK|LLC)\b/u', ',', $part);
        // qavs ichidagi pasport ma'lumotlari, qo'shtirnoqdagi kompaniya nomlari
        $part = preg_replace('/\([^)]*\)?/u', ' ', $part);
        $part = preg_replace('/(ООО|OOO|LLC|ЧП|MCHJ|АО|ИП)?\s*("[^"]*"?|\?[^?]{3,}\?)/u', ' ', $part);

        // lavozimlarni olib tashlaymiz
        foreach (array_keys(self::POSITIONS) as $pattern) {
            $part = preg_replace('/'.$pattern.'/u', ',', $part);
        }

        // har bo'lakni "shovqin" so'zidan keyin kesamiz
        $chunks = [];
        foreach (preg_split('/[,:]|\s-\s|(?<=[A-ZА-ЯЁ\'Ї]{3})\.\s(?=[A-ZА-Я]{3,})/u', $part) as $chunk) {
            $chunk = preg_split('/'.self::NOISE.'/u', $chunk)[0];
            $chunks[] = trim($chunk, " -");
        }

        $nameRe = '/'.self::TOKEN.'(?:\s+'.self::TOKEN.'){0,4}/u';

        foreach ($chunks as $chunk) {
            if (! preg_match($nameRe, $chunk, $m)) {
                continue;
            }

            $words = array_values(array_filter(
                preg_split('/\s+/u', trim($m[0], ' .')),
                fn ($w) => ! in_array(rtrim($w, '.'), self::STOP_WORDS, true)
            ));

            // kamida bitta to'liq so'z (familiya) va ism yoki bosh harf bo'lishi kerak
            $surname = array_filter($words, fn ($w) => preg_match('/^[^.]{3,}/u', $w));
            if ($surname && (count($words) >= 2 || str_contains($words[0], '.'))) {
                return mb_substr(implode(' ', $words), 0, 255);
            }
        }

        return null;
    }
}
