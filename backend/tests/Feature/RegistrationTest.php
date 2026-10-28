<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_hashes_password_and_starts_a_session(): void
    {
        $this->postJson('/register', ['name'=>'Fleet User','username'=>'fleet-user','email'=>'fleet@example.test','password'=>'long-test-password','password_confirmation'=>'long-test-password'])
            ->assertCreated()->assertJsonMissingPath('user.password');
        $user=User::where('username','fleet-user')->firstOrFail();
        $this->assertTrue(Hash::check('long-test-password',$user->password));
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('admin_user_links',0);
    }

    public function test_registration_rejects_unconfirmed_password(): void
    {
        $this->postJson('/register', ['name'=>'Fleet User','username'=>'fleet-user','email'=>'fleet@example.test','password'=>'long-test-password','password_confirmation'=>'different-password'])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseCount('users',0);
    }
}
