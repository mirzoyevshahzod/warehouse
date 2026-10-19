@extends('layouts.app')

@section('title', "Belarus — Benyakoni")

@section('content')
    @include('partials.data-page', ['title' => "Belarus — Benyakoni", 'subtitle' => "Benyakoni chegara posti: kutish zonasidagi (ZO) elektron navbat ma'lumotlari"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/belarus-benyakoni',
        importUrl: '/api/belarus-benyakoni/import',
        exportUrl: '/api/belarus-benyakoni/export',
        exportName: () => 'belarus_benyakoni_export.xlsx',
        filters: [
            { param: 'call_order', label: "Qo'ng'iroq tartibi", type: 'text', placeholder: "Qo'ng'iroq tartibi" },
            { param: 'car_number', label: "Ro'yxatdan o'tish raqami", type: 'text', placeholder: "Ro'yxat raqami" },
            { param: 'registration_date_from', label: "Ro'yxatdan o'tgan: sanadan", type: 'date' },
            { param: 'registration_date_to', label: "Ro'yxatdan o'tgan: sanagacha", type: 'date' },
            { param: 'status_changed', label: "Holat o'zgargan sana", type: 'date' },
        ],
        columns: [
            { key: 'call_order', label: "Qo'ng'iroq tartibi" },
            { key: 'queue_type', label: "Navbat turi" },
            { key: 'car_number', label: "Ro'yxatdan o'tish raqami" },
            { key: 'phone', label: "Telefon" },
            { key: 'date_of_registration_in_the_zo', label: "ZO'da ro'yxatdan o'tgan", format: 'date' },
            { key: 'status_changed', label: "Holat o'zgardi", format: 'date' },
            { key: 'status', label: "Holat" },
            { key: 'company_name', label: "Tashkilot" },
        ],
    });
</script>
@endpush
