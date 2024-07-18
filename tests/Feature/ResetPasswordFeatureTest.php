<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ResetPasswordFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_resets_password_with_valid_token()
    {
        $user = User::factory()->create();
        $token = Str::random(60);

        // Store the token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );

        $this->postJson("/api/v1/auth/reset-password/{$token}", [
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ])->assertStatus(200)
          ->assertJson(['message' => 'Password reset successfully']);

        $this->assertTrue(Hash::check('newpassword', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    /** @test */
    public function it_returns_error_with_invalid_token()
    {
        $user = User::factory()->create();

        $this->postJson("/api/v1/auth/reset-password/invalidtoken", [
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ])->assertStatus(400)
          ->assertJson(['message' => 'Invalid token or email']);
    }

    /** @test */
    public function it_returns_error_with_expired_token()
    {
        $user = User::factory()->create();
        $token = Str::random(60);

        // Store the token in the password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()->subHours(2) // Assuming tokens expire in 1 hour
            ]
        );

        $this->postJson("/api/v1/auth/reset-password/{$token}", [
            'email' => $user->email,
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword'
        ])->assertStatus(400)
          ->assertJson(['message' => 'Token has expired']);
    }

    /** @test */
    public function it_validates_reset_password_fields()
    {
        $token = Str::random(60);

        $this->postJson("/api/v1/auth/reset-password/{$token}", [
            'email' => 'notanemail',
            'password' => 'short',
            'password_confirmation' => 'notmatching'
        ])->assertStatus(400)
          ->assertJsonStructure(['message', 'errors' => ['email', 'password']]);
    }

}
