<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers');
            $table->foreignId('cast_id')->constrained('casts');
            $table->foreignId('codeword_id')->nullable()->constrained('codewords')->nullOnDelete();
            $table->date('date');
            $table->time('time');
            $table->unsignedSmallInteger('duration'); // 分単位
            $table->string('area')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('base_price')->default(0);
            $table->unsignedInteger('designation_fee')->default(0);
            $table->unsignedInteger('options_total_price')->default(0);
            $table->unsignedInteger('transport_fee')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('total_price')->default(0);
            $table->enum('reservation_status', ['仮予約', '確定', 'キャンセル'])->default('確定');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['date', 'cast_id']);
            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
