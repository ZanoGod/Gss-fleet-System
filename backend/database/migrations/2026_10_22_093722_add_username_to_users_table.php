<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable();
            });
        }

        $this->ensureUniqueUsernameIndex();
    }

    public function down(): void
    {
        if ($this->hasNamedUniqueUsernameIndex()) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_username_unique');
            });
        }
    }

    private function ensureUniqueUsernameIndex(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'username') || $this->hasUniqueUsernameIndex()) {
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

    private function hasUniqueUsernameIndex(): bool
    {
        return collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => ($index['unique'] ?? false) && ($index['columns'] ?? []) === ['username']
        );
    }

    private function hasNamedUniqueUsernameIndex(): bool
    {
        return collect(Schema::getIndexes('users'))->contains(
            fn (array $index): bool => ($index['name'] ?? null) === 'users_username_unique'
                && ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['username']
        );
    }
};
