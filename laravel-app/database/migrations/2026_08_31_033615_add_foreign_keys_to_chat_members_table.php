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
        Schema::table('chat_members', function (Blueprint $table) {
            $table->foreign('chat_id')
                ->references('id')
                ->on('chats');

            $table->foreign('user_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_members', function (Blueprint $table) {
            $table->dropForeign(['chat_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
