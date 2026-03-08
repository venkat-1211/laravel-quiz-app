<?php

if (!function_exists('formatTime')) {
    function formatTime(int $seconds, string $format = 'full'): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $seconds = $seconds % 60;
        
        switch ($format) {
            case 'full':
                $parts = [];
                if ($hours > 0) $parts[] = $hours . 'h';
                if ($minutes > 0) $parts[] = $minutes . 'm';
                if ($seconds > 0) $parts[] = $seconds . 's';
                return implode(' ', $parts);
                
            case 'compact':
                if ($hours > 0) {
                    return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
                }
                return sprintf('%d:%02d', $minutes, $seconds);
                
            case 'colon':
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                
            default:
                return "$hours:$minutes:$seconds";
        }
    }
}

if (!function_exists('getDifficultyBadge')) {
    /**
     * Get Bootstrap badge class based on difficulty level
     *
     * @param string $difficulty
     * @return string
     */
    function getDifficultyBadge(string $difficulty): string
    {
        $badges = [
            'beginner' => 'bg-success',
            'intermediate' => 'bg-info',
            'advanced' => 'bg-warning text-dark',
            'expert' => 'bg-danger',
            'easy' => 'bg-success',
            'medium' => 'bg-warning text-dark',
            'hard' => 'bg-danger',
        ];

        return $badges[strtolower($difficulty)] ?? 'bg-secondary';
    }
}

if (!function_exists('getDifficultyText')) {
    /**
     * Get human-readable difficulty text
     *
     * @param string $difficulty
     * @return string
     */
    function getDifficultyText(string $difficulty): string
    {
        $texts = [
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced',
            'expert' => 'Expert',
            'easy' => 'Easy',
            'medium' => 'Medium',
            'hard' => 'Hard',
        ];

        return $texts[strtolower($difficulty)] ?? ucfirst($difficulty);
    }
}

if (!function_exists('getInitials')) {
    /**
     * Get initials from a name
     *
     * @param string $name
     * @return string
     */
    function getInitials(string $name): string
    {
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }

        return substr($initials, 0, 2);
    }
}

if (!function_exists('truncateText')) {
    /**
     * Truncate text to a specified length
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    function truncateText(string $text, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . $suffix;
    }
}

if (!function_exists('calculatePercentage')) {
    /**
     * Calculate percentage
     *
     * @param int $value
     * @param int $total
     * @param int $decimals
     * @return float
     */
    function calculatePercentage(int $value, int $total, int $decimals = 2): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($value / $total) * 100, $decimals);
    }
}

if (!function_exists('getScoreColor')) {
    /**
     * Get color class based on score percentage
     *
     * @param float $score
     * @return string
     */
    function getScoreColor(float $score): string
    {
        if ($score >= 80) {
            return 'text-success';
        } elseif ($score >= 60) {
            return 'text-primary';
        } elseif ($score >= 40) {
            return 'text-warning';
        }

        return 'text-danger';
    }
}

if (!function_exists('getScoreBadge')) {
    /**
     * Get badge class based on score percentage
     *
     * @param float $score
     * @return string
     */
    function getScoreBadge(float $score): string
    {
        if ($score >= 80) {
            return 'bg-success';
        } elseif ($score >= 60) {
            return 'bg-primary';
        } elseif ($score >= 40) {
            return 'bg-warning';
        }

        return 'bg-danger';
    }
}

if (!function_exists('getRandomColor')) {
    /**
     * Generate random color for charts
     *
     * @param int $index
     * @return string
     */
    function getRandomColor(int $index = 0): string
    {
        $colors = [
            '#667eea', '#764ba2', '#f39c12', '#e74c3c', '#27ae60',
            '#3498db', '#9b59b6', '#f1c40f', '#1abc9c', '#e67e22',
            '#2c3e50', '#95a5a6', '#34495e', '#16a085', '#d35400'
        ];

        return $colors[$index % count($colors)];
    }
}

if (!function_exists('getTimeAgo')) {
    /**
     * Get human-readable time ago
     *
     * @param $datetime
     * @return string
     */
    function getTimeAgo($datetime): string
    {
        if (!$datetime) {
            return 'Never';
        }

        $time = $datetime instanceof \Carbon\Carbon ? $datetime : \Carbon\Carbon::parse($datetime);
        return $time->diffForHumans();
    }
}

if (!function_exists('getOrdinalSuffix')) {
    /**
     * Get ordinal suffix for numbers (1st, 2nd, 3rd, etc.)
     *
     * @param int $number
     * @return string
     */
    function getOrdinalSuffix(int $number): string
    {
        if (!in_array(($number % 100), [11, 12, 13])) {
            switch ($number % 10) {
                case 1:
                    return $number . 'st';
                case 2:
                    return $number . 'nd';
                case 3:
                    return $number . 'rd';
            }
        }
        return $number . 'th';
    }
}

