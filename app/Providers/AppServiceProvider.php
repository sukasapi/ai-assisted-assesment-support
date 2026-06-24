<?php

namespace App\Providers;

use App\Models\Assessment;
use App\Models\AssessmentSession;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Policies\AssessmentPolicy;
use App\Policies\AssessmentSessionPolicy;
use App\Policies\UserPolicy;
use App\Services\Ai\ChatClientContract;
use App\Services\Ai\GeminiChatClient;
use App\Services\Ai\OpenRouterClient;
use App\Services\Stt\GroqSttTranscriber;
use App\Services\Stt\OpenRouterSttTranscriber;
use App\Services\Stt\TranscriberContract;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

/**
 * Perintah Artisan ini menghapus skema/data — diblokir di lingkungan non-testing
 * kecuali ALLOW_MIGRATE_FRESH=true di .env (agar tidak terhapus tidak sengaja).
 *
 * @see guardDestructiveSchemaCommands()
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TranscriberContract::class, function ($app) {
            $penyedia = (string) config('stt.penyedia', 'groq');

            return match ($penyedia) {
                'openrouter' => $app->make(OpenRouterSttTranscriber::class),
                default => $app->make(GroqSttTranscriber::class),
            };
        });

        $this->app->bind(ChatClientContract::class, function ($app) {
            $penyedia = (string) config('ai.penyedia', 'openrouter');

            return match ($penyedia) {
                'gemini' => $app->make(GeminiChatClient::class),
                default => $app->make(OpenRouterClient::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Gate::policy(Assessment::class, AssessmentPolicy::class);
        Gate::policy(AssessmentSession::class, AssessmentSessionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        $this->registerAiRateLimiters();
        RateLimiter::for('login', function (Request $request): Limit {
            $kunci = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($kunci);
        });

        RateLimiter::for('konsultan-token', function (Request $request): Limit {
            return Limit::perMinute(10)->by('konsultan-token:'.($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id ? 'api:'.$request->user()->id : 'api-ip:'.$request->ip());
        });

        $this->guardDestructiveSchemaCommands();
    }

    /**
     * Mencegah penggunaan tidak sengaja migrate:fresh / refresh yang mengosongkan DB.
     */
    private function guardDestructiveSchemaCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $argv = $_SERVER['argv'] ?? [];
        $destructive = [
            'migrate:fresh',
            'migrate:refresh',
            'migrate:reset',
            'db:wipe',
        ];

        $matched = null;
        foreach ($argv as $arg) {
            if (in_array($arg, $destructive, true)) {
                $matched = $arg;
                break;
            }
        }

        if ($matched === null) {
            return;
        }

        if ($this->app->environment('testing')) {
            return;
        }

        if (filter_var(env('ALLOW_MIGRATE_FRESH', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $nl = PHP_EOL;
        fwrite(STDERR, $nl);
        fwrite(STDERR, str_repeat('=', 67).$nl);
        fwrite(STDERR, '  Perintah "'.$matched.'" DIBATALKAN — dapat menghapus SEMUA data & skema.'.$nl.$nl);
        fwrite(STDERR, '  Untuk perubahan skema biasa gunakan: php artisan migrate'.$nl.$nl);
        fwrite(STDERR, '  Jika memang perlu database bersih (mis. setup ulang), tambahkan di .env:'.$nl);
        fwrite(STDERR, '    ALLOW_MIGRATE_FRESH=true'.$nl.$nl);
        fwrite(STDERR, '  lalu jalankan lagi perintah tersebut.'.$nl);
        fwrite(STDERR, str_repeat('=', 67).$nl);
        fwrite(STDERR, $nl);

        exit(1);
    }

    private function registerAiRateLimiters(): void
    {
        RateLimiter::for('ai-analysis-trigger', function (Request $request): Limit {
            $limit = max(1, (int) config('ai.throttle.request_per_menit', 20));
            $id = $request->user()?->getAuthIdentifier() ?? $request->ip();

            return Limit::perMinute($limit)->by('ai-trigger:'.$id);
        });

        RateLimiter::for('ai-analysis-job', function (): Limit {
            $limit = max(1, (int) config('ai.throttle.job_per_menit', 30));

            return Limit::perMinute($limit)->by('ai-job:openrouter');
        });
    }
}
