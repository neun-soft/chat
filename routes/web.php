<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\WorkspaceAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\ChannelAdminController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('chat')
        : redirect()->route('login');
})->name('home');

// After-login landing redirects into the chat app.
Route::get('dashboard', fn () => redirect()->route('chat'))
    ->middleware(['auth'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Chat shell. Optional workspace/channel deep-links resolve client-side.
    Route::get('chat/{workspace?}/{channel?}', fn () => Inertia::render('chat/Index'))->name('chat');

    // Workspaces the current user belongs to.
    Route::get('api/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
    Route::get('api/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');

    // Channel messages.
    Route::get('api/channels/{channel}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('api/channels/{channel}/messages', [MessageController::class, 'store'])->name('messages.store');

    // Admin (workspace owner only — gated by `admin` middleware).
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');

        Route::get('workspaces', [WorkspaceAdminController::class, 'index'])->name('workspaces.index');
        Route::post('workspaces', [WorkspaceAdminController::class, 'store'])->name('workspaces.store');
        // Workspace binds by slug elsewhere (chat deep-links); admin works by id.
        Route::get('workspaces/{workspace:id}', [WorkspaceAdminController::class, 'show'])->name('workspaces.show');
        Route::patch('workspaces/{workspace:id}', [WorkspaceAdminController::class, 'update'])->name('workspaces.update');

        Route::post('workspaces/{workspace:id}/members', [WorkspaceAdminController::class, 'addMember'])->name('workspaces.members.add');
        Route::delete('workspaces/{workspace:id}/members/{user}', [WorkspaceAdminController::class, 'removeMember'])->name('workspaces.members.remove');

        Route::post('workspaces/{workspace:id}/channels', [ChannelAdminController::class, 'store'])->name('channels.store');
        Route::patch('channels/{channel}', [ChannelAdminController::class, 'update'])->name('channels.update');
        Route::delete('channels/{channel}', [ChannelAdminController::class, 'destroy'])->name('channels.destroy');
        Route::post('channels/{channel}/members', [ChannelAdminController::class, 'syncMembers'])->name('channels.members.sync');

        // Create client logins manually (no email invites).
        Route::post('users', [UserAdminController::class, 'store'])->name('users.store');
        Route::patch('users/{user}', [UserAdminController::class, 'update'])->name('users.update');
    });
});

require __DIR__.'/settings.php';
