<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'username')) {
            return;
        }

        $hasUniqueUsernameIndex = collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['username']
        );

        if ($hasUniqueUsernameIndex) {
            return;
        }

        $hasDuplicates = DB::table('users')
            ->select('username')
            ->whereNotNull('username')
            ->groupBy('username')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasDuplicates) {
            throw new RuntimeException('Cannot add the users.username unique index because duplicate usernames exist. Resolve the duplicates, then rerun the migration.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username', 'users_username_unique');
        });
    }

    public function down(): void
    {
        // Keep username unique: it is part of the account identity contract.
    }
};
