<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageGroupController as AdminPageGroupController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\PageController;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\UnderConstruction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Login/logout + password reset only: this is a client website, public
// registration (and thus e-mail verification) stays disabled on purpose.
Route::middleware(NoIndex::class)->group(function (): void {
    Auth::routes([
        'register' => false,
        'verify' => false,
        'confirm' => false,
    ]);
});

Route::middleware(UnderConstruction::class)->group(function (): void {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/blog', [PageController::class, 'index'])->name('pages.index');
    Route::get('/blog/{page:slug}', [PageController::class, 'show'])->name('pages.show');
});

// Admin area: every authenticated user is an admin.
Route::middleware(['auth', NoIndex::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('pages', AdminPageController::class)->except('show');
    Route::resource('page-groups', AdminPageGroupController::class)->except('show');
    Route::post('users/{user}/reset-link', [AdminUserController::class, 'resetLink'])->name('users.reset-link');
    Route::resource('users', AdminUserController::class)->except('show');
});
