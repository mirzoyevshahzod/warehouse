@extends('layouts.app')

@section('title', "Mintrans")

@section('content')
    @include('partials.data-page', ['title' => "Mintrans", 'subtitle' => "Transport vazirligi litsenziyalari: avtomobillar, korxonalar va faoliyat turi"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/mintrans',
        importUrl: '/api/mintrans/import',
        exportUrl: '/api/mintrans/export',
        exportName: () => 'mintrans_export.xlsx',
        // API paginate javobini to'g'ridan-to'g'ri qaytaradi
        dataKey: null,
        filters: [
            { param: 'license_number', label: "Litsenziya", type: 'text', placeholder: 'Litsenziya' },
            { param: 'date_given', label: "Berilgan sana", type: 'date' },
            { param: 'state_number', label: "Davlat raqami", type: 'text', placeholder: 'Davlat raqami' },
            { param: 'company_name', label: "Korxona nomi", type: 'text', placeholder: 'Korxona nomi' },
        ],
        columns: [
            { key: 'model', label: "Rusumi" },
            { key: 'load_capacity', label: "Yuk ko'tarish", align: 'right' },
            { key: 'license_number', label: "Litsenziya varaqasi" },
            { key: 'state_number', label: "Davlat raqami" },
            { key: 'company_name', label: "Korxona" },
            { key: 'type_of_activity', label: "Faoliyat turi" },
            { key: 'transport_type', label: "Transport turi" },
            { key: 'cargo_type', label: "Yuk turi" },
            { key: 'date_given', label: "Berilgan sana", format: 'date' },
            { key: 'status', label: "Holati" },
            { key: 'inn', label: "INN" },
            { key: 'territorial_management', label: "Hududiy boshqarma" },
        ],
    });
</script>
@endpush
