<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 顧客 → キャストのNG（顧客がキャストをNG指定）
        Schema::create('customer_ng_casts', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->primary(['customer_id', 'cast_id']);
        });

        // キャスト → 顧客のNG（キャストが顧客をNG指定）
        Schema::create('cast_ng_customers', function (Blueprint $table) {
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->primary(['cast_id', 'customer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_ng_customers');
        Schema::dropIfExists('customer_ng_casts');
    }
};
