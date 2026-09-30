<?php

use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

/*
|--------------------------------------------------------------------------
| Public Portfolio API
|--------------------------------------------------------------------------
*/

Route::prefix('v1/public')
    ->name('api.v1.public.')
    ->group(function () {
        Route::get('/projects', [
            ProjectController::class,
            'index',
        ])->name('projects.index');

        Route::get('/projects/{slug}', [
            ProjectController::class,
            'show',
        ])->name('projects.show');

        Route::get('/categories', [
            CategoryController::class,
            'index',
        ])->name('categories.index');

        Route::get('/categories/{slug}', [
            CategoryController::class,
            'show',
        ])->name('categories.show');
    });

/*
|--------------------------------------------------------------------------
| Protected OAuth 2.0 API
|--------------------------------------------------------------------------
*/

Route::middleware('auth:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->only([
            'id',
            'name',
            'email',
        ]);
    });

    Route::prefix('v1')
        ->name('api.v1.')
        ->middleware(
            CheckToken::using('portfolio:read')
        )
        ->group(function () {
            Route::get('/projects', [
                ProjectController::class,
                'index',
            ])->name('projects.index');

            Route::get('/projects/{slug}', [
                ProjectController::class,
                'show',
            ])->name('projects.show');

            Route::get('/categories', [
                CategoryController::class,
                'index',
            ])->name('categories.index');

            Route::get('/categories/{slug}', [
                CategoryController::class,
                'show',
            ])->name('categories.show');
        });
});