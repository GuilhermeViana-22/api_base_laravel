<?php

use App\Http\Controllers\Api\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Api\Admin\BannerImageController;
use App\Http\Controllers\Api\Admin\ImageUploadController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\CarouselSlideController as AdminCarouselSlideController;
use App\Http\Controllers\Api\Admin\CarouselSlideImageController;
use App\Http\Controllers\Api\Admin\CourseController as AdminCourseController;
use App\Http\Controllers\Api\Admin\CourseCoverController;
use App\Http\Controllers\Api\Admin\CourseLandingController;
use App\Http\Controllers\Api\Admin\HomeCounterController;
use App\Http\Controllers\Api\Admin\HomeSectionController;
use App\Http\Controllers\Api\Admin\PostCoverController;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\SecuritySettingController;
use App\Http\Controllers\Api\Admin\SitePageController;
use App\Http\Controllers\Api\Admin\UserPermissionController;
use App\Http\Controllers\Api\Admin\TestimonialController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\CarouselSlideController;
use App\Http\Controllers\Api\CourseController;
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

// Cursos no ar: vitrine (`/cursos`), relação (menu e cards) e a página de cada um
Route::get('courses/landing', [CourseController::class, 'landing']);
Route::get('courses', [CourseController::class, 'index']);
Route::get('courses/{slug}', [CourseController::class, 'show']);

// Rotas do site que um botão do painel pode apontar (select de destino)
Route::get('site/rotas', [SiteRouteController::class, 'index']);

// Menu do cabeçalho do site, com os submenus das seções já montados
Route::get('site/menu', [SiteRouteController::class, 'menu']);

// Páginas que o CMS tirou do ar: o site usa para responder 404 em acesso direto
Route::get('site/paginas-ocultas', [SiteRouteController::class, 'hidden']);

// Páginas internas de uma seção: só a relação de rotas (slug + nome)
Route::get('secoes/{secao}/paginas', [SectionPageController::class, 'index'])
    ->whereIn('secao', SectionPages::sections());

