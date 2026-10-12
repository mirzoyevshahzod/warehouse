<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vazifa qaysi ma'lumotlarga tegishli: import (uzbekistan_imports) yoki eksport (uzbekistan_exports).
     * Mavjud vazifalar — import ma'lumotlariniki.
     */
    public function up(): void
    {
        Schema::table('uzbekistan_import_tasks', function (Blueprint $table) {
            $table->string('dataset', 10)->default('import')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('uzbekistan_import_tasks', function (Blueprint $table) {
            $table->dropColumn('dataset');
        });
    }
};
