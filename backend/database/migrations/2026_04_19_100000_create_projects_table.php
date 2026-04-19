<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->string('name_ar', 255);
            $table->string('name_en', 255);
            $table->text('description')->nullable();
            $table->string('city', 100);
            $table->string('district', 100)->nullable();
            $table->decimal('location_lat', 10, 7)->nullable();
            $table->decimal('location_lng', 10, 7)->nullable();
            $table->enum('status', ['draft', 'planning', 'in_progress', 'on_hold', 'completed', 'closed'])->default('draft');
            $table->enum('type', ['residential', 'commercial', 'infrastructure']);
            $table->decimal('budget_estimated', 15, 2)->nullable();
            $table->decimal('budget_actual', 15, 2)->nullable()->default(0.00);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('owner_id');
            $table->index('status');
            $table->index('type');
            $table->index('city');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
