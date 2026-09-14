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
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            // Relation polymorphique : une réservation porte sur un Item OU un Skill.
            $table->string('reservable_type');
            $table->unsignedBigInteger('reservable_id');
            $table->string('status'); // pending | accepted | declined | completed | cancelled
            $table->text('message')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['reservable_type', 'reservable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
