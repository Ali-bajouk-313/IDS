<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use App\Mail\EmailVerificationMail;
use Tests\TestCase;

class AuthVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('roleId')->nullable();
            $table->unsignedBigInteger('departmentId')->nullable();
            $table->string('fullName');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone')->nullable();
            $table->string('status');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_verification_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_already_verified_user_receives_a_successful_verification_response(): void
    {
        $user = User::create([
            'roleId' => 1,
            'departmentId' => null,
            'fullName' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => Hash::make('password123'),
            'phone' => null,
            'status' => 'Active',
        ]);

        $user->email_verified_at = now();
        $user->save();

        $response = $this->getJson('/api/verify-email?email=' . urlencode($user->email) . '&token=does-not-matter');

        $response->assertOk();
        $response->assertJsonFragment(['message' => 'Email is already verified']);
    }

    public function test_unverified_user_can_request_a_new_verification_email(): void
    {
        Mail::fake();

        $user = User::create([
            'roleId' => 1,
            'departmentId' => null,
            'fullName' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => Hash::make('password123'),
            'phone' => null,
            'status' => 'Active',
        ]);

        $response = $this->postJson('/api/resend-verification-email', [
            'email' => strtoupper($user->email),
        ]);

        $response->assertOk();
        $response->assertJsonFragment([
            'message' => 'A new verification link has been sent to your email.',
        ]);
        $this->assertDatabaseHas('email_verification_tokens', [
            'email' => $user->email,
        ]);
        Mail::assertSent(EmailVerificationMail::class, function (EmailVerificationMail $mail) use ($user) {
            return $mail->email === $user->email && strlen($mail->token) === 60;
        });
    }
}
