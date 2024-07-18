<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PasswordRequestController extends Controller
{
    public function forgetPassword(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User does not exist',
                'status_code' => 400
            ], 400);
        }

        // Create a new token
        $token = Str::random(60);

         // Store the token in the password_reset_tokens table
         DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]
        );
        $user->sendPasswordResetNotification($token);

        return response()->json([
            'message' => 'Password reset link sent',
            'status_code' => 200
        ], 200);
    }

    public function resetPassword(Request $request, $token)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
                'status_code' => 400
            ], 400);
        }

        // Check if the token exists in the password resets table
        $passwordReset = DB::table('password_reset_tokens')->where([
            ['email', $request->email]
        ])->first();

        // If the token is invalid, return an error
        if (!$passwordReset || !Hash::check($token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Invalid token or email',
                'status_code' => 400
            ], 400);
        }

        // Check if the token has expired (tokens are typically valid for one hour)
        if (Carbon::parse($passwordReset->created_at)->addMinutes(config('auth.passwords.users.expire'))->isPast()) {
            return response()->json([
                'message' => 'Token has expired',
                'status_code' => 400
            ], 400);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User does not exist',
                'status_code' => 400
            ], 400);
        }

        // Reset the password
        $user->password = Hash::make($request->password);
        $user->save();

        // Delete the password reset token after successful reset
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password reset successfully',
            'status_code' => 200
        ], 200);
    }
}
