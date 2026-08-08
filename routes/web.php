<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
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

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('/blog', [PostController::class, 'index'])->name('posts.index');
Route::get('/blog/{post:slug}', [PostController::class, 'show'])->name('posts.show');

// Admin area: every authenticated user is an admin (registration is
// disabled; accounts only exist for the client and Jeroen, see CLAUDE.md).
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('posts', AdminPostController::class)->except('show');
    Route::post('users/{user}/reset-link', [AdminUserController::class, 'resetLink'])->name('users.reset-link');
    Route::resource('users', AdminUserController::class)->except('show');
});
