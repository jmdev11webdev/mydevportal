<?php

use App\Http\Controllers\CareerEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\GitHubAuthController;
use App\Http\Controllers\LearningResourceController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReadmeController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('demo', DemoLoginController::class)->name('demo.login');

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('auth/github/redirect', [GitHubAuthController::class, 'redirect'])->name('auth.github.redirect');
    Route::get('auth/github/callback', [GitHubAuthController::class, 'callback'])->name('auth.github.callback');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('readme', ReadmeController::class)->name('readme.index');

    Route::resource('projects', ProjectController::class)->except(['show', 'create', 'edit']);

    Route::resource('tasks', TaskController::class)->except(['show', 'create', 'edit']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');

    Route::resource('learning', LearningResourceController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['learning' => 'learningResource']);
    Route::patch('learning/{learningResource}/progress', [LearningResourceController::class, 'progress'])
        ->name('learning.progress');

    Route::resource('notes', NoteController::class)->except(['show', 'create', 'edit']);
    Route::patch('notes/{note}/pin', [NoteController::class, 'pin'])->name('notes.pin');

    Route::resource('career', CareerEntryController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['career' => 'careerEntry']);
});

require __DIR__.'/settings.php';
