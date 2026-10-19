@extends('layouts.app')

@section('title', "E-Ombor AVIA")

@section('content')
    @include('partials.data-page', ['title' => "E-Ombor AVIA", 'subtitle' => "Havo yo'li orqali kelgan yuklar: yuk varaqasi, parvoz va qabul qiluvchi"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/avia-e-ombor',
        importUrl: '/api/avia-eombor/import',
        exportUrl: '/api/avia-e-ombor/export',
        dataKey: 'eombors',
        exportName: () => 'AVIA-e-ombor.xlsx',
        filters: [
            { param: 'air_waybill_number', label: "Yuk varaqasi raqami", type: 'text', placeholder: 'Yuk varaqasi' },
            { param: 'flight_number', label: "Parvoz raqami", type: 'text', placeholder: 'Parvoz raqami' },
            { param: 'registration_date', label: "Ro'yxatdan o'tish sanasi", type: 'date' },
            { param: 'border_customs_post_code', label: "Bojxona posti kodi", type: 'text', placeholder: 'Post kodi' },
        ],
        columns: [
            { key: 'air_waybill_number', label: "Yuk varaqasi raqami" },
            { key: 'flight_number', label: "Parvoz raqami" },
            { key: 'registration_date', label: "Ro'yxatdan o'tish sanasi", format: 'date' },
            { key: 'consignee', label: "Yuk qabul qiluvchi" },
            { key: 'total_net_weight', label: "Sof og'irlik", align: 'right' },
            { key: 'border_customs_post_code', label: "Bojxona posti kodi" },
        ],
    });
</script>
@endpush
