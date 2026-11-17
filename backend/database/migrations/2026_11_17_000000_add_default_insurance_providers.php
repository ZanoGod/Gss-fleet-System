<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $timestamp = now();

            foreach (['GGI', 'AYASUMPO', 'IKBZ'] as $name) {
                $providers = DB::table('insurance_providers');

                if (! (clone $providers)->where('name', $name)->exists()) {
                    $providers->insert([
                        'name' => $name,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                }
            }
        });
    }

    public function down(): void
    {
        // Keep provider records because policies may now reference them.
    }
};
