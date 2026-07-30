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
use App\Services\ActivityLogService;

class AuthController extends Controller
{
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

        // Validate registration data
        $request->validate([

            'fullName' => 'required|string|max:100',

            'email' => 'required|email|unique:users,email',

            'password' => 'required|min:8',

            'phone' => 'nullable|string|max:20'

        ]);



        // Create new user
        $user = User::create([

            // Default role = Employee
            'roleId' => 1,

            'departmentId' => null,

            'fullName' => $request->fullName,

            'email' => $request->email,

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


Mail::to($user->email)
    ->send(new EmailVerificationMail(
        $token,
        $user->email
    ));

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
    // Validate request
    $request->validate([
        'email' => 'required|email',
        'token' => 'required'
    ]);


    // Check token exists
    $verification = DB::table('email_verification_tokens')
        ->where('email', $request->email)
        ->where('token', $request->token)
        ->first();


    if (!$verification) {

        return response()->json([
            'message' => 'Invalid verification token'
        ], 400);

    }


    // Find user
    $user = User::where('email', $request->email)->first();


    if (!$user) {

        return response()->json([
            'message' => 'User not found'
        ], 404);

    }


    // Update email verification status
    $user->email_verified_at = now();

    $user->save();


    // Remove token after successful verification
    DB::table('email_verification_tokens')
        ->where('email', $request->email)
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

        if ($user->role->roleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $users = User::with('role')->get();

        return response()->json(['users' => $users]);
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

}