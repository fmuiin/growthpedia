<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Handle orphaned courses (courses referencing deleted/non-existent users).
        // This runs AFTER migration 000002 renamed instructor_id → created_by.
        $firstAdminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId !== null) {
            $orphanedCount = DB::table('courses')
                ->whereNotIn('created_by', DB::table('users')->select('id'))
                ->count();

            if ($orphanedCount > 0) {
                DB::table('courses')
                    ->whereNotIn('created_by', DB::table('users')->select('id'))
                    ->update(['created_by' => $firstAdminId]);

                Log::warning("Reassigned {$orphanedCount} orphaned course(s) to admin user ID {$firstAdminId}.");
            }
        }

        // Step 2: Verify no instructor users remain (safety check — migration 000001 should have handled this).
        $remainingInstructors = DB::table('users')->where('role', 'instructor')->count();
        if ($remainingInstructors > 0) {
            DB::table('users')->where('role', 'instructor')->update(['role' => 'admin']);
            Log::warning("Found {$remainingInstructors} remaining instructor user(s) during enum migration — promoted to admin.");
        }

        // Step 3: Update the role CHECK constraint to remove 'instructor'.
        // The column is already VARCHAR with a CHECK constraint from the original enum migration.
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite does not support ALTER TABLE DROP/ADD CONSTRAINT.
            // CHECK constraints in SQLite are defined at table creation and cannot be modified.
            // For SQLite (used in tests), we skip constraint modification — the application
            // layer enforces valid roles via validation rules.
        } else {
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");

            // Step 4: Add the new CHECK constraint allowing only 'learner' and 'admin'.
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('learner', 'admin'))");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            // Remove the CHECK constraint and restore the original enum with 'instructor'.
            DB::statement("ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('learner', 'instructor', 'admin'))");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'learner'");
        }
    }
};
