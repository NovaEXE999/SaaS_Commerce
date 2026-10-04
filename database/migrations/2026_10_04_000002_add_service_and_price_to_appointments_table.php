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
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('user_id')->constrained('services')->nullOnDelete();
            // Snapshot del precio del servicio al momento de agendar (no cambia si el catálogo se actualiza)
            $table->decimal('price', 10, 2)->default(0)->after('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn('price');
        });
    }
};
