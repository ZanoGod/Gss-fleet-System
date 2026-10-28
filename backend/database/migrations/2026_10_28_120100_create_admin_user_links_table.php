<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admin_user_links')) {
            throw new RuntimeException('admin_user_links already exists; review its schema before continuing.');
        }

        Schema::create('admin_user_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('admin_id')->unique();
            $table->foreign('admin_id')->references('id')->on('admins')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Automatic rollback is disabled for identity compatibility links.');
    }
};
