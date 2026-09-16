<?php

use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\BannerImageController;
use App\Http\Controllers\Api\Admin\ImageUploadController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\CarouselSlideController as AdminCarouselSlideController;
use App\Http\Controllers\Api\Admin\CarouselSlideImageController;
use App\Http\Controllers\Api\Admin\HomeCounterController;
use App\Http\Controllers\Api\Admin\HomeSectionController;
use App\Http\Controllers\Api\Admin\PostCoverController;
use App\Http\Controllers\Api\Admin\TestimonialController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CarouselSlideController;
use App\Http\Controllers\Api\HomeContentController;
use App\Http\Controllers\Api\SiteRouteController;
use App\Http\Controllers\Api\SectionPageController;
use App\Http\Controllers\Api\PostController;
use App\Models\Banner;
use App\Models\HomeSection;
use App\Support\SectionPages;
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

// Carrossel do topo da página inicial: slides ativos, na ordem do painel
Route::get('carousel-slides', [CarouselSlideController::class, 'index']);

// Conteúdo da página inicial: blocos, contadores e depoimentos, de uma vez
Route::get('home', [HomeContentController::class, 'index']);

// Rotas do site que um botão do painel pode apontar (select de destino)
Route::get('site/rotas', [SiteRouteController::class, 'index']);

// Páginas internas de uma seção: só a relação de rotas (slug + nome)
Route::get('secoes/{secao}/paginas', [SectionPageController::class, 'index'])
    ->whereIn('secao', SectionPages::sections());

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

    // Carrossel da página inicial: CRUD, imagem e ordem dos slides
    Route::post('carousel-slides/reorder', [AdminCarouselSlideController::class, 'reorder']);
    Route::apiResource('carousel-slides', AdminCarouselSlideController::class);
    Route::post('carousel-slides/{carousel_slide}/image', [CarouselSlideImageController::class, 'store']);
    Route::delete('carousel-slides/{carousel_slide}/image', [CarouselSlideImageController::class, 'destroy']);

    // Página inicial: blocos de chave fixa, contadores e depoimentos
    Route::get('home/sections/{key}', [HomeSectionController::class, 'show'])->whereIn('key', HomeSection::KEYS);
    Route::patch('home/sections/{key}', [HomeSectionController::class, 'update'])->whereIn('key', HomeSection::KEYS);
    Route::post('home/sections/{key}/image', [HomeSectionController::class, 'storeImage'])->whereIn('key', HomeSection::KEYS);
    Route::delete('home/sections/{key}/image', [HomeSectionController::class, 'destroyImage'])->whereIn('key', HomeSection::KEYS);

    Route::apiResource('home/counters', HomeCounterController::class)->except('show')->parameters(['counters' => 'counter']);

    Route::apiResource('home/testimonials', TestimonialController::class)->except('show')->parameters(['testimonials' => 'testimonial']);
    Route::post('home/testimonials/{testimonial}/photo', [TestimonialController::class, 'storePhoto']);
    Route::delete('home/testimonials/{testimonial}/photo', [TestimonialController::class, 'destroyPhoto']);

    // Usuários do painel: listagem com filtros e troca de situação
    Route::get('users', [AdminUserController::class, 'index']);
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus']);
});
