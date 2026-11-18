<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserAccessController extends Controller
{
    public const PERMISSIONS = [
        'dashboard.view' => 'View dashboard',
        'bookings.view' => 'View daily bookings', 'bookings.manage' => 'Manage daily bookings',
        'contracts.view' => 'View monthly contracts', 'contracts.manage' => 'Manage monthly contracts',
        'fleet.view' => 'View fleet vehicles', 'fleet.manage' => 'Manage fleet vehicles',
        'customers.view' => 'View customers', 'customers.manage' => 'Manage customers',
        'drivers.view' => 'View drivers', 'drivers.manage' => 'Manage drivers',
        'operators.view' => 'View operators', 'operators.manage' => 'Manage operators',
        'maintenance.view' => 'View maintenance', 'maintenance.manage' => 'Manage maintenance',
        'car_licenses.view' => 'View car licenses', 'car_licenses.manage' => 'Manage car licenses',
        'insurance.view' => 'View insurance', 'insurance.manage' => 'Manage insurance',
        'finance.view' => 'View finance', 'finance.manage' => 'Manage finance',
        'reports.view' => 'View reports', 'reports.manage' => 'Manage reports',
        'users.view' => 'View staff users',
        'users.create' => 'Add staff users',
        'users.update' => 'Edit staff users',
        'users.delete' => 'Remove staff users',
        'access.manage' => 'Manage account access and status',
        'roles.view' => 'View roles and permissions',
        'roles.manage' => 'Create and edit roles',
    ];

    public const MODULE_VIEW_PERMISSIONS = [
        'dashboard.view' => 'Dashboard',
        'bookings.view' => 'Daily Bookings',
        'contracts.view' => 'Monthly Contracts',
        'fleet.view' => 'Vehicles',
        'customers.view' => 'Customers',
        'drivers.view' => 'Drivers',
        'operators.view' => 'Operators',
        'maintenance.view' => 'Maintenance',
        'car_licenses.view' => 'Car License',
        'insurance.view' => 'Insurance',
        'finance.view' => 'Finance',
        'reports.view' => 'Reports',
        'users.view' => 'Users & Access',
    ];

    public function index(Request $request)
    {
        Gate::authorize('users.view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::in(['Active', 'Inactive'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $query = User::query()->with('role:id,name,slug')
            ->when(($filters['search'] ?? '') !== '', fn ($q) => $q->where(fn ($s) => $s->where('name', 'like', '%'.$filters['search'].'%')->orWhere('username', 'like', '%'.$filters['search'].'%')->orWhere('email', 'like', '%'.$filters['search'].'%')))
            ->when(isset($filters['status']), fn ($q) => $q->where('is_active', $filters['status'] === 'Active'))
            ->when(isset($filters['role_id']), fn ($q) => $q->where('role_id', $filters['role_id']));

        return response()->json($query->orderBy('name')->paginate($filters['per_page'] ?? 10)->withQueryString());
    }

    public function summary()
    {
        Gate::authorize('users.view');
        return response()->json(['data' => [
            'total' => User::query()->count(),
            'active' => User::query()->where('is_active', true)->count(),
            'inactive' => User::query()->where('is_active', false)->count(),
            'roles' => Role::query()->count(),
        ]]);
    }

    public function roles()
    {
        Gate::authorize('roles.view');
        return response()->json(['data' => Role::query()->withCount('users')->orderBy('name')->get(), 'permissions' => self::MODULE_VIEW_PERMISSIONS]);
    }

    public function store(Request $request)
    {
        Gate::authorize('users.create');
        $data = $this->userData($request);
        $data['password'] = $data['password'];
        $data['approved_at'] = now();
        $user = User::create($data);
        return response()->json(['data' => $user->load('role:id,name,slug')], 201);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('users.update');
        $data = $this->userData($request, $user, true);
        $this->protectAdministrator($request->user(), $user, $data);
        if (empty($data['password'])) unset($data['password']);
        $wasApproved = $user->approved_at !== null;
        $user->update($data);
        $user->refresh();
        if (! $wasApproved && $user->is_active && $user->role_id !== null) {
            $user->update(['approved_at' => now()]);
        }
        $this->revokeSessionsIfAccessWasWithdrawn($user, $data);
        return response()->json(['data' => $user->fresh()->load('role:id,name,slug')]);
    }

    public function updateAccess(Request $request, User $user)
    {
        Gate::authorize('access.manage');
        $data = $request->validate([
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);
        $this->protectAdministrator($request->user(), $user, $data);
        $wasApproved = $user->approved_at !== null;
        $user->update($data);
        if (! $wasApproved && $user->is_active && $user->role_id !== null) {
            $user->update(['approved_at' => now()]);
        }
        $this->revokeSessionsIfAccessWasWithdrawn($user, $data);
        return response()->json(['data' => $user->fresh()->load('role:id,name,slug')]);
    }

    public function destroy(Request $request, User $user)
    {
        Gate::authorize('users.delete');
        if ($request->user()->is($user)) throw ValidationException::withMessages(['user' => 'You cannot remove your own account.']);
        $this->protectLastAdministrator($user, ['role_id' => null, 'is_active' => false]);
        $user->delete();
        return response()->noContent();
    }

    public function storeRole(Request $request)
    {
        Gate::authorize('roles.manage');
        $data = $this->roleData($request);
        $data['slug'] = Str::slug($data['name']);
        return response()->json(['data' => Role::create($data)->loadCount('users')], 201);
    }

    public function updateRole(Request $request, Role $role)
    {
        Gate::authorize('roles.manage');
        $data = $this->roleData($request, $role);
        $isAdministratorRole = $role->slug === 'administrator' || ($role->is_system && $role->slug === 'admin');
        if ($isAdministratorRole && count(array_diff(array_keys(self::MODULE_VIEW_PERMISSIONS), $data['permissions'])) > 0) {
            throw ValidationException::withMessages(['permissions' => 'Administrator roles must retain access to every module.']);
        }
        $modulePermissions = $data['permissions'];
        $internalPermissions = array_values(array_diff($role->permissions ?? [], array_keys(self::MODULE_VIEW_PERMISSIONS)));
        $role->update([
            ...$data,
            'permissions' => array_values(array_unique([...$internalPermissions, ...$modulePermissions])),
            'slug' => $role->is_system ? $role->slug : Str::slug($data['name']),
        ]);
        return response()->json(['data' => $role->fresh()->loadCount('users')]);
    }

    public function destroyRole(Role $role)
    {
        Gate::authorize('roles.manage');
        $isProtectedAdministrator = $role->slug === 'administrator'
            || ($role->is_system && $role->slug === 'admin');
        $isDeletableBuiltInRole = $role->is_system && $role->slug === 'staff';
        if ($isProtectedAdministrator || ($role->is_system && ! $isDeletableBuiltInRole)) {
            throw ValidationException::withMessages(['role' => 'Administrator roles and protected system roles cannot be removed.']);
        }
        if ($role->users()->exists()) throw ValidationException::withMessages(['role' => 'Move users to another role before removing this role.']);
        $role->delete();
        return response()->noContent();
    }

    private function userData(Request $request, ?User $user = null, bool $updating = false): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'alpha_dash', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'password' => [$updating ? 'nullable' : 'required', 'string', 'min:8'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function roleData(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(self::MODULE_VIEW_PERMISSIONS))],
        ]);
        $data['permissions'] = array_values(array_unique($data['permissions']));
        return $data;
    }

    private function protectAdministrator(User $actor, User $target, array $data): void
    {
        if ((int) $actor->id === (int) $target->id && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw ValidationException::withMessages(['is_active' => 'You cannot deactivate your own account.']);
        }
        $this->protectLastAdministrator($target, $data);
    }

    private function revokeSessionsIfAccessWasWithdrawn(User $user, array $data): void
    {
        $accessWasWithdrawn = (array_key_exists('is_active', $data) && ! $data['is_active'])
            || (array_key_exists('role_id', $data) && $data['role_id'] === null);
        if (! $accessWasWithdrawn) return;

        // This app uses Laravel's database session driver; deleting the rows revokes
        // existing browser sessions immediately. Sanctum tokens are cleared as well.
        $connection = config('session.connection');
        $table = config('session.table', 'sessions');
        if (config('session.driver') === 'database' && Schema::connection($connection)->hasTable($table)) {
            DB::connection($connection)->table($table)->where('user_id', $user->id)->delete();
        }
        $user->tokens()->delete();
    }

    private function protectLastAdministrator(User $target, array $data): void
    {
        $adminRoleId = Role::query()->where('slug', 'administrator')->value('id');
        $removesAdmin = (int) $target->role_id === (int) $adminRoleId && (array_key_exists('role_id', $data) && (int) $data['role_id'] !== (int) $adminRoleId || array_key_exists('is_active', $data) && ! $data['is_active']);
        if ($removesAdmin && User::query()->where('role_id', $adminRoleId)->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages(['role_id' => 'At least one active administrator must remain.']);
        }
    }
}
