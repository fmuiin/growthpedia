<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('parent_comment_id')->nullable()->constrained('comments');
            $table->text('content');
            $table->boolean('is_flagged')->default(false);
            $table->string('flag_reason', 255)->nullable();
            $table->foreignId('flagged_by')->nullable()->constrained('users');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
