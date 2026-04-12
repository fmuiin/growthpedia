<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['learner', 'instructor', 'admin'])->default('learner')->after('password');
            $table->boolean('is_suspended')->default(false)->after('email_verified_at');
            $table->integer('failed_login_attempts')->default(0)->after('is_suspended');
            $table->timestamp('locked_until')->nullable()->after('failed_login_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_suspended', 'failed_login_attempts', 'locked_until']);
        });
    }
};
