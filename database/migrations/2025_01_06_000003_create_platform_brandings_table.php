<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_brandings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name', 255)->default('GrowthPedia');
            $table->string('tagline', 500)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('favicon_url', 500)->nullable();
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#1E40AF');
            $table->text('footer_text')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_brandings');
    }
};
