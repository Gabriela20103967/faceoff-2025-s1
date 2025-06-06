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
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Cluster ID that the ClassSession belongs to
            $table->foreignId('cluster_id')->constrained()->onDelete('cascade');
            // Staff ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
