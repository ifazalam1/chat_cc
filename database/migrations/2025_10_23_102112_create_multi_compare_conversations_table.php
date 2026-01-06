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
        Schema::create('multi_compare_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->json('selected_models'); // Store the selected models for this conversation
            $table->timestamps();
            
            $table->index(['user_id', 'updated_at']);
        });
        
        Schema::create('multi_compare_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('multi_compare_conversations')->onDelete('cascade');
            $table->string('role'); // 'user' or 'assistant'
            $table->longText('content');
            $table->string('model')->nullable(); // For assistant messages, which model generated this
            $table->json('all_responses')->nullable(); // Store all model responses for user messages
            $table->timestamps();
            
            $table->index(['conversation_id', 'created_at']);
        });
        
        Schema::create('multi_compare_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('multi_compare_messages')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('multi_compare_attachments');
        Schema::dropIfExists('multi_compare_messages');
        Schema::dropIfExists('multi_compare_conversations');
    }
};