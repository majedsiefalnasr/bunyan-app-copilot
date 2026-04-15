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
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('name_ar', 100);
            $table->string('name_en', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 50)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key for self-referential parent
            $table->foreign('parent_id')
                ->references('id')
                ->on('categories')
                ->onDelete('set null');

            // Indexes for query optimization
            $table->index('parent_id');
            $table->index(['parent_id', 'sort_order', 'is_active']);
            $table->index('deleted_at');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
