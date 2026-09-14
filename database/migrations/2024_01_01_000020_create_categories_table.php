<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            // 'item' ou 'skill' : une même table sert aux deux domaines
            // plutôt que de dupliquer item_categories / skill_categories.
            $table->string('type');
            $table->timestamps();

            $table->unique(['slug', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
