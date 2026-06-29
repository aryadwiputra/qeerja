<?php

use App\Http\Controllers\Api\SocketAuthController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/socket/token', [SocketAuthController::class, 'token'])->name('api.socket.token');
    Route::get('/notifications/recent', [NotificationController::class, 'recent'])->name('api.notifications.recent');
});

Route::post('/socket/auth', [SocketAuthController::class, 'auth'])->name('api.socket.auth');
