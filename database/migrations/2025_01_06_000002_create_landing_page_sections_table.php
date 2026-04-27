<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_sections', function (Blueprint $table) {
            $table->id();
            $table->string('section_type', 50);
            $table->string('title', 255)->nullable();
            $table->text('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('cta_text', 100)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_sections');
    }
};
