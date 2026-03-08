<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Exception;

class SocialiteController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirect($provider)
    {
        // Validate provider
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Invalid provider');
        }
        
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the callback from the provider.
     */
    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            // Check if user exists with this email
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? $provider . '_user',
                    'email' => $socialUser->getEmail(),
                    'password' => Hash::make(Str::random(24)),
                    'social_id' => $socialUser->getId(),
                    'social_type' => $provider,
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
                
                // Assign default role
                $user->addRole('user');
                
                // Create leaderboard entry
                \App\Models\Leaderboard::create([
                    'user_id' => $user->id,
                    'total_points' => 0,
                    'quizzes_completed' => 0,
                    'total_attempts' => 0,
                    'average_score' => 0,
                    'rank' => 0,
                    'weekly_rank' => 0,
                    'monthly_rank' => 0,
                ]);
            } else {
                // Update existing user with social info if not already set
                if (!$user->social_id) {
                    $user->update([
                        'social_id' => $socialUser->getId(),
                        'social_type' => $provider,
                        'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                    ]);
                }
            }
            
            // Log the user in
            Auth::login($user, true);
            
            // Update last login info
            $user->updateLastLogin();
            
            return redirect()->intended('/dashboard')
                ->with('success', 'Successfully logged in with ' . ucfirst($provider));
            
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Social login failed. Please try again. ' . $e->getMessage());
        }
    }
}