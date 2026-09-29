<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Kitchen board SPA (Vue). Authentication happens against the API with a staff token.
Route::view('/kitchen', 'kitchen');
