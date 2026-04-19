<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('company_name_ar');
            $table->string('company_name_en');
            $table->string('commercial_reg', 100)->unique();
            $table->string('tax_number', 50)->nullable();
            $table->string('city', 100);
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 20);
            $table->enum('verification_status', ['pending', 'verified', 'suspended'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->decimal('rating_avg', 8, 2)->default(0.00);
            $table->unsignedInteger('total_ratings')->default(0);
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('logo', 500)->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index('verification_status');
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_profiles');
    }
};