// Painel (área restrita): exige token Bearer e, em cada rota, a permissão do
// papel de quem está logado ('pode:<modulo>,<acao>' — App\Support\PanelModules).
Route::prefix('admin')->middleware('auth:api')->group(function () {
    Route::apiResource('posts', AdminPostController::class)
        ->middlewareFor(['index', 'show'], 'pode:posts,view')
        ->middlewareFor('store', 'pode:posts,create')
        ->middlewareFor('update', 'pode:posts,update')
        ->middlewareFor('destroy', 'pode:posts,delete');
    Route::post('posts/{post}/cover', [PostCoverController::class, 'store'])->middleware('pode:posts,update');
    Route::delete('posts/{post}/cover', [PostCoverController::class, 'destroy'])->middleware('pode:posts,update');
    Route::post('uploads/images', [ImageUploadController::class, 'store'])->middleware('pode:posts,create');

    // Banners (hero) das páginas do site: chaves fixas em Banner::KEYS
    Route::get('banners/{key}', [AdminBannerController::class, 'show'])->whereIn('key', Banner::KEYS)->middleware('pode:banners,view');
    Route::put('banners/{key}', [AdminBannerController::class, 'update'])->whereIn('key', Banner::KEYS)->middleware('pode:banners,update');
    Route::post('banners/{key}/image', [BannerImageController::class, 'store'])->whereIn('key', Banner::KEYS)->middleware('pode:banners,update');
    Route::delete('banners/{key}/image', [BannerImageController::class, 'destroy'])->whereIn('key', Banner::KEYS)->middleware('pode:banners,update');

    // Carrossel da página inicial: CRUD, imagem e ordem dos slides
    Route::post('carousel-slides/reorder', [AdminCarouselSlideController::class, 'reorder'])->middleware('pode:home.carousel,update');
    Route::apiResource('carousel-slides', AdminCarouselSlideController::class)
        ->middlewareFor(['index', 'show'], 'pode:home.carousel,view')
        ->middlewareFor('store', 'pode:home.carousel,create')
        ->middlewareFor('update', 'pode:home.carousel,update')
        ->middlewareFor('destroy', 'pode:home.carousel,delete');
    Route::post('carousel-slides/{carousel_slide}/image', [CarouselSlideImageController::class, 'store'])->middleware('pode:home.carousel,update');
    Route::delete('carousel-slides/{carousel_slide}/image', [CarouselSlideImageController::class, 'destroy'])->middleware('pode:home.carousel,update');

    // Página inicial: blocos de chave fixa, contadores e depoimentos
    Route::get('home/sections/{key}', [HomeSectionController::class, 'show'])->whereIn('key', HomeSection::KEYS)->middleware('pode:home.sections,view');
    Route::patch('home/sections/{key}', [HomeSectionController::class, 'update'])->whereIn('key', HomeSection::KEYS)->middleware('pode:home.sections,update');
    Route::post('home/sections/{key}/image', [HomeSectionController::class, 'storeImage'])->whereIn('key', HomeSection::KEYS)->middleware('pode:home.sections,update');
    Route::delete('home/sections/{key}/image', [HomeSectionController::class, 'destroyImage'])->whereIn('key', HomeSection::KEYS)->middleware('pode:home.sections,update');

    Route::apiResource('home/counters', HomeCounterController::class)->except('show')->parameters(['counters' => 'counter'])
        ->middlewareFor('index', 'pode:home.counters,view')
        ->middlewareFor('store', 'pode:home.counters,create')
        ->middlewareFor('update', 'pode:home.counters,update')
        ->middlewareFor('destroy', 'pode:home.counters,delete');

    Route::apiResource('home/testimonials', TestimonialController::class)->except('show')->parameters(['testimonials' => 'testimonial'])
        ->middlewareFor('index', 'pode:home.testimonials,view')
        ->middlewareFor('store', 'pode:home.testimonials,create')
        ->middlewareFor('update', 'pode:home.testimonials,update')
        ->middlewareFor('destroy', 'pode:home.testimonials,delete');
    Route::post('home/testimonials/{testimonial}/photo', [TestimonialController::class, 'storePhoto'])->middleware('pode:home.testimonials,update');
    Route::delete('home/testimonials/{testimonial}/photo', [TestimonialController::class, 'destroyPhoto'])->middleware('pode:home.testimonials,update');

    // Cursos: cada um vira uma página do site e um item do menu "Cursos"
    Route::get('courses/landing', [CourseLandingController::class, 'show'])->middleware('pode:courses,view');
    Route::patch('courses/landing', [CourseLandingController::class, 'update'])->middleware('pode:courses,update');
    Route::post('courses/reorder', [AdminCourseController::class, 'reorder'])->middleware('pode:courses,update');
    Route::post('courses/{course}/cover', [CourseCoverController::class, 'store'])->middleware('pode:courses,update');
    Route::delete('courses/{course}/cover', [CourseCoverController::class, 'destroy'])->middleware('pode:courses,update');
    Route::apiResource('courses', AdminCourseController::class)
        ->middlewareFor(['index', 'show'], 'pode:courses,view')
        ->middlewareFor('store', 'pode:courses,create')
        ->middlewareFor('update', 'pode:courses,update')
        ->middlewareFor('destroy', 'pode:courses,delete');

    // Usuários do painel: listagem com filtros e troca de situação
    Route::get('users', [AdminUserController::class, 'index'])->middleware('pode:users,view');
    Route::patch('users/{user}/status', [AdminUserController::class, 'updateStatus'])->middleware('pode:users,update');

    // Equipe: quem entra no painel e o papel de cada um
    Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])->middleware('pode:team,update');

    // Configurações: segurança do painel (sessão, senha, segundo fator)
    Route::get('settings/security', [SecuritySettingController::class, 'show'])->middleware('pode:settings,view');
    Route::put('settings/security', [SecuritySettingController::class, 'update'])->middleware('pode:settings,update');

    // Configurações: quais páginas do site aparecem, e por quanto tempo
    Route::get('site-pages', [SitePageController::class, 'index'])->middleware('pode:settings,view');
    Route::patch('site-pages', [SitePageController::class, 'update'])->middleware('pode:settings,update');

    // Permissões de uma pessoa: o papel dela e as exceções tela a tela
    Route::get('users/{user}/permissions', [UserPermissionController::class, 'show'])->middleware('pode:team,view');
    Route::put('users/{user}/permissions', [UserPermissionController::class, 'update'])->middleware('pode:team,update');

    // Papéis e a matriz de permissões
    Route::get('roles', [RoleController::class, 'index'])->middleware('pode:team,view');
    Route::post('roles', [RoleController::class, 'store'])->middleware('pode:team,create');
    Route::patch('roles/{role}', [RoleController::class, 'update'])->middleware('pode:team,update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->middleware('pode:team,delete');
});
