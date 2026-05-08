<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['待機中', '稼働中', '休み'])->default('待機中');
            $table->string('car')->nullable();
            $table->string('phone', 20)->nullable();
            $table->time('return_at')->nullable(); // 稼働中の戻り予定時刻
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
