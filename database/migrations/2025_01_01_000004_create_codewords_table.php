<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codewords', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('word');
            $table->enum('discount_type', ['fixed', 'percent'])->default('fixed');
            $table->unsignedInteger('discount_value')->default(0);
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codewords');
    }
};
