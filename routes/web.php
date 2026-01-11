<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    abort(501, 'Not implemented yet');
})->name('login');

Route::get('/get-token', function () {

    abort_if(!app()->isLocal(), 404);

    $user = \App\Models\User::firstOrCreate(
        ['email' => 'api@example.com'],
        [
            'name' => 'API User',
            'password' => bcrypt('password'),
        ]
    );

    // Optional: revoke old tokens so you always get a fresh one
    $user->tokens()->delete();

    return $user->createToken('media-api')->plainTextToken;
});;
