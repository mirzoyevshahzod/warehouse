<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O'zbekiston eksport savdo ma'lumotlari (har qator — bitta tovar pozitsiyasi).
     */
    public function up(): void
    {
        Schema::create('uzbekistan_exports', function (Blueprint $table) {
            $table->id();

            // Faylda deklaratsiya raqami yo'q — qayta yuklashda takrorlanmaslik uchun
            // qator qiymatlaridan hisoblangan kalit (hash + fayl ichidagi takror tartibi)
            $table->string('row_key', 60)->unique();

            $table->date('date')->nullable();

            // Tomonlar
            $table->string('exporter_name')->nullable();          // O'zbekistondagi sotuvchi
            $table->string('exporter_inn', 20)->nullable();
            $table->string('importer_name')->nullable();          // chet eldagi xaridor

            // Tovar
            $table->string('hs_code', 20)->nullable();
            $table->text('product_description')->nullable();
            $table->decimal('quantity', 18, 3)->nullable();
            $table->string('quantity_unit', 20)->nullable();
            $table->decimal('net_weight_kg', 18, 3)->nullable();

            // Summalar
            $table->decimal('invoice_amount_usd', 20, 5)->nullable();
            $table->decimal('invoice_unit_price_usd', 20, 5)->nullable();
            $table->decimal('customs_value_usd', 20, 5)->nullable();
            $table->decimal('statistical_value_usd', 20, 3)->nullable();

            // Yo'nalish
            $table->string('destination_country')->nullable();
            $table->string('destination')->nullable();
            $table->string('transit_country')->nullable();

            // Rejim va transport
            $table->string('customs_system')->nullable();
            $table->string('supervision_method', 20)->nullable();
            $table->string('transaction_method', 10)->nullable();
            $table->string('transportation_method', 20)->nullable();
            $table->string('transportation_method_code', 10)->nullable();
            $table->string('regime_type', 20)->nullable();
            $table->string('regime_code', 10)->nullable();
            $table->string('declaration_type_name')->nullable();
            $table->string('previous_declaration_type')->nullable();
            $table->string('previous_declaration_type_name')->nullable();
            $table->string('freight_transport_type_code', 20)->nullable();
            $table->string('delivery_condition_code', 10)->nullable();
            $table->string('delivery_condition_name')->nullable();

            $table->timestamps();

            $table->index('date');

            // filtrlar
            $table->index('exporter_name');
            $table->index('exporter_inn');
            $table->index('importer_name');
            $table->index('destination_country');
            $table->index('transportation_method');
            $table->index('transaction_method');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzbekistan_exports');
    }
};
