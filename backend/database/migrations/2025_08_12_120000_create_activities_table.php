<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('price');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('adult_agent_commission')->default(0);
            $table->unsignedInteger('adult_staff_commission')->default(0);
            $table->unsignedInteger('adult_boat_boy_commission')->default(0);
            $table->unsignedInteger('adult_total_commission')->default(0);
            $table->unsignedInteger('adult_admin_share')->default(0);
            $table->unsignedInteger('child_agent_commission')->default(0);
            $table->unsignedInteger('child_staff_commission')->default(0);
            $table->unsignedInteger('child_boat_boy_commission')->default(0);
            $table->unsignedInteger('child_total_commission')->default(0);
            $table->unsignedInteger('child_admin_share')->default(0);
            $table->unsignedTinyInteger('gst_rate')->default(18);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};


