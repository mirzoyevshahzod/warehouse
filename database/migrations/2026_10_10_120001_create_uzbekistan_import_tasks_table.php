<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Queue orqali bajariladigan import/export vazifalari va ularning holati.
     */
    public function up(): void
    {
        Schema::create('uzbekistan_import_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);                       // import | export
            $table->string('status', 20)->default('pending'); // pending | processing | completed | failed
            $table->foreignId('user_id')->nullable()->index();
            $table->string('original_name')->nullable();      // yuklangan fayl nomi
            $table->string('file_path')->nullable();          // storage/app/private ichidagi yo'l
            $table->json('params')->nullable();               // export filtrlari
            $table->unsignedInteger('total_rows')->nullable();
            $table->unsignedInteger('processed_rows')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzbekistan_import_tasks');
    }
};
