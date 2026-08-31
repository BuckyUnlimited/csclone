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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_id');
            $table->enum('type', ['personal', 'group'])->default('personal');
            $table->string('name')->nullable(); // For group chats
            $table->text('description')->nullable(); // For group chat description
            $table->string('avatar')->nullable(); // For group chat avatar/image

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('name');
            $table->index('creator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
