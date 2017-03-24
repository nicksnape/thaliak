<?php

namespace Thaliak\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Socialite;
use Thaliak\Models\Character;
use Thaliak\Models\OAuthUser;
use Thaliak\Models\User;
use Thaliak\Models\World;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Route::model('character', Character::class);
        Route::model('auth', OAuthUser::class);

        Route::bind('user', function ($user) {
            if ($user === 'me') {
                return request()->user();
            }

            return User::findOrFail($user);
        });

        Route::bind('world', function ($world) {
            return World::whereName(ucfirst($world))->first();
        });
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        Route::group([
            'middleware' => 'api',
            'prefix' => 'api'
        ], function ($router) {
            require base_path('routes/api.php');
        });
    }
}
