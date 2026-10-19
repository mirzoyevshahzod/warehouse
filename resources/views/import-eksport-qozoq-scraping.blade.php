@extends('layouts.app')

@section('title', "Qozog'iston — import-eksport")

@section('content')
    @include('partials.data-page', ['title' => "Qozog'iston — import-eksport", 'subtitle' => "Qozog'iston–Xitoy va Qozog'iston–Qirg'iziston chegara postlari"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/import-eksport-qozoq',
        importUrl: '/api/import-eksport-qozoq/import',
        exportUrl: '/api/import-eksport-qozoq/export',
        filters: [
            { param: 'boundary_name', label: "Chegara posti", type: 'select', options: ["Бахты - Покиту", "Достык - Алашанькоу", "Калжат - Дулаты", "Майкапчагай - Зимунай", "Нур Жолы - Хоргос", "Айша-Биби - Чон-Какпа", "Аухатты - Кенбулын", "Карасу - Ак-Тилек", "Кеген - Каркыра", "Кордай - Ак-Жол", "Сыпатай Батыр - Чалдыбар", "Сартобе - Токмок"] },
            { param: 'from_date', label: "Sanadan", type: 'date' },
            { param: 'to_date', label: "Sanagacha", type: 'date' },
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
            { key: 'company_name', label: "Tashkilot" },
            { key: 'date_and_time', label: "Sana", format: 'hourRange' },
            { key: 'status', label: "Holati" },
        ],
    });
</script>
@endpush
