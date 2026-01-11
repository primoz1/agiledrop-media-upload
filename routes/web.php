<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function () {
    if (request()->expectsJson()) {
        abort(401);
    }

    abort(501, 'Not implemented yet');
})->name('login');

Route::get('/get-token', \App\Http\Controllers\Dev\TokenController::class);