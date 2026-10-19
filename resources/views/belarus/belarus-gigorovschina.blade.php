@extends('layouts.app')

@section('title', "Belarus — Grigorovschina")

@section('content')
    @include('partials.data-page', ['title' => "Belarus — Grigorovschina", 'subtitle' => "Grigorovschina chegara posti: kutish zonasidagi (ZO) elektron navbat ma'lumotlari"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/belarus-gigorovschina',
        importUrl: '/api/belarus-gigorovschina/import',
        exportUrl: '/api/belarus-gigorovschina/export',
        exportName: () => 'belarus_gigorovschina_export.xlsx',
        filters: [
            { param: 'call_order', label: "Qo'ng'iroq tartibi", type: 'text', placeholder: "Qo'ng'iroq tartibi" },
            { param: 'car_number', label: "Ro'yxatdan o'tish raqami", type: 'text', placeholder: "Ro'yxat raqami" },
            { param: 'registration_date', label: "Ro'yxatdan o'tgan sana", type: 'date' },
            { param: 'status_changed', label: "Holat o'zgargan sana", type: 'date' },
        ],
        columns: [
            { key: 'call_order', label: "Qo'ng'iroq tartibi" },
            { key: 'queue_type', label: "Navbat turi" },
            { key: 'car_number', label: "Ro'yxatdan o'tish raqami" },
            { key: 'date_of_registration_in_the_zo', label: "ZO'da ro'yxatdan o'tgan", format: 'date' },
            { key: 'status_changed', label: "Holat o'zgardi", format: 'date' },
            { key: 'status', label: "Holat" },
            { key: 'company_name', label: "Tashkilot" },
        ],
    });
</script>
@endpush
