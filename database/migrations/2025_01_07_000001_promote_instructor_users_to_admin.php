<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $count = DB::table('users')->where('role', 'instructor')->count();

        DB::table('users')
            ->where('role', 'instructor')
            ->update(['role' => 'admin']);

        if ($count > 0) {
            Log::info("Promoted {$count} instructor user(s) to admin role.");
        }
    }

    public function down(): void
    {
        // Cannot reliably reverse — we don't know which admins were formerly instructors.
        // This is intentionally a one-way migration.
        Log::warning('Rollback of instructor-to-admin promotion is not supported. Manual intervention required.');
    }
};
