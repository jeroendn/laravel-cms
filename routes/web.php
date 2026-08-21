<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\PageGroupController as AdminPageGroupController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\LanguageController;
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

// Admin area: every authenticated user is an admin. Registered before the
// public catch-alls, so /admin can never be swallowed by a page slug.
Route::middleware(['auth', NoIndex::class])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::resource('pages', AdminPageController::class)->except('show');
    Route::resource('page-groups', AdminPageGroupController::class)->except('show');
    Route::post('users/{user}/reset-link', [AdminUserController::class, 'resetLink'])->name('users.reset-link');
    Route::resource('users', AdminUserController::class)->except('show');
    Route::get('settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [AdminSettingController::class, 'update'])->name('settings.update');
});

Route::post('language/{locale}', LanguageController::class)->name('language.switch');

Route::middleware(UnderConstruction::class)->group(function (): void {
    Route::get('/', [PageController::class, 'home'])->name('home');
    // The home page's own slug keeps working; its canonical URL is /.
    Route::redirect('/home', '/', 301);

    // The dynamic page/group URLs, last on purpose: every literal route
    // above wins first. The binders (AppServiceProvider) 404 unknown paths
    // before the UnderConstruction middleware can answer.
    $slug = '[a-z0-9-]+';
    Route::get('/{item}', [PageController::class, 'show'])
        ->where('item', $slug)->name('pages.show');
    Route::get('/{group}/{item}', [PageController::class, 'showInGroup'])
        ->where(['group' => $slug, 'item' => $slug])->name('pages.grouped');
    Route::get('/{group}/{subgroup}/{item}', [PageController::class, 'showInSubgroup'])
        ->where(['group' => $slug, 'subgroup' => $slug, 'item' => $slug])->name('pages.subgrouped');
});
