<?php

use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\BannerImageController;
use App\Http\Controllers\Api\Admin\ImageUploadController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\PostCoverController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\PostController;
use App\Models\Banner;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        // Cadastro: register -> (e-mail com código) -> verify-email, que já devolve a sessão
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-email', [AuthController::class, 'verifyEmail']);
        Route::post('resend-code', [AuthController::class, 'resendCode']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

// Site público: só notícias publicadas (página inicial, /noticias e /noticias/{id})
Route::get('posts', [PostController::class, 'index']);
Route::get('posts/{id}', [PostController::class, 'show'])->whereNumber('id');
Route::get('banners/{key}', [BannerController::class, 'show'])->whereIn('key', Banner::KEYS);

// Painel (área restrita): CRUD completo, exige token Bearer
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::apiResource('posts', AdminPostController::class);
    Route::post('posts/{post}/cover', [PostCoverController::class, 'store']);
    Route::delete('posts/{post}/cover', [PostCoverController::class, 'destroy']);
    Route::post('uploads/images', [ImageUploadController::class, 'store']);

    // Banners (hero) das páginas do site: chaves fixas em Banner::KEYS
    Route::get('banners/{key}', [AdminBannerController::class, 'show'])->whereIn('key', Banner::KEYS);
    Route::put('banners/{key}', [AdminBannerController::class, 'update'])->whereIn('key', Banner::KEYS);
    Route::post('banners/{key}/image', [BannerImageController::class, 'store'])->whereIn('key', Banner::KEYS);
    Route::delete('banners/{key}/image', [BannerImageController::class, 'destroy'])->whereIn('key', Banner::KEYS);
});
