<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResetPasswordNotification;

class ForgotPasswordFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_sends_password_reset_link_when_email_exists()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])
             ->assertStatus(200)
             ->assertJson(['message' => 'Password reset link sent']);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        Notification::assertSentTo(
            [$user], ResetPasswordNotification::class
        );
    }

    /** @test */
    public function it_returns_error_when_email_does_not_exist()
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'nonexistent@example.com'])
             ->assertStatus(400)
             ->assertJson(['message' => 'User does not exist']);
    }

    /** @test */
    public function it_validates_email_field()
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => ''])
             ->assertStatus(400)
             ->assertJsonStructure(['message', 'errors' => ['email']]);
    }
}
