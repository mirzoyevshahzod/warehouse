@extends('layouts.app')

@section('title', "Qozog'iston — import")

@section('content')
    @include('partials.data-page', ['title' => "Qozog'iston — import", 'subtitle' => "O'zbekiston–Qozog'iston chegara postlari orqali import"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/import-qozoq',
        importUrl: '/api/import-qozoq/import',
        exportUrl: '/api/import-qozoq/export',
        filters: [
            { param: 'boundary_name', label: "Chegara posti", type: 'select', options: ["Атамекен - Гулистан", "Б. Конысбаева - Яллама", "Казыгурт - Майский", "Тажен - Каракалпакстан"] },
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
            { key: 'date_and_time', label: "Sana", format: 'hourRange' },
            { key: 'status', label: "Holati" },
        ],
    });
</script>
@endpush
