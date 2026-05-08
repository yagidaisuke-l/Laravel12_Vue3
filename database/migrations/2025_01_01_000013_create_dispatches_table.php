<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispatches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->enum('type', ['送り', '迎え'])->default('送り');
            $table->enum('status', ['未配車', '配車済', '完了'])->default('未配車');
            $table->dateTime('scheduled_at')->nullable(); // 配車予定日時
            $table->dateTime('dispatched_at')->nullable(); // 実際に出発した日時
            $table->dateTime('completed_at')->nullable(); // 完了日時
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispatches');
    }
};
