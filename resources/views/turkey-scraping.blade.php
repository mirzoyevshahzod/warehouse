@extends('layouts.app')

@section('title', "Turkiya")

@section('content')
    @include('partials.data-page', ['title' => "Turkiya", 'subtitle' => "Turkiya chegara postlariga kirish navbati: avtomobillar, tashkilotlar va sana"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/turkey',
        importUrl: '/api/turkey/import',
        exportUrl: '/api/turkey/export',
        exportName: () => 'turkey_export.xlsx',
        filters: [
            { param: 'company_name', label: "Tashkilot nomi", type: 'text', placeholder: 'Tashkilot nomi' },
            { param: 'date_from', label: "Sanadan", type: 'date' },
            { param: 'date_to', label: "Sanagacha", type: 'date' },
            { param: 'car_number', label: "Avtomobil raqami", type: 'text', placeholder: 'Avto raqam' },
            { param: 'input_sequence_number', label: "Kirish tartib raqami", type: 'text', placeholder: 'Tartib raqami' },
        ],
        columns: [
            { key: 'ordinal_number', label: "Tartib raqami", align: 'right' },
            { key: 'input_sequence_number', label: "Kirish tartib raqami", align: 'right' },
            { key: 'car_number', label: "Avtomobil" },
            { key: 'phone', label: "Telefon" },
            { key: 'date', label: "Sana", format: 'date' },
            { key: 'entrance', label: "Kirish joyi" },
            { key: 'company_name', label: "Tashkilot" },
        ],
    });
</script>
@endpush
