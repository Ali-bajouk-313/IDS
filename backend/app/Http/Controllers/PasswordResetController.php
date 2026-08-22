<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\PasswordResetMail;
use Throwable;
class PasswordResetController extends Controller
{
   public function sendResetLink(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }


    $email = strtolower(trim($request->email));
    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();


    if (!$user) {
        return response()->json([
            'message' => 'No account found with this email'
        ], 404);
    }
    $token = Str::random(60);
    DB::table('password_reset_tokens')->updateOrInsert(
    [
        'email' => $email
    ],
    [
        'token' => $token,
        'created_at' => now()
    ]
);

    try {
        Mail::to($user->email)->send(new PasswordResetMail($token, $user->email));
    } catch (Throwable $exception) {
        DB::table('password_reset_tokens')->where('email', $email)->delete();
        report($exception);

        return response()->json([
            'message' => 'The reset email could not be sent. Check the mail configuration and try again.'
        ], 503);
    }


return response()->json([
    'message' => 'Password reset link sent successfully'
]);
}

   public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'token' => 'required',
        'password' => 'required|min:8|confirmed'
    ]);


    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);
    }


    $email = strtolower(trim($request->email));
    $resetToken = DB::table('password_reset_tokens')
        ->where('email', $email)
        ->where('token', $request->token)
        ->first();


    if (!$resetToken) {
        return response()->json([
            'message' => 'Invalid reset token'
        ], 400);
    }


    $user = User::whereRaw('LOWER(email) = ?', [$email])->first();


    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }


    $user->password = bcrypt($request->password);

    $user->save();


    DB::table('password_reset_tokens')
        ->where('email', $email)
        ->delete();


    return response()->json([
        'message' => 'Password reset successfully'
    ]);
}
}