if (!function_exists('getFileSize')) {
    /**
     * Get human-readable file size
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    function getFileSize(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}

if (!function_exists('getTimeRemaining')) {
    /**
     * Get formatted time remaining
     *
     * @param int $seconds
     * @return string
     */
    function getTimeRemaining(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . ' min ' . ($remainingSeconds ? $remainingSeconds . ' sec' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            return $hours . ' hr ' . ($minutes ? $minutes . ' min' : '');
        }
    }
}

if (!function_exists('getProgressColor')) {
    /**
     * Get progress bar color based on percentage
     *
     * @param float $percentage
     * @return string
     */
    function getProgressColor(float $percentage): string
    {
        if ($percentage >= 80) {
            return 'bg-success';
        } elseif ($percentage >= 50) {
            return 'bg-info';
        } elseif ($percentage >= 30) {
            return 'bg-warning';
        }

        return 'bg-danger';
    }
}

if (!function_exists('getDeviceType')) {
    /**
     * Detect device type from user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    function getDeviceType(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            return 'Mobile';
        }

        if (preg_match('/iPad|tablet|kindle|silk/i', $userAgent)) {
            return 'Tablet';
        }

        return 'Desktop';
    }
}

if (!function_exists('getBrowser')) {
    /**
     * Get browser name from user agent
     *
     * @param string|null $userAgent
     * @return string
     */
    function getBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown';
        }

        if (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edge') === false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        }

        return 'Other';
    }
}

if (!function_exists('generateSlug')) {
    /**
     * Generate URL-friendly slug
     *
     * @param string $text
     * @return string
     */
    function generateSlug(string $text): string
    {
        return \Illuminate\Support\Str::slug($text);
    }
}

if (!function_exists('getReadingTime')) {
    /**
     * Estimate reading time for text
     *
     * @param string $text
     * @param int $wordsPerMinute
     * @return int
     */
    function getReadingTime(string $text, int $wordsPerMinute = 200): int
    {
        $wordCount = str_word_count(strip_tags($text));
        return ceil($wordCount / $wordsPerMinute);
    }
}

if (!function_exists('getSimilarity')) {
    /**
     * Calculate similarity between two strings
     *
     * @param string $str1
     * @param string $str2
     * @return float
     */
    function getSimilarity(string $str1, string $str2): float
    {
        similar_text($str1, $str2, $percent);
        return round($percent, 2);
    }
}

if (!function_exists('generateRandomCode')) {
    /**
     * Generate random code
     *
     * @param int $length
     * @param bool $letters
     * @param bool $numbers
     * @return string
     */
    function generateRandomCode(int $length = 6, bool $letters = true, bool $numbers = true): string
    {
        $characters = '';
        
        if ($letters) {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }
        
        if ($numbers) {
            $characters .= '0123456789';
        }
        
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }
}

if (!function_exists('maskEmail')) {
    /**
     * Mask email address for privacy
     *
     * @param string $email
     * @return string
     */
    function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        $name = $parts[0];
        $domain = $parts[1] ?? '';

        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 4)) . substr($name, -2);

        return $maskedName . '@' . $domain;
    }
}

if (!function_exists('formatTimeLimit')) {
    function formatTimeLimit($minutes) {
        if (!$minutes) return 'N/A';
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours > 0 ? $hours . 'h ' . $mins . 'm' : $mins . ' min';
    }
}

if (!function_exists('calculateAchievementProgress')) {
    function calculateAchievementProgress($user, $achievement)
    {
        $criteria = $achievement->criteria;
        
        if (!$criteria || !isset($criteria['type'])) {
            return 0;
        }
        
        switch ($criteria['type']) {
            case 'quizzes_completed':
                $count = $user->attempts()->where('status', 'completed')->count();
                $target = $criteria['value'] ?? 1;
                return $target > 0 ? min(100, round(($count / $target) * 100)) : 0;
                
            case 'total_points':
                $points = $user->leaderboard->total_points ?? 0;
                $target = $criteria['value'] ?? 1000;
                return $target > 0 ? min(100, round(($points / $target) * 100)) : 0;
                
            case 'average_score':
                $avg = $user->average_score;
                $target = $criteria['value'] ?? 80;
                return $target > 0 ? min(100, round(($avg / $target) * 100)) : 0;
                
            case 'perfect_score':
                $count = $user->attempts()->where('status', 'completed')->where('percentage_score', 100)->count();
                $target = $criteria['value'] ?? 1;
                return $target > 0 ? min(100, round(($count / $target) * 100)) : 0;
                
            case 'speed_demon':
                $count = $user->attempts()->where('status', 'completed')
                    ->whereRaw('time_taken <= quizzes.time_limit * 60 * 0.5')
                    ->join('quizzes', 'attempts.quiz_id', '=', 'quizzes.id')
                    ->count();
                $target = $criteria['value'] ?? 1;
                return $target > 0 ? min(100, round(($count / $target) * 100)) : 0;
                
            case 'top_rank':
                $rank = $user->leaderboard->rank ?? 999;
                return $rank == 1 ? 100 : 0;
                
            default:
                return 0;
        }
    }
}