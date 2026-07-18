<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

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


        // Check password
        if (!Hash::check($request->password, $user->password)) {

            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);

        }


        // Generate JWT token
        $token = JWTAuth::fromUser($user);


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



        return response()->json([

            'message' => 'User registered successfully',

            'user' => [

                'id' => $user->id,

                'fullName' => $user->fullName,

                'email' => $user->email

            ]

        ], 201);

    }



    public function me()
    {
        $user = auth('api')->user();

        return response()->json([
            'user' => $user
        ]);
    }



    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

}