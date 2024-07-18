<?php

namespace Tests\Unit;

// use PHPUnit\Framework\TestCase;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Str;

class UserTest extends TestCase
{
    /** @test */
    public function it_sends_password_reset_notification()
    {
        Notification::fake();
        // $user_id = Str::uuid();
        $user = User::factory()->create();
        $token = 'sample-token';

        $user->sendPasswordResetNotification($token);

        Notification::assertSentTo(
            [$user], ResetPasswordNotification::class,
            function ($notification) use ($token) {
                return $notification->token === $token;
            }
        );
    }
}
