<?php

use App\Http\Controllers\AdminAppointmentController;
use App\Http\Controllers\AdminChatController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminProfileController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientChatController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register', [RegistrationController::class, 'email'])->name('register');
    Route::post('/register/send-code', [RegistrationController::class, 'sendCode'])->middleware('throttle:5,10');
    Route::get('/register/verify', [RegistrationController::class, 'verify'])->name('register.verify');
    Route::post('/register/verify-code', [RegistrationController::class, 'verifyCode'])->middleware('throttle:10,10');
    Route::get('/register/complete', [RegistrationController::class, 'complete'])->name('register.complete');
    Route::post('/register/complete', [RegistrationController::class, 'store'])->middleware('throttle:5,10');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode'])->middleware('throttle:5,10');
    Route::get('/reset-password', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,10');
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

// Shared notification routes (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
});

Route::prefix('client')->middleware(['auth', 'role:client'])->name('client.')->group(function () {
    Route::get('/dashboard', ClientDashboardController::class)->name('dashboard');

    Route::get('/appointment', [AppointmentController::class, 'create'])->name('appointment');
    Route::post('/appointment', [AppointmentController::class, 'store']);
    Route::post('/appointment/{id}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointment.reschedule');
    Route::post('/appointment/{id}/cancel', [AppointmentController::class, 'cancel'])->name('appointment.cancel');

    Route::get('/ai', fn () => redirect()->route('client.chat'));
    Route::get('/chat', [ClientChatController::class, 'page'])->name('chat');
    Route::get('/chat/messages', [ClientChatController::class, 'show']);
    Route::post('/chat/messages', [ClientChatController::class, 'send'])->middleware('throttle:30,1');
    Route::post('/chat/request-live', [ClientChatController::class, 'requestLiveSupport'])->middleware('throttle:5,10');
    Route::post('/chat/return-to-ai', [ClientChatController::class, 'returnToAi'])->middleware('throttle:5,10');

    Route::get('/profile', [ProfileController::class, 'page'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->middleware('throttle:10,10');
    Route::post('/onboarding/complete', [ProfileController::class, 'completeOnboarding']);

    Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback');
    Route::post('/feedback', [FeedbackController::class, 'store']);
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->name('admin.')->group(function () {
    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    Route::get('/live-chat', [AdminChatController::class, 'page'])->name('live-chat');
    Route::get('/chat/conversations', [AdminChatController::class, 'index']);
    Route::get('/chat/conversations/{conversation}', [AdminChatController::class, 'show']);
    Route::post('/chat/conversations/{conversation}/claim', [AdminChatController::class, 'claim']);
    Route::post('/chat/conversations/{conversation}/messages', [AdminChatController::class, 'send'])->middleware('throttle:60,1');
    Route::post('/chat/conversations/{conversation}/end', [AdminChatController::class, 'end']);
    Route::post('/chat/conversations/{conversation}/close', [AdminChatController::class, 'close']);

    Route::get('/appointments', [AdminAppointmentController::class, 'index'])->name('appointments');
    Route::post('/appointments/{id}/approve', [AdminAppointmentController::class, 'approve']);
    Route::post('/appointments/{id}/cancel', [AdminAppointmentController::class, 'cancel']);
    Route::post('/appointments/{id}/complete', [AdminAppointmentController::class, 'complete']);
    Route::get('/archive', [AdminAppointmentController::class, 'archive'])->name('archive');

    Route::get('/products', [ProductController::class, 'index'])->name('products');
    Route::post('/products', [ProductController::class, 'store']);
    Route::post('/products/{id}/sell', [ProductController::class, 'sell']);

    Route::get('/mechanics', [MechanicController::class, 'index'])->name('mechanics');
    Route::post('/mechanics', [MechanicController::class, 'store']);

    Route::get('/services', [ServiceController::class, 'index'])->name('services');
    Route::post('/services', [ServiceController::class, 'store']);

    Route::get('/analytics', AnalyticsController::class)->name('analytics');

    Route::get('/profile', [AdminProfileController::class, 'page'])->name('profile');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'password'])->name('profile.password');
});
