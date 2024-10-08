<?php

namespace App\Providers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;
use App\Actions\Fortify\LoginUser;
use Illuminate\Support\Facades\Route;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use Illuminate\Support\Facades\RateLimiter;
use App\Actions\Fortify\EditedCreateNewUser;
use App\Actions\Fortify\LoginAuthenticateThrough;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Actions\Fortify\EditedUpdateUserProfileInformation;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
        $this->configureRoutes();

        Fortify::loginView('auth.edited_login');
        Fortify::authenticateUsing([new LoginUser,'__invoke']);
        Fortify::registerView('auth.edited_register');
        Fortify::createUsersUsing(EditedCreateNewUser::class);
        Fortify::authenticateThrough([new LoginAuthenticateThrough,'__invoke']);
        Fortify::verifyEmailView('auth.edited_verify-email');
        Fortify::resetPasswordView('auth.edited_reset-password');
        Fortify::requestPasswordResetLinkView('auth.edited_forgot-password');
        Fortify::updateUserProfileInformationUsing(EditedUpdateUserProfileInformation::class);
        Fortify::confirmPasswordView('auth.edited_confirm-password');
    }
    /**
     * Configure the routes offered by the application.
     *
     * @return void
     */
    protected function configureRoutes()
    {
        Route::group([
            'namespace' => 'Laravel\Fortify\Http\Controllers',
            'domain' => config('fortify.domain', null),
            'prefix' => config('fortify.prefix'),
        ], function () {
            $this->loadRoutesFrom(base_path('routes/fortify.php'));
        });
    }
}
