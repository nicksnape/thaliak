<?php

use Illuminate\Routing\Router;

// Thaliak API
Route::group(['namespace' => 'Thaliak\Http\Controllers\Api'], function (Router $r) {
    // World context
    $r->group([
        'domain'    => '{world}.' . config('app.domain'),
        'namespace' => 'World'
    ], function (Router $r) {
        // Users
        $r->group(['prefix' => 'users'], function (Router $r) {
            $r->get('/', 'UsersController@index');
            $r->get('totals', 'UsersController@totals');
            $r->post('search', 'UsersController@search');
            $r->post('/', 'UsersController@create');
            $r->post('verify', 'UserController@verify');

            $r->group(['prefix' => '{user}'], function (Router $r) {
                $r->get('/', 'UsersController@get');
                $r->get('characters', 'UsersController@characters');
                $r->patch('/', 'UsersController@update');
                $r->patch('state', 'UsersController@updateState');
                $r->post('clear-token', 'UsersController@clearToken');
                $r->delete('/', 'UsersController@delete');
            });
        });

        // Characters
        $r->group(['prefix' => 'characters'], function (Router $r) {
            $r->get('/', 'CharactersController@index');
            $r->get('totals', 'CharactersController@totals');
            $r->post('search', 'CharactersController@search');
            $r->post('/', 'CharactersController@add');

            $r->group(['prefix' => '{character}'], function (Router $r) {
                $r->get('/', 'CharactersController@get');
                $r->post('verify', 'CharactersController@verify');
                $r->post('set-main', 'CharactersController@setMain');
                $r->patch('/', 'CharactersController@update');
                $r->delete('/', 'CharactersController@delete');
            });
        });
    });

    // Social auth
    $r->get('social/drivers', 'SocialAuthController@drivers');
    $r->group(['prefix' => 'social/{provider}/auth'], function (Router $r) {
        $r->get('/', 'SocialAuthController@redirect');
        $r->get('receive', 'SocialAuthController@receive');
        $r->delete('{auth}', 'SocialAuthController@delete');
    });
});

// Authentication (Passport)
Route::group([
    'prefix' => 'auth',
    'namespace' => 'Laravel\Passport\Http\Controllers'
], function (Router $r) {
    $r->group(['prefix' => 'token'], function (Router $r) {
        $r->post('/', [
            'middleware' => ['handle-grant-injections', 'attach-token-cookie'],
            'uses' => 'AccessTokenController@issueToken'
        ]);
        $r->post('refresh', [
            'middleware' => ['handle-grant-injections', 'attach-token-cookie'],
            'uses' => 'TransientTokenController@refresh'
        ]);
    });
});
