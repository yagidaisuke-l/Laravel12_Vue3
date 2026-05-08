<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['出勤予定', '体調不良', '無断欠勤', 'その他欠勤'])->default('出勤予定');
            $table->string('absent_note')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['cast_id', 'date']); // 1キャスト1日1シフト
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
