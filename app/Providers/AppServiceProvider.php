<?php

namespace App\Providers;

use App\Services\OrderService;
use App\Services\Payments\PaymentGatewayFactory;
use App\Services\Transactions\YooKassaTransactionService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(OrderService::class, function ($app) {
            return new OrderService(
                $app->make(YooKassaTransactionService::class),
                PaymentGatewayFactory::make(),
            );
        });
        $this->app->bind(YooKassaTransactionService::class, function ($app) {
            return new YooKassaTransactionService(PaymentGatewayFactory::make());
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        RateLimiter::for('yookassa-webhook', function (Request $request) {
            return Limit::perMinute(300)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    Log::channel('payments')->warning("Rate Limiter: Превышен лимит вебхуков ЮKassa с IP: {$request->ip()}");
                    return response()->json(['error' => 'Too Many Requests'], 429, $headers);
                });
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
