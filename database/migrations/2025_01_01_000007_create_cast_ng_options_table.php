<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cast_ng_options', function (Blueprint $table) {
            $table->foreignId('cast_id')->constrained('casts')->cascadeOnDelete();
            $table->foreignId('option_id')->constrained('options')->cascadeOnDelete();
            $table->primary(['cast_id', 'option_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cast_ng_options');
    }
};
