<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });

        // Existing active accounts keep their prior access and are not marked as
        // awaiting approval when this feature is deployed.
        DB::table('users')
            ->where('is_active', true)
            ->whereNotNull('role_id')
            ->update(['approved_at' => DB::raw('COALESCE(updated_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('approved_at');
        });
    }
};
