<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O'zbekiston import savdo ma'lumotlari (bojxona deklaratsiyalari, har qator — bitta tovar pozitsiyasi).
     */
    public function up(): void
    {
        Schema::create('uzbekistan_imports', function (Blueprint $table) {
            $table->id();

            // Deklaratsiya
            $table->date('date')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('declaration_date')->nullable();
            $table->string('regime_type', 20)->nullable();
            $table->string('regime_code', 10)->nullable();
            $table->string('declaration_number', 50)->nullable();
            $table->unsignedInteger('item_no')->nullable();

            // Importyor
            $table->string('importer_name')->nullable();
            $table->string('importer_name_2')->nullable();
            $table->text('importer_address')->nullable();
            $table->string('importer_region')->nullable();
            $table->string('importer_district')->nullable();
            $table->string('importer_village')->nullable();
            $table->string('importer_inn', 20)->nullable();
            $table->string('importer_phone', 50)->nullable();

            // Shartnoma egasi
            $table->string('contract_holder')->nullable();
            $table->string('contract_holder_code', 20)->nullable();
            $table->text('contract_holder_address')->nullable();
            $table->string('contract_holder_region')->nullable();
            $table->string('contract_holder_district')->nullable();
            $table->string('contract_holder_street')->nullable();

            // Eksportyor
            $table->string('exporter_standard_name')->nullable();
            $table->string('exporter_local_name')->nullable();
            $table->text('exporter_address')->nullable();
            $table->string('exporter_country')->nullable();
            $table->string('exporter_region')->nullable();
            $table->string('exporter_city')->nullable();
            $table->string('exporter_street')->nullable();
            $table->string('exporter_code', 50)->nullable();
            $table->string('exporter_phone', 50)->nullable();

            // Tovar
            $table->string('hs_code', 20)->nullable();
            $table->text('product_description')->nullable();
            $table->string('brand')->nullable();
            $table->decimal('quantity', 18, 3)->nullable();
            $table->string('quantity_unit', 20)->nullable();
            $table->decimal('net_weight_kg', 18, 3)->nullable();
            $table->decimal('gross_weight_kg', 18, 3)->nullable();

            // Summalar
            $table->decimal('invoice_amount_fc', 20, 3)->nullable();
            $table->decimal('customs_amount_usd', 20, 3)->nullable();
            $table->decimal('customs_amount_fc', 20, 3)->nullable();
            $table->decimal('statistical_amount_usd', 20, 3)->nullable();
            $table->decimal('total_invoice_amount_fc', 20, 3)->nullable();
            $table->decimal('total_customs_amount_fc', 20, 3)->nullable();
            $table->decimal('total_tariffs', 20, 3)->nullable();
            $table->string('contract_currency', 10)->nullable();
            $table->decimal('usd_exchange_rate', 18, 4)->nullable();
            $table->decimal('fc_exchange_rate', 18, 4)->nullable();

            // Savdo va shartnoma
            $table->string('trade_mode', 10)->nullable();
            $table->string('trade_mode_code', 10)->nullable();
            $table->date('contract_date')->nullable();
            $table->string('contract_no')->nullable();
            $table->string('contract_signing_country', 10)->nullable();
            $table->string('contract_char_code', 10)->nullable();

            // Davlatlar
            $table->string('origin_country')->nullable();
            $table->string('delivery_place')->nullable();
            $table->string('shipment_country')->nullable();
            $table->string('trading_country')->nullable();
            $table->string('transport_country')->nullable();
            $table->string('transit_country')->nullable();
            $table->string('destination_country')->nullable();
            $table->date('transit_date')->nullable();

            // Deklarant (broker)
            $table->string('declaring_unit')->nullable();
            $table->string('declaring_unit_code', 20)->nullable();
            $table->text('applicant_address')->nullable();
            $table->string('applicant_region')->nullable();
            $table->string('applicant_district')->nullable();
            $table->string('applicant_street')->nullable();
            $table->string('declarant_name')->nullable();
            $table->string('applicant_code', 20)->nullable();
            $table->string('applicant_email')->nullable();
            $table->string('applicant_phone', 50)->nullable();
            $table->string('applicant_contract_no')->nullable();
            $table->string('applicant_city')->nullable();

            // Transport
            $table->string('transport_type', 20)->nullable();
            $table->string('overseas_transport_type', 20)->nullable();
            $table->string('vehicle_number', 30)->nullable();
            $table->string('trailer_number', 30)->nullable();
            $table->string('border_vehicle_number', 50)->nullable();

            // Bojxona
            $table->string('customs_code', 10)->nullable();
            $table->string('border_customs')->nullable();
            $table->text('customs_office')->nullable();
            $table->string('responsible_person_name')->nullable();
            $table->string('responsible_person_position')->nullable();
            $table->string('responsible_person_phone', 20)->nullable();

            $table->timestamps();

            $table->unique(
                ['customs_code', 'registration_date', 'declaration_number', 'item_no'],
                'uzbekistan_imports_unique_record'
            );
            $table->index('registration_date');

            // filtrlar
            $table->index('importer_name');
            $table->index('importer_inn');
            $table->index('exporter_standard_name');
            $table->index('transport_type');
            $table->index('origin_country');
            $table->index('trade_mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzbekistan_imports');
    }
};
