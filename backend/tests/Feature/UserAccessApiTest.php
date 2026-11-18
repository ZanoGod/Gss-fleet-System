<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserAccessApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role_id' => Role::where('slug', 'administrator')->value('id')]);
        $this->actingAs($this->admin, 'web');
    }

    public function test_user_list_summary_and_roles_require_permissions(): void
    {
        $this->assertTrue($this->admin->hasPermission('users.view'));
        User::factory()->create(['name' => 'Searchable Staff', 'username' => 'searchable', 'role_id' => Role::where('slug', 'staff')->value('id')]);
        $this->getJson('/api/users?search=Searchable')->assertOk()->assertJsonPath('data.0.name', 'Searchable Staff');
        $this->getJson('/api/users/summary')->assertOk()->assertJsonPath('data.total', 2);
        $this->getJson('/api/users/roles')->assertOk()->assertJsonStructure(['data', 'permissions'])
            ->assertJson(['permissions' => ['dashboard.view' => 'Dashboard']]);

        $staff = User::factory()->create(['role_id' => Role::where('slug', 'staff')->value('id')]);
        $this->actingAs($staff, 'web')->getJson('/api/users')->assertForbidden();
    }

    public function test_system_admin_slug_can_access_users_and_roles_endpoints(): void
    {
        $role = Role::create(['name' => 'Admin Alias', 'slug' => 'admin', 'permissions' => []]);
        $role->forceFill(['is_system' => true])->save();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user, 'web')->getJson('/api/users')->assertOk();
        $this->getJson('/api/users/summary')->assertOk();
        $this->getJson('/api/users/roles')->assertOk();
    }

    public function test_add_edit_and_remove_user_with_validated_credentials(): void
    {
        $staffRole = Role::where('slug', 'staff')->value('id');
        $id = $this->postJson('/api/users', [
            'name' => 'New Teammate', 'username' => 'new-teammate', 'email' => 'new@example.test',
            'password' => 'temporary-password', 'role_id' => $staffRole, 'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.name', 'New Teammate')->json('data.id');
        $user = User::findOrFail($id);
        $this->assertTrue(Hash::check('temporary-password', $user->password));
        $this->putJson('/api/users/'.$id, [
            'name' => 'Updated Teammate', 'username' => 'new-teammate', 'email' => 'new@example.test',
            'password' => '', 'role_id' => Role::where('slug', 'administrator')->value('id'), 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.name', 'Updated Teammate')->assertJsonPath('data.role.slug', 'administrator');
        $this->deleteJson('/api/users/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('users', ['id' => $id]);
    }

    public function test_access_changes_disable_sign_in_and_keep_one_active_administrator(): void
    {
        $staff = User::factory()->create(['username' => 'deactivated', 'password' => Hash::make('test-password'), 'role_id' => Role::where('slug', 'staff')->value('id')]);
        $this->patchJson('/api/users/'.$staff->id.'/access', ['role_id' => $staff->role_id, 'is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->postJson('/login', ['login' => 'deactivated', 'password' => 'test-password'])->assertUnprocessable();

        $this->actingAs($this->admin, 'web');
        $this->patchJson('/api/users/'.$this->admin->id.'/access', ['role_id' => Role::where('slug', 'staff')->value('id'), 'is_active' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('role_id');
        $this->deleteJson('/api/users/'.$this->admin->id)->assertUnprocessable();
    }

    public function test_administrator_can_approve_pending_user_by_assigning_role_and_activating(): void
    {
        $staffRoleId = Role::where('slug', 'staff')->value('id');
        $pending = User::factory()->create([
            'name' => 'Pending teammate', 'username' => 'pending-teammate',
            'role_id' => null, 'is_active' => false, 'approved_at' => null,
        ]);

        $this->patchJson('/api/users/'.$pending->id.'/access', ['role_id' => $staffRoleId, 'is_active' => false])
            ->assertOk()->assertJsonPath('data.role.slug', 'staff')->assertJsonPath('data.approved_at', null);
        $this->patchJson('/api/users/'.$pending->id.'/access', ['role_id' => $staffRoleId, 'is_active' => true])
            ->assertOk()->assertJsonPath('data.is_active', true);
        $this->assertNotNull($pending->fresh()->approved_at);
        $this->assertTrue($pending->fresh()->hasPermission('dashboard.view'));
    }

    public function test_withdrawing_access_revokes_database_sessions_and_tokens_and_blocks_requests(): void
    {
        config(['session.driver' => 'database']);
        $staff = User::factory()->create([
            'username' => 'revoked-staff', 'role_id' => Role::where('slug', 'staff')->value('id'),
        ]);
        $sessionId = 'revoke-session-'.$staff->id;
        DB::table('sessions')->insert([
            'id' => $sessionId, 'user_id' => $staff->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
        ]);
        $token = $staff->createToken('test')->accessToken;

        $this->patchJson('/api/users/'.$staff->id.'/access', [
            'role_id' => $staff->role_id, 'is_active' => false,
        ])->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => $sessionId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        $this->actingAs($staff->fresh(), 'web')->getJson('/api/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Your account is pending administrator approval. Contact an administrator.');

        $this->actingAs($this->admin, 'web')->patchJson('/api/users/'.$staff->id.'/access', [
            'role_id' => null, 'is_active' => true,
        ])->assertOk()->assertJsonPath('data.role_id', null);
        $rolelessSessionId = 'roleless-session-'.$staff->id;
        DB::table('sessions')->insert([
            'id' => $rolelessSessionId, 'user_id' => $staff->id, 'ip_address' => '127.0.0.1',
            'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
        ]);
        $rolelessToken = $staff->fresh()->createToken('roleless-test')->accessToken;
        $this->actingAs($this->admin, 'web')->patchJson('/api/users/'.$staff->id.'/access', [
            'role_id' => null, 'is_active' => true,
        ])->assertOk();
        $this->assertDatabaseMissing('sessions', ['id' => $rolelessSessionId]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $rolelessToken->id]);
        $this->actingAs($staff->fresh(), 'web')->getJson('/api/dashboard')->assertForbidden();
        $this->assertFalse($staff->fresh()->hasPermission('dashboard.view'));
    }

    public function test_role_create_update_and_delete_enforce_permission_and_assignment_rules(): void
    {
        $id = $this->postJson('/api/users/roles', ['name' => 'Dispatch', 'permissions' => ['users.view']])
            ->assertCreated()->assertJsonPath('data.slug', 'dispatch')->json('data.id');
        $this->putJson('/api/users/roles/'.$id, ['name' => 'Dispatch Team', 'permissions' => ['users.view', 'drivers.view']])
            ->assertOk()->assertJsonPath('data.name', 'Dispatch Team');
        $role = Role::findOrFail($id);
        $role->update(['permissions' => [...$role->permissions, 'users.update']]);
        $this->putJson('/api/users/roles/'.$id, ['name' => 'Dispatch Team', 'permissions' => ['users.view', 'drivers.view']])->assertOk();
        $this->assertContains('users.update', Role::findOrFail($id)->permissions);
        $this->postJson('/api/users/roles', ['name' => 'Invalid access', 'permissions' => ['users.update']])
            ->assertUnprocessable()->assertJsonValidationErrors('permissions.0');
        $this->deleteJson('/api/users/roles/'.$id)->assertNoContent();

        $this->putJson('/api/users/roles/'.Role::where('slug', 'administrator')->value('id'), ['name' => 'Administrator', 'permissions' => ['users.view']])
            ->assertUnprocessable()->assertJsonValidationErrors('permissions');
        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        $staffMember = User::factory()->create(['role_id' => $staffRole->id]);
        $this->deleteJson('/api/users/roles/'.$staffRole->id)->assertUnprocessable()->assertJsonValidationErrors('role');
        $staffMember->delete();
        $this->deleteJson('/api/users/roles/'.$staffRole->id)->assertNoContent();
        $this->assertDatabaseMissing('roles', ['id' => $staffRole->id]);

        $this->deleteJson('/api/users/roles/'.Role::where('slug', 'administrator')->value('id'))
            ->assertUnprocessable()->assertJsonValidationErrors('role');
    }

    public function test_inactive_authenticated_accounts_are_blocked_from_api_requests(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->actingAs($user, 'web')->getJson('/api/dashboard')->assertForbidden();
    }

    public function test_module_roles_separate_read_and_manage_permissions_on_api_requests(): void
    {
        $reader = Role::create(['name' => 'Operations reader', 'slug' => 'operations-reader', 'permissions' => ['dashboard.view', 'customers.view']]);
        $user = User::factory()->create(['role_id' => $reader->id]);
        $this->actingAs($user, 'web')->getJson('/api/dashboard')->assertOk();
        $this->postJson('/api/customers', [])->assertForbidden();
    }
}
