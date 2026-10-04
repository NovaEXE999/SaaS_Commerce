<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Venta generada automáticamente al completar una cita (una cita = máximo una venta)
            $table->foreignId('appointment_id')->nullable()->after('user_id')->constrained('appointments')->nullOnDelete();
            $table->unique('appointment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['appointment_id']);
            $table->dropConstrainedForeignId('appointment_id');
        });
    }
};
