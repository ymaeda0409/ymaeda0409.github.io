<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Kitchen board SPA (Vue). Authentication happens against the API with a staff token.
Route::view('/kitchen', 'kitchen');

// Back-office SPA (Vue, hash routes such as /admin#/sales). Same staff token as the kitchen board.
Route::view('/admin', 'admin');
