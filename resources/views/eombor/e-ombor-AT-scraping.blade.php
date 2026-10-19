@extends('layouts.app')

@section('title', "E-Ombor AT")

@section('content')
    @include('partials.data-page', ['title' => "E-Ombor AT", 'subtitle' => "Avtotransport orqali kelgan yuklar: hujjat, avto raqami, qabul qiluvchi va holat"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/at-e-ombor',
        importUrl: '/api/at-e-ombor/import',
        exportUrl: '/api/at-e-ombor/export',
        dataKey: 'eombors',
        exportName: () => 'AT-e-ombor.xlsx',
        filters: [
            { param: 'document_number', label: "Hujjat raqami", type: 'text', placeholder: 'Hujjat raqami' },
            { param: 'custom_date', label: "Sana", type: 'date' },
            { param: 'inn', label: "INN", type: 'text', placeholder: 'INN' },
            { param: 'transport_number', label: "Avto raqami", type: 'text', placeholder: 'Avto raqami' },
        ],
        columns: [
            { key: 'document_number', label: "Hujjat raqami" },
            { key: 'custom_code', label: "Kod" },
            { key: 'custom_date', label: "Sana", format: 'date' },
            { key: 'transport_number', label: "Avto raqami" },
            { key: 'gross_weight', label: "Brutto vazn", align: 'right' },
            { key: 'inn', label: "INN" },
            { key: 'recipient_name', label: "Qabul qiluvchi" },
            { key: 'delivery_post', label: "Yetkazib berish posti" },
            { key: 'delivery_date', label: "Yetkazib berish sanasi", format: 'date' },
            { key: 'arrival_place', label: "Kelish joyi" },
            { key: 'status', label: "Holat" },
        ],
    });
</script>
@endpush
