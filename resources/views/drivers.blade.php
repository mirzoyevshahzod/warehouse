@extends('layouts.app')

@section('title', "Haydovchilar")

@section('content')
    @include('partials.data-page', ['title' => "Haydovchilar", 'subtitle' => "Haydovchilar bazasi: telefon, mashina va tirkama raqami, o'lchamlar"])
@endsection

@push('scripts')
<script>
    DataPage.init({
        api: '/api/drivers',
        importUrl: '/api/import-drivers',
        exportUrl: '/api/export-drivers',
        exportName: () => 'drivers_export.xlsx',
        filters: [
            { param: 'full_name', label: "Ism familiya", type: 'text', placeholder: 'Ism familiya' },
            // API "number" parametrini kutadi (eski sahifa car_number yuborgani uchun bu filtr ishlamas edi)
            { param: 'number', label: "Mashina raqami", type: 'text', placeholder: 'Mashina raqami' },
            { param: 'phone', label: "Telefon", type: 'text', placeholder: 'Telefon raqami' },
            { param: 'trailer_number', label: "Tirkama raqami", type: 'text', placeholder: 'Tirkama raqami' },
        ],
        columns: [
            { key: 'full_name', label: "Ism familiya" },
            { key: 'number', label: "Mashina raqami" },
            { key: 'phone', label: "Telefon" },
            { key: 'length', label: "Uzunlik", align: 'right' },
            { key: 'width', label: "Kenglik", align: 'right' },
            { key: 'height', label: "Balandlik", align: 'right' },
            { key: 'trailer_number', label: "Tirkama raqami" },
            { key: 'capacity', label: "Sig'im", align: 'right' },
            { key: 'lifting', label: "Yuk hajmi", align: 'right' },
            { key: 'brand', label: "Brend" },
        ],
    });
</script>
@endpush
