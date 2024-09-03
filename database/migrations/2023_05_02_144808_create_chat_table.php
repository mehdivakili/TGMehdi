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
            $table->bigInteger('chat_id')->unsigned()->index();
            $table->bigInteger('bot_name')->unsigned()->index()->default('default');
            $table->string('type');
            $table->string('status')->default('.start.');
            $table->longText('temp_text')->default('{}');
            $table->timestamps();
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
