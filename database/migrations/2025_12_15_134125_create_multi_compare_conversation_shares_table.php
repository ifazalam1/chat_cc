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
        Schema::create('multi_compare_conversation_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('multi_compare_conversations')->onDelete('cascade');
            $table->string('share_token', 64)->unique();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_public')->default(true); // Public means anyone with link can view
            $table->timestamp('expires_at')->nullable(); // Optional expiration
            $table->integer('view_count')->default(0);
            $table->timestamps();
            
            $table->index(['share_token', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_compare_conversation_shares');
    }
};
