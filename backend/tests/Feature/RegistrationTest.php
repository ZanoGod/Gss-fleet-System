<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_inactive_roleless_account_without_authenticating_it(): void
    {
        $this->postJson('/register', ['name' => 'Fleet User', 'username' => 'fleet-user', 'email' => 'fleet@example.test', 'password' => 'long-test-password', 'password_confirmation' => 'long-test-password'])
            ->assertCreated()
            ->assertJsonMissingPath('user.password')
            ->assertJsonPath('message', 'Registration successful. Your account is pending administrator approval.')
            ->assertJsonPath('user.is_active', false)
            ->assertJsonPath('user.role_id', null);
        $user = User::where('username', 'fleet-user')->firstOrFail();
        $this->assertTrue(Hash::check('long-test-password', $user->password));
        $this->assertFalse($user->is_active);
        $this->assertNull($user->role_id);
        $this->assertGuest('web');
        $this->getJson('/api/user')->assertUnauthorized();
        $this->assertDatabaseCount('admin_user_links', 0);
    }

    public function test_registration_cannot_sign_in_until_an_administrator_grants_both_role_and_activation(): void
    {
        $this->postJson('/register', ['name' => 'Fleet User', 'username' => 'fleet-user', 'email' => 'fleet@example.test', 'password' => 'long-test-password', 'password_confirmation' => 'long-test-password'])->assertCreated();
        $user = User::where('username', 'fleet-user')->firstOrFail();

        $this->postJson('/login', ['login' => 'fleet-user', 'password' => 'long-test-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'Your account is pending administrator approval. Contact an administrator.');
        $this->assertGuest('web');

        $user->update(['role_id' => \App\Models\Role::where('slug', 'staff')->value('id')]);
        $this->postJson('/login', ['login' => 'fleet-user', 'password' => 'long-test-password'])->assertUnprocessable();
        $this->assertGuest('web');

        $user->update(['is_active' => true]);
        $this->postJson('/login', ['login' => 'fleet-user', 'password' => 'long-test-password'])->assertOk()->assertJsonPath('user.id', $user->id);

        $this->postJson('/logout')->assertOk();
        $user->update(['role_id' => null]);
        $this->postJson('/login', ['login' => 'fleet-user', 'password' => 'long-test-password'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.login.0', 'Your account is pending administrator approval. Contact an administrator.');
        $this->assertGuest('web');
    }

    public function test_registration_rejects_unconfirmed_password(): void
    {
        $this->postJson('/register', ['name' => 'Fleet User', 'username' => 'fleet-user', 'email' => 'fleet@example.test', 'password' => 'long-test-password', 'password_confirmation' => 'different-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_rejects_duplicate_username_and_email(): void
    {
        User::factory()->create([
            'username' => 'fleet-user',
            'email' => 'fleet@example.test',
        ]);

        $this->postJson('/register', [
            'name' => 'Another Fleet User',
            'username' => 'fleet-user',
            'email' => 'another@example.test',
            'password' => 'long-test-password',
            'password_confirmation' => 'long-test-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');

        $this->postJson('/register', [
            'name' => 'Another Fleet User',
            'username' => 'another-user',
            'email' => 'fleet@example.test',
            'password' => 'long-test-password',
            'password_confirmation' => 'long-test-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }
}
