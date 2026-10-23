<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withCredentials();
        config(['app.key' => 'base64:'.base64_encode(str_repeat('t', 32)), 'session.driver' => 'database', 'session.lifetime' => 1440]);
        // Exercise real CSRF validation, normally bypassed by Laravel tests.
        $this->app->instance('env', 'local');
        $this->withHeaders(['Origin' => 'http://127.0.0.1:5173', 'Accept' => 'application/json']);
    }

    private function csrf(): string
    {
        $this->get('/sanctum/csrf-cookie')->assertNoContent();
        $this->withCookie(config('session.cookie'), session()->getId());
        return session()->token();
    }

    public function test_cookie_login_restoration_rotation_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'session-test', 'password' => Hash::make('test-password')]);
        $token = $this->csrf();
        $oldId = session()->getId();
        $response = $this->postJson('/login', ['login' => 'session-test', 'password' => 'test-password'], ['X-CSRF-TOKEN' => $token]);
        $response->assertOk()->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('token')->assertJsonMissingPath('user.password');
        $this->assertNotSame($oldId, session()->getId());
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertGreaterThan(time() + 86300, $cookie->getExpiresTime());
        $this->withCookie(config('session.cookie'), session()->getId());
        session()->flush(); // Each real HTTP request starts with an empty in-memory store.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertOk()->assertJsonPath('user.id', $user->id);
        $sessionId = session()->getId();
        $this->postJson('/logout', [], ['X-CSRF-TOKEN' => session()->token()])->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->withCookie(config('session.cookie'), session()->getId());
        session()->flush(); // Each real HTTP request starts with an empty in-memory store.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_validation_invalid_credentials_and_csrf_protection(): void
    {
        $this->postJson('/login', ['login' => 'nobody', 'password' => 'wrong'])->assertStatus(419)->assertJsonPath('code', 'CSRF_INVALID')->assertJsonPath('message', 'Your session is invalid or expired. Please refresh and try again.');
        $token = $this->csrf();
        $this->postJson('/login', [], ['X-CSRF-TOKEN' => $token])->assertUnprocessable()->assertJsonValidationErrors(['login', 'password']);
        $this->postJson('/login', ['login' => 'nobody', 'password' => 'wrong'], ['X-CSRF-TOKEN' => $token])->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_ERROR')->assertJsonPath('errors.login.0', 'Incorrect username or password.');
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_database_expiration_and_activity_renewal(): void
    {
        $user = User::factory()->create(['password' => Hash::make('test-password')]);
        $token = $this->csrf();
        $this->postJson('/login', ['login' => $user->email, 'password' => 'test-password'], ['X-CSRF-TOKEN' => $token])->assertOk();
        $id = session()->getId();
        DB::table('sessions')->where('id', $id)->update(['last_activity' => time() - 86300]);
        $this->withCookie(config('session.cookie'), session()->getId());
        session()->flush(); // Each real HTTP request starts with an empty in-memory store.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertOk();
        $this->assertGreaterThan(time() - 5, DB::table('sessions')->where('id', $id)->value('last_activity'));
        DB::table('sessions')->where('id', $id)->update(['last_activity' => time() - 86401]);
        $this->withCookie(config('session.cookie'), session()->getId());
        session()->flush(); // Each real HTTP request starts with an empty in-memory store.
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/user')->assertUnauthorized();
    }

    public function test_login_is_rate_limited(): void
    {
        $token = $this->csrf();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/login', ['login' => 'missing', 'password' => 'wrong'], ['X-CSRF-TOKEN' => $token])->assertUnprocessable();
        }
        $this->postJson('/login', ['login' => 'missing', 'password' => 'wrong'], ['X-CSRF-TOKEN' => $token])->assertStatus(429);
    }
}

