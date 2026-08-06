<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login/logout + password reset only: this is a client blog, public
// registration (and thus e-mail verification) stays disabled on purpose.
Auth::routes([
    'register' => false,
    'verify' => false,
    'confirm' => false,
]);

Route::view('/', 'home')->name('home');
