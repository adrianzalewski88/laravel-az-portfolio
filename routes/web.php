<?php

use Illuminate\Support\Facades\Route;

Route::livewire(
    '/',
    'pages::home'
)->name('home');

Route::livewire(
    '/projects/{project:slug}',
    'pages::projects.show'
)->name('projects.show');

Route::middleware('auth')->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->name('dashboard');

    // Projects
    Route::livewire(
        '/dashboard/projects',
        'pages::projects.index'
    )->name('projects.index');

    Route::livewire(
        '/dashboard/projects/create',
        'pages::projects.create'
    )->name('projects.create');

    Route::livewire(
        '/dashboard/projects/{project}/edit',
        'pages::projects.edit'
    )->name('projects.edit');

    // Categories
    Route::livewire(
        '/dashboard/categories',
        'pages::categories.index'
    )->name('categories.index');

    Route::livewire(
        '/dashboard/categories/create',
        'pages::categories.create'
    )->name('categories.create');

    Route::livewire(
        '/dashboard/categories/{category}/edit',
        'pages::categories.edit'
    )->name('categories.edit');

    // Users
    Route::livewire(
        '/dashboard/users',
        'pages::users.index'
    )->name('users.index');

    Route::livewire(
        '/dashboard/users/create',
        'pages::users.create'
    )->name('users.create');

    Route::livewire(
        '/dashboard/users/{user}/edit',
        'pages::users.edit'
    )->name('users.edit');
});