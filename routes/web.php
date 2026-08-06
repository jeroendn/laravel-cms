<?php

use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login/logout + password reset only: this is a client blog, public
// registration (and thus e-mail verification) stays disabled on purpose.
Auth::routes([
    'register' => false,
    'verify' => false,
    'confirm' => false,
]);

Route::get('/', [PostController::class, 'index'])->name('home');
Route::get('/blog/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Admin area: every authenticated user is an admin (registration is
// disabled; accounts only exist for the client and Jeroen, see CLAUDE.md).
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::resource('posts', AdminPostController::class)->except('show');
});
