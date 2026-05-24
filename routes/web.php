<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'API Running']);
});

Route::get('/test-session', function () {
    return config('session.driver');
});
Route::get('/login', function () {
    return response()->json(['message' => 'Login page not available for API']);
})->name('login');