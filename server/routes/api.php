<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - سراج ERP
|--------------------------------------------------------------------------
*/

// 1. مسارات المصادقة (عامة)
Route::post('/auth/login', [AuthController::class, 'login'])->name('login');

// 2. مسارات محمية (تتطلب توكن)
Route::middleware(['auth:sanctum', 'check.permission'])->group(function () {

    // 2.1 المصادقة
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // 2.2 إدارة المستخدمين (خاصة بالمدير العام)
    Route::get('/users', [UserController::class, 'index']);          // قائمة المستخدمين
    Route::post('/users', [UserController::class, 'store']);         // إنشاء مستخدم جديد
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);

    // 2.3 الأدوار والصلاحيات (للإعدادات)
    Route::get('/roles', [UserController::class, 'roles']);
    Route::get('/permissions', [UserController::class, 'permissions']);
});
