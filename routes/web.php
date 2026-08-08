<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PostController;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\UnderConstruction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login/logout + password reset only: this is a client blog, public
// registration (and thus e-mail verification) stays disabled on purpose.
Route::middleware(NoIndex::class)->group(function (): void {
    Auth::routes([
        'register' => false,
        'verify' => false,
        'confirm' => false,
    ]);
});

Route::middleware(UnderConstruction::class)->group(function (): void {
    Route::get('/', [PostController::class, 'home'])->name('home');
    Route::get('/blog', [PostController::class, 'index'])->name('posts.index');
    Route::get('/blog/{post:slug}', [PostController::class, 'show'])->name('posts.show');
});

// Admin area: every authenticated user is an admin.
Route::middleware(['auth', NoIndex::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('posts', AdminPostController::class)->except('show');
    Route::post('users/{user}/reset-link', [AdminUserController::class, 'resetLink'])->name('users.reset-link');
    Route::resource('users', AdminUserController::class)->except('show');
});
