<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('multi_compare_conversations', function (Blueprint $table) {
            $table->string('hex_code', 16)->unique()->after('id')->nullable();
            $table->index('hex_code');
        });

         // Generate hex codes for existing records
        DB::table('multi_compare_conversations')->whereNull('hex_code')->orderBy('id')->each(function ($conversation) {
            DB::table('multi_compare_conversations')
                ->where('id', $conversation->id)
                ->update(['hex_code' => bin2hex(random_bytes(8))]);
        });
        
        // Make hex_code non-nullable after backfilling
        Schema::table('multi_compare_conversations', function (Blueprint $table) {
            $table->string('hex_code', 16)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('multi_compare_conversations', function (Blueprint $table) {
            $table->dropIndex(['hex_code']);
            $table->dropColumn('hex_code');
        });
    }
};
