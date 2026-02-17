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
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique()->index(); // Unique ID from IMAP
            $table->string('subject')->nullable();
            $table->string('from_address')->index();
            $table->string('from_name')->nullable();
            $table->string('to_address')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->enum('status', ['unread', 'read', 'archived'])->default('unread')->index();
            
            // AI Analysis Fields
            $table->string('intent', 50)->nullable()->index();
            $table->string('sentiment', 20)->nullable();
            $table->float('urgency_score')->default(0);
            $table->json('ai_metadata')->nullable(); // Store category, trust score, etc.
            
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            
            $table->timestamp('email_date')->index(); // Date from email header
            $table->timestamps();
        });

        Schema::create('email_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_message_id')->constrained('email_messages')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type');
            $table->bigInteger('size');
            $table->boolean('is_po')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_attachments');
        Schema::dropIfExists('email_messages');
    }
};
