@extends('layouts.app')

@section('title', "Qozog'iston — tekshirilganlar (eksport)")

@section('content')
    @include('partials.data-page', ['title' => "Qozog'iston — tekshirilganlar (eksport)", 'subtitle' => "Tekshirilgan avtomobillar: telefon, tashkilot, sana va holat"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/checked-qozoq',
        importUrl: '/api/checked-qozoq/import',
        exportUrl: '/api/checked-qozoq/export',
        filters: [
            { param: 'boundary_name', label: "Chegara posti", type: 'select', options: ["Акбалшык – Воскресенское", "Аксай – Илек", "Алимбет – Орск", "Амангельды – Невольное", "Аят – Николаевка", "Бидаик – Одесское", "Жайсан – Сагарчин", "Жана Жол – Петухово", "Жаныбек – Вишневка", "Жезкент – Горняк", "Желкуар – Мариинский", "Кайрак – Бугристое", "Каракога – Исилькуль", "Карашатау – Светлый", "Кондыбай – Комсомольский", "Косак – Павловка", "Коянбай – Малиновое Озеро", "Курмангазы – Караузек", "Кызыл Жар – Казанское", "Найза – Павловка (Славгород)", "Орда – Полынный", "Сырым – Маштаково", "Таскала – Озинки", "Убаган – Звериноголовское", "Убе – Михайловка", "Урлютобе – Ольховка", "Шаган – Теплое", "Шарбакты – Кулунда", "Ауыл – Веселоярск"] },
            { param: 'date_from', label: "Sanadan", type: 'date', suffix: ' 00:00:00' },
            { param: 'date_to', label: "Sanagacha", type: 'date', suffix: ' 23:59:59' },
            { param: 'status', label: "Holat", type: 'text', placeholder: 'Holat' },
            { param: 'car_number', label: "Avto raqam", type: 'text', placeholder: 'Avto raqam' },
            { param: 'company_name', label: "Tashkilot nomi", type: 'text', placeholder: 'Tashkilot nomi' },
            { param: 'car_type', label: "Avto turi", type: 'select', options: [
                { value: 'uz', label: "Faqat O'zbekiston" },
                { value: 'foreign', label: "Faqat O'zbekiston emas" },
            ] },
        ],
        columns: [
            { key: 'boundary_name', label: "Chegara posti" },
            { key: 'car_number', label: "Avto raqami" },
            { key: 'phone', label: "Telefon" },
            { key: 'company_name', label: "Tashkilot" },
            { key: 'date_and_time', label: "Sana", format: 'datetime' },
            { key: 'status', label: "Holati" },
        ],
    });
</script>
@endpush
