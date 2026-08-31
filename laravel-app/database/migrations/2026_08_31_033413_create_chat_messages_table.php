<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('creator_id');
            $table->enum('type', ['text', 'file', 'voice', 'video', 'image'])->default('text');
            $table->longText('content')->nullable(); // For text messages
            $table->string('file_name')->nullable(); // Original filename
            $table->string('file_path')->nullable(); // Path relative to storage
            $table->string('mime_type')->nullable(); // e.g., audio/mpeg, video/mp4
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('chat_id');
            $table->index('creator_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
