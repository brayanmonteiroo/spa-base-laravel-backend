<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
    ]);
});

// Rotas Públicas
Route::post('/login', [LoginController::class, 'store']);
Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
Route::post('/reset-password', [ResetPasswordController::class, 'store']);

// Rotas Protegidas por Autenticação via Sanctum
Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', function (Request $request) {
        return new UserResource($request->user());
    });

    Route::post('/logout', [LogoutController::class, 'destroy']);

    Route::prefix('admin')->group(function (): void {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('permissions/catalog', [RoleController::class, 'catalog']);
        Route::get('audits', [AuditController::class, 'index']);
        Route::get('audits/{audit}', [AuditController::class, 'show']);
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('users', UserController::class);
    });
});
