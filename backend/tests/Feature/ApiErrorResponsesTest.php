<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;
use PDOException;
use RuntimeException;

class ApiErrorResponsesTest extends TestCase
{
    public function test_missing_database_returns_safe_database_error_and_logs_sanitized_context(): void
    {
        Log::spy();
        Route::get('/api/testing/database-error', function () {
            $pdo = new PDOException('Unknown database private_db; SQL select secret from users');
            $pdo->errorInfo = ['HY000', 1049, 'Unknown database private_db'];

            throw new QueryException('mysql', 'select secret from users', ['private-binding'], $pdo);
        });

        $response = $this->getJson('/api/testing/database-error');

        $response->assertStatus(503)
            ->assertJsonPath('code', 'DATABASE_UNAVAILABLE')
            ->assertJsonPath('message', 'Database connection unavailable. Please contact the administrator.')
            ->assertDontSee('private_db')
            ->assertDontSee('secret')
            ->assertDontSee('private-binding');

        Log::shouldHaveReceived('error')->once()->with(
            'Database connection unavailable.',
            \Mockery::on(fn (array $context): bool => isset($context['exception_type'], $context['sql_state'], $context['driver_code']) && ! isset($context['sql'], $context['bindings'], $context['database']))
        );
    }

    public function test_unrelated_server_errors_use_generic_safe_message(): void
    {
        Route::get('/api/testing/server-error', function () {
            throw new RuntimeException('SQL select private_data from /srv/internal/app.php');
        });

        $this->getJson('/api/testing/server-error')
            ->assertStatus(500)
            ->assertJsonPath('code', 'SERVER_ERROR')
            ->assertJsonPath('message', 'An unexpected server error occurred. Please try again later.')
            ->assertDontSee('private_data')
            ->assertDontSee('/srv/internal');
    }
}
