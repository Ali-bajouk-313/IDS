<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('roles');

        Schema::create('roles', function ($table) {
            $table->id();
            $table->string('roleName');
            $table->string('description')->nullable();
            $table->timestamp('createdAt')->nullable();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->unsignedBigInteger('roleId');
            $table->unsignedBigInteger('departmentId')->nullable();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('status')->default('Active');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('createdAt')->nullable();
            $table->timestamp('updatedAt')->nullable();
        });
    }

    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['IT Support'], 'profile-it@example.com', 'Support Agent', null, '03712345');
        $user->email_verified_at = now();
        $user->save();

        $this->actingAs($user, 'api');

        $response = $this->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'profile-it@example.com')
            ->assertJsonPath('data.user.role.name', 'IT Support');
    }

    public function test_guest_cannot_retrieve_profile(): void
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    public function test_authenticated_user_can_update_their_own_profile(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['Employee'], 'profile-update@example.com', 'Old Name', null, '03711111');

        $this->actingAs($user, 'api');

        $response = $this->putJson('/api/profile', [
            'fullName' => 'Updated Name',
            'phone' => '03 722 222',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.fullName', 'Updated Name')
            ->assertJsonPath('data.user.phone', '03722222');

        $user->refresh();
        $this->assertSame('Updated Name', $user->fullName);
        $this->assertSame('03722222', $user->phone);
    }

    public function test_user_cannot_update_another_users_profile_via_payload_tampering(): void
    {
        $roles = $this->seedRoles();
        $userA = $this->makeUser($roles['Employee'], 'profile-a@example.com', 'User A', null, '03733333');
        $userB = $this->makeUser($roles['Employee'], 'profile-b@example.com', 'User B', null, '03744444');

        $this->actingAs($userA, 'api');

        $response = $this->putJson('/api/profile', [
            'id' => $userB->id,
            'userId' => $userB->id,
            'fullName' => 'Hacked Name',
            'phone' => '03755555',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.user.id', $userA->id)
            ->assertJsonPath('data.user.fullName', 'Hacked Name');

        $userA->refresh();
        $userB->refresh();

        $this->assertSame('Hacked Name', $userA->fullName);
        $this->assertSame('User B', $userB->fullName);
        $this->assertSame('03744444', $userB->phone);
    }

    public function test_profile_validation_returns_clear_errors(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['Manager'], 'profile-validation@example.com', 'Manager User', null, '03766666');

        $this->actingAs($user, 'api');

        $response = $this->putJson('/api/profile', [
            'fullName' => '',
            'phone' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fullName', 'phone']);
    }

    public function test_authenticated_user_can_change_password(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['Admin'], 'profile-password@example.com', 'Admin User', null, '03777777');

        $this->actingAs($user, 'api');

        $response = $this->putJson('/api/profile/password', [
            'currentPassword' => 'password123',
            'newPassword' => 'newPassword123',
            'newPassword_confirmation' => 'newPassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $user->refresh();
        $this->assertTrue(Hash::check('newPassword123', $user->password));
    }

    public function test_incorrect_current_password_is_rejected(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['Admin'], 'profile-password-invalid@example.com', 'Admin User', null, '03788888');

        $this->actingAs($user, 'api');

        $response = $this->putJson('/api/profile/password', [
            'currentPassword' => 'wrong-password',
            'newPassword' => 'newPassword123',
            'newPassword_confirmation' => 'newPassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['currentPassword']);
    }

    public function test_password_confirmation_is_required(): void
    {
        $roles = $this->seedRoles();
        $user = $this->makeUser($roles['Employee'], 'profile-password-confirm@example.com', 'Employee User', null, '03799999');

        $this->actingAs($user, 'api');

        $response = $this->putJson('/api/profile/password', [
            'currentPassword' => 'password123',
            'newPassword' => 'newPassword123',
            'newPassword_confirmation' => 'does-not-match',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['newPassword']);
    }

    public function test_profile_works_for_all_supported_roles(): void
    {
        $roles = $this->seedRoles();

        foreach (['Admin', 'Manager', 'IT Support', 'Employee'] as $roleName) {
            $user = $this->makeUser(
                $roles[$roleName],
                strtolower(str_replace(' ', '-', $roleName)) . '@profile-role.example.com',
                $roleName . ' User',
                null,
                '037' . rand(10000, 99999)
            );

            $this->actingAs($user, 'api');
            $this->getJson('/api/profile')
                ->assertStatus(200)
                ->assertJsonPath('data.user.role.name', $roleName);
        }
    }

    public function test_admin_can_view_users_listing(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'users-admin@example.com', 'Admin Users');
        $this->makeUser($roles['Employee'], 'users-employee@example.com', 'Employee Users');

        $this->actingAs($admin, 'api');

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'users')
            ->assertJsonPath('users.0.role.roleName', 'Admin');
    }

    public function test_non_admin_cannot_view_users_listing(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'users-no-access@example.com', 'Employee No Access');

        $this->actingAs($employee, 'api');

        $this->getJson('/api/users')->assertStatus(403);
    }

    public function test_admin_can_view_single_user(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'users-admin-view@example.com', 'Admin View');
        $employee = $this->makeUser($roles['Employee'], 'users-employee-view@example.com', 'Employee View');

        $this->actingAs($admin, 'api');

        $this->getJson('/api/users/' . $employee->id)
            ->assertStatus(200)
            ->assertJsonPath('user.id', $employee->id)
            ->assertJsonPath('user.fullName', 'Employee View');
    }

    public function test_admin_can_update_user_role_and_status(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'users-admin-update@example.com', 'Admin Update', null, '03712000');
        $employee = $this->makeUser($roles['Employee'], 'users-employee-update@example.com', 'Employee Update', null, '03713000');

        $this->actingAs($admin, 'api');

        $response = $this->putJson('/api/users/' . $employee->id, [
            'fullName' => 'Employee Updated',
            'email' => 'employee-updated@example.com',
            'phone' => '03 765 432',
            'roleId' => $roles['IT Support']->id,
            'status' => 'Inactive',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.fullName', 'Employee Updated')
            ->assertJsonPath('user.email', 'employee-updated@example.com')
            ->assertJsonPath('user.phone', '03765432')
            ->assertJsonPath('user.status', 'Inactive')
            ->assertJsonPath('user.role.roleName', 'IT Support');
    }

    public function test_admin_can_delete_user_but_not_self(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->makeUser($roles['Admin'], 'users-admin-delete@example.com', 'Admin Delete', null, '03714000');
        $employee = $this->makeUser($roles['Employee'], 'users-employee-delete@example.com', 'Employee Delete', null, '03715000');

        $this->actingAs($admin, 'api');

        $this->deleteJson('/api/users/' . $employee->id)->assertStatus(200);
        $this->assertNull(User::find($employee->id));

        $this->deleteJson('/api/users/' . $admin->id)->assertStatus(422);
        $this->assertNotNull(User::find($admin->id));
    }

    public function test_non_admin_cannot_manage_users(): void
    {
        $roles = $this->seedRoles();
        $employee = $this->makeUser($roles['Employee'], 'users-manage-no-access@example.com', 'No Access User', null, '03716000');
        $another = $this->makeUser($roles['Employee'], 'users-manage-target@example.com', 'Target User', null, '03717000');

        $this->actingAs($employee, 'api');

        $this->getJson('/api/users/' . $another->id)->assertStatus(403);
        $this->putJson('/api/users/' . $another->id, [
            'fullName' => 'Nope',
            'email' => 'nope@example.com',
            'phone' => '03718000',
            'roleId' => $roles['Employee']->id,
            'status' => 'Active',
        ])->assertStatus(403);
        $this->deleteJson('/api/users/' . $another->id)->assertStatus(403);
    }

    private function seedRoles(): array
    {
        $employee = Role::firstOrCreate(['id' => 1], ['roleName' => 'Employee']);
        $support = Role::firstOrCreate(['id' => 2], ['roleName' => 'IT Support']);
        $manager = Role::firstOrCreate(['id' => 3], ['roleName' => 'Manager']);
        $admin = Role::firstOrCreate(['id' => 4], ['roleName' => 'Admin']);

        return [
            'Employee' => $employee,
            'IT Support' => $support,
            'Manager' => $manager,
            'Admin' => $admin,
        ];
    }

    private function makeUser(Role $role, string $email, string $fullName, ?int $departmentId = null, ?string $phone = null): User
    {
        return User::create([
            'roleId' => $role->id,
            'departmentId' => $departmentId,
            'fullName' => $fullName,
            'email' => $email,
            'password' => bcrypt('password123'),
            'phone' => $phone,
            'status' => 'Active',
            'createdAt' => now(),
            'updatedAt' => now(),
        ]);
    }
}