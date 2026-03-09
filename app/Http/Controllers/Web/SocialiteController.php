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
    public function redirect($provider)
    {
        if (!in_array($provider, ['google', 'facebook'])) {
            return redirect()->route('login')->with('error', 'Invalid provider');
        }
        
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
            
            $user = User::where('email', $socialUser->getEmail())->first();
            
            if (!$user) {
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
                
                $user->addRole('user');
                
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
                if (!$user->social_id) {
                    $user->update([
                        'social_id' => $socialUser->getId(),
                        'social_type' => $provider,
                        'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                    ]);
                }
            }
            
            Auth::login($user, true);
            
            $user->updateLastLogin();
            
            return redirect()->intended('/dashboard')
                ->with('success', 'Successfully logged in with ' . ucfirst($provider));
            
        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Social login failed. Please try again. ' . $e->getMessage());
        }
    }
}