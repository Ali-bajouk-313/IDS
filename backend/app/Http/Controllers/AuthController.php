<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ActivityLogService;
use Illuminate\Validation\Rule;
use Throwable;

class AuthController extends Controller
{
    private const LEBANESE_PHONE_REGEX = '/^(?:\+?961|00961|0)?(?:3|70|71|76|78|79|81)\d{6}$/';

    public function __construct(protected ActivityLogService $activityLogService)
    {
    }

    public function login(Request $request)
{
    // Validate input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);


    // Find user
    $user = User::where('email', $request->email)->first();


    // Check email exists
    if (!$user) {

        return response()->json([
            'message' => 'Invalid email or password'
        ], 401);

    }


    // Check account status
    if ($user->status !== 'Active') {

        return response()->json([
            'message' => 'Account is inactive'
        ], 403);

    }


    // Check email verification
    if ($user->email_verified_at === null) {

        return response()->json([
            'message' => 'Please verify your email before logging in'
        ], 403);

    }


    // Check password
    if (!Hash::check($request->password, $user->password)) {

        return response()->json([
            'message' => 'Invalid email or password'
        ], 401);

    }


    // Generate JWT token
    $token = JWTAuth::fromUser($user);

    $this->activityLogService->logLogin($user, $request->ip());


    return response()->json([

        'message' => 'Login successful',

        'token' => $token,

        'user' => [
            'id' => $user->id,
            'fullName' => $user->fullName,
            'email' => $user->email,
            'role' => $user->role->roleName
        ]

    ]);

}

    public function register(Request $request)
    {
        $normalizedFullName = trim((string) $request->input('fullName'));
        $normalizedEmail = strtolower(trim((string) $request->input('email')));
        $normalizedPhone = preg_replace('/[\s-]+/', '', (string) $request->input('phone'));

        $request->merge([
            'fullName' => $normalizedFullName,
            'email' => $normalizedEmail,
            'phone' => $normalizedPhone,
        ]);

        // Validate registration data
        $request->validate([

            'fullName' => 'required|string|max:100|unique:users,fullName',

            'email' => 'required|email|unique:users,email',

            'password' => 'required|min:8',

            'phone' => ['required', 'unique:users,phone', 'regex:' . self::LEBANESE_PHONE_REGEX]

        ], [
            'fullName.unique' => 'This username is already taken.',
            'phone.unique' => 'This phone number is already registered.',
            'phone.regex' => 'Phone number must be a valid Lebanese number.',
        ]);



        // Create new user
        $user = User::create([

            // Default role = Employee
            'roleId' => 1,

            'fullName' => $request->fullName,

            'email' => strtolower(trim($request->email)),

            // Encrypt password using bcrypt
            'password' => Hash::make($request->password),

            'phone' => $request->phone,

            'status' => 'Active'

        ]);
$token = Str::random(60);


DB::table('email_verification_tokens')->insert([
    'email' => $user->email,
    'token' => $token,
    'created_at' => now()
]);


        try {
            Mail::to($user->email)->send(new EmailVerificationMail($token, $user->email));
        } catch (Throwable $exception) {
            DB::table('email_verification_tokens')->where('email', $user->email)->delete();
            report($exception);

       return response()->json([
    'message' => 'The verification email could not be sent. Check the mail configuration and try again.'
], 503);
        }

        $this->activityLogService->logRegister($user, $request->ip());


        return response()->json([

            'message' => 'User registered successfully',

            'user' => [

                'id' => $user->id,

                'fullName' => $user->fullName,

                'email' => $user->email

            ]

        ], 201);

    }

public function verifyEmail(Request $request)
{
    $email = $request->query('email', $request->input('email'));
    $token = $request->query('token', $request->input('token'));

    $request->merge([
        'email' => $email,
        'token' => $token,
    ]);

    $request->validate([
        'email' => 'required|email',
        'token' => 'required'
    ]);

    $normalizedEmail = strtolower($email);

    $user = User::where('email', $normalizedEmail)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    if (!is_null($user->email_verified_at)) {
        return response()->json([
            'message' => 'Email is already verified'
        ]);
    }

    $verification = DB::table('email_verification_tokens')
        ->where('email', $normalizedEmail)
        ->where('token', $token)
        ->first();

    if (!$verification) {
        return response()->json([
            'message' => 'Invalid verification token'
        ], 400);
    }

    $user->email_verified_at = now();
    $user->save();

    DB::table('email_verification_tokens')
        ->where('email', $normalizedEmail)
        ->delete();

    return response()->json([
        'message' => 'Email verified successfully'
    ]);
}

    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => $user
        ]);
    }

    public function index(Request $request)
    {
        $user = auth('api')->user();

        if (!$this->isAdmin($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $users = User::with('role')->get();

        return response()->json(['users' => $users]);
    }

    public function showUser(Request $request, int $id)
    {
        $admin = auth('api')->user();

        if (!$this->isAdmin($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $user = User::with('role')->find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(['user' => $user]);
    }

    public function updateUser(Request $request, int $id)
    {
        $admin = auth('api')->user();

        if (!$this->isAdmin($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $normalizedFullName = trim((string) $request->input('fullName', ''));
        $normalizedEmail = strtolower(trim((string) $request->input('email', '')));
        $normalizedPhone = preg_replace('/[\s-]+/', '', (string) $request->input('phone', ''));

        $request->merge([
            'fullName' => $normalizedFullName,
            'email' => $normalizedEmail,
            'phone' => $normalizedPhone,
        ]);

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:100', Rule::unique('users', 'fullName')->ignore($targetUser->id)],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($targetUser->id)],
            'phone' => ['required', 'string', 'max:20', 'regex:' . self::LEBANESE_PHONE_REGEX, Rule::unique('users', 'phone')->ignore($targetUser->id)],
            'roleId' => ['required', 'integer', Rule::exists('roles', 'id')],
            'status' => ['required', 'string', Rule::in(['Active', 'Inactive'])],
        ], [
            'fullName.unique' => 'This username is already taken.',
            'phone.unique' => 'This phone number is already registered.',
            'phone.regex' => 'Phone number must be a valid Lebanese number.',
        ]);

        if ((int) $targetUser->id === (int) $admin->id && $validated['status'] !== 'Active') {
            return response()->json([
                'errors' => [
                    'status' => ['You cannot deactivate your own account.'],
                ],
            ], 422);
        }

        $targetUser->fullName = $validated['fullName'];
        $targetUser->email = $validated['email'];
        $targetUser->phone = $validated['phone'];
        $targetUser->roleId = (int) $validated['roleId'];
        $targetUser->status = $validated['status'];
        $targetUser->updatedAt = now();
        $targetUser->save();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => User::with('role')->find($targetUser->id),
        ]);
    }

    public function deleteUser(Request $request, int $id)
    {
        $admin = auth('api')->user();

        if (!$this->isAdmin($admin)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $targetUser = User::find($id);

        if (!$targetUser) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ((int) $targetUser->id === (int) $admin->id) {
            return response()->json([
                'errors' => [
                    'user' => ['You cannot delete your own account.'],
                ],
            ], 422);
        }

        DB::transaction(function () use ($targetUser, $admin): void {
            $this->reassignAndCleanupUserRelations((int) $targetUser->id, (int) $admin->id, (string) $targetUser->email);
            $targetUser->delete();
        });

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = auth('api')->user();

        auth('api')->logout();

        if ($user) {
            $this->activityLogService->logLogout($user, $request->ip());
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    protected function isAdmin(?User $user): bool
    {
        return $user && $user->role && $user->role->roleName === 'Admin';
    }

    protected function reassignAndCleanupUserRelations(int $targetUserId, int $replacementUserId, string $targetEmail): void
    {
        if (Schema::hasTable('tickets')) {
            if (Schema::hasColumn('tickets', 'createdBy')) {
                DB::table('tickets')
                    ->where('createdBy', $targetUserId)
                    ->update(['createdBy' => $replacementUserId]);
            }

            if (Schema::hasColumn('tickets', 'assignedTo')) {
                if (Schema::hasColumn('tickets', 'status')) {
                    DB::table('tickets')
                        ->where('assignedTo', $targetUserId)
                        ->whereIn('status', ['Assigned', 'In Progress'])
                        ->update(['status' => 'Open']);
                }

                $assignedUpdate = ['assignedTo' => null];
                if (Schema::hasColumn('tickets', 'assignedSupportName')) {
                    $assignedUpdate['assignedSupportName'] = null;
                }

                DB::table('tickets')
                    ->where('assignedTo', $targetUserId)
                    ->update($assignedUpdate);
            }
        }

        if (Schema::hasTable('tickethistory') && Schema::hasColumn('tickethistory', 'changedBy')) {
            DB::table('tickethistory')
                ->where('changedBy', $targetUserId)
                ->update(['changedBy' => $replacementUserId]);
        }

        if (Schema::hasTable('ticketcomments') && Schema::hasColumn('ticketcomments', 'userId')) {
            DB::table('ticketcomments')
                ->where('userId', $targetUserId)
                ->update(['userId' => $replacementUserId]);
        }

        if (Schema::hasTable('ticketattachments') && Schema::hasColumn('ticketattachments', 'uploadedBy')) {
            DB::table('ticketattachments')
                ->where('uploadedBy', $targetUserId)
                ->update(['uploadedBy' => $replacementUserId]);
        }

        if (Schema::hasTable('ticket_internal_notes') && Schema::hasColumn('ticket_internal_notes', 'user_id')) {
            DB::table('ticket_internal_notes')
                ->where('user_id', $targetUserId)
                ->update(['user_id' => $replacementUserId]);
        }

        if (Schema::hasTable('ticket_assignments')) {
            if (Schema::hasColumn('ticket_assignments', 'assigned_by')) {
                DB::table('ticket_assignments')
                    ->where('assigned_by', $targetUserId)
                    ->update(['assigned_by' => $replacementUserId]);
            }

            if (Schema::hasColumn('ticket_assignments', 'old_assigned_to')) {
                DB::table('ticket_assignments')
                    ->where('old_assigned_to', $targetUserId)
                    ->update(['old_assigned_to' => null]);
            }

            if (Schema::hasColumn('ticket_assignments', 'new_assigned_to')) {
                DB::table('ticket_assignments')
                    ->where('new_assigned_to', $targetUserId)
                    ->update(['new_assigned_to' => null]);
            }
        }

        if (Schema::hasTable('activitylogs') && Schema::hasColumn('activitylogs', 'userId')) {
            DB::table('activitylogs')
                ->where('userId', $targetUserId)
                ->delete();
        }

        if (Schema::hasTable('notifications')) {
            if (Schema::hasColumn('notifications', 'userId')) {
                DB::table('notifications')->where('userId', $targetUserId)->delete();
            }

            if (Schema::hasColumn('notifications', 'user_id')) {
                DB::table('notifications')->where('user_id', $targetUserId)->delete();
            }
        }

        if (Schema::hasTable('usersessions') && Schema::hasColumn('usersessions', 'userId')) {
            DB::table('usersessions')
                ->where('userId', $targetUserId)
                ->delete();
        }

        if (Schema::hasTable('email_verification_tokens') && Schema::hasColumn('email_verification_tokens', 'email')) {
            DB::table('email_verification_tokens')
                ->where('email', $targetEmail)
                ->delete();
        }
    }

}