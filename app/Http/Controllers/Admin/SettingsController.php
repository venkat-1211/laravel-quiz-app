<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];
        
        $cacheStats = [
            'quiz_list' => Cache::has('queries.quizzes.published.*'),
            'categories' => Cache::has('categories.active'),
            'leaderboard' => Cache::has('leaderboard.global'),
        ];
        
        return view('admin.settings.index', compact('settings', 'cacheStats'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'app_name' => 'required|string|max:255',
        ]);

        // Update .env file
        $this->updateEnvironmentFile([
            'APP_NAME' => '"' . $request->app_name . '"',
        ]);

        return redirect()->route('admin.settings')
            ->with('success', 'Settings updated successfully.');
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        
        return back()->with('success', 'All caches cleared successfully.');
    }

    private function updateEnvironmentFile($data)
    {
        $envFile = base_path('.env');
        $content = file_get_contents($envFile);
        
        foreach ($data as $key => $value) {
            $content = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $content
            );
        }
        
        file_put_contents($envFile, $content);
    }
}