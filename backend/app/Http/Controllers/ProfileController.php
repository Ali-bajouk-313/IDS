<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private const LEBANESE_PHONE_REGEX = '/^(?:\+?961|00961|0)?(?:3|70|71|76|78|79|81)\d{6}$/';

    public function show(Request $request)
    {
        $user = $this->authenticatedUser($request);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $this->profilePayload($user),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $normalizedFullName = trim((string) $request->input('fullName', ''));
        $normalizedPhone = preg_replace('/[\s-]+/', '', (string) $request->input('phone', ''));

        $request->merge([
            'fullName' => $normalizedFullName,
            'phone' => $normalizedPhone,
        ]);

        $validated = $request->validate([
            'fullName' => ['required', 'string', 'max:100', Rule::unique('users', 'fullName')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:20', 'regex:' . self::LEBANESE_PHONE_REGEX, Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'fullName.unique' => 'This username is already taken.',
            'phone.unique' => 'This phone number is already registered.',
            'phone.regex' => 'Phone number must be a valid Lebanese number.',
        ]);

        $user->fullName = $validated['fullName'];
        $user->phone = $validated['phone'];
        $user->updatedAt = now();
        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'success' => true,
            'data' => [
                'user' => $this->profilePayload($this->authenticatedUser($request)),
            ],
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $validated = $request->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'newPassword.confirmed' => 'New password confirmation does not match.',
        ]);

        if (!Hash::check($validated['currentPassword'], $user->password)) {
            return response()->json([
                'errors' => [
                    'currentPassword' => ['Current password is incorrect.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($validated['newPassword']);
        $user->updatedAt = now();
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully.',
            'success' => true,
        ]);
    }

    protected function authenticatedUser(Request $request): User
    {
        return User::with(['role:id,roleName'])
            ->findOrFail($request->user('api')->id);
    }

    protected function profilePayload(User $user): array
    {
        return [
            'id' => $user->id,
            'fullName' => $user->fullName,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'emailVerifiedAt' => optional($user->email_verified_at)->toIso8601String(),
            'createdAt' => optional($user->createdAt)->toIso8601String(),
            'updatedAt' => optional($user->updatedAt)->toIso8601String(),
            'role' => [
                'id' => $user->role?->id,
                'name' => $user->role?->roleName,
            ],
        ];
    }
}