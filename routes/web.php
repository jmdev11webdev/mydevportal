<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CareerEntryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoLoginController;
use App\Http\Controllers\EyeCareController;
use App\Http\Controllers\GitHubAuthController;
use App\Http\Controllers\LearningResourceController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PipelineLeadController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReadmeController;
use App\Http\Controllers\StackItemController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function (Request $request) {
    if ($request->user()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('auth/Login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'githubAuthEnabled' => filled(config('services.github.client_id')),
        'status' => $request->session()->get('status'),
    ]);
})->name('home');

Route::inertia('welcome', 'Welcome')->name('welcome');

Route::get('demo', DemoLoginController::class)->name('demo.login');

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::get('auth/github/redirect', [GitHubAuthController::class, 'redirect'])->name('auth.github.redirect');
    Route::get('auth/github/callback', [GitHubAuthController::class, 'callback'])->name('auth.github.callback');
});

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('readme', ReadmeController::class)->name('readme.index');
    Route::get('eye-care', EyeCareController::class)->name('eye-care.index');

    Route::resource('calendar', CalendarController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['calendar' => 'calendarEvent']);
    Route::post('calendar/{calendarEvent}/tasks', [CalendarController::class, 'storeTask'])
        ->name('calendar.tasks.store');

    Route::resource('projects', ProjectController::class)->except(['show', 'create', 'edit']);

    Route::resource('tasks', TaskController::class)->except(['show', 'create', 'edit']);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::post('tasks/{task}/subtasks', [TaskController::class, 'storeSubtask'])->name('tasks.subtasks.store');
    Route::patch('tasks/{task}/subtasks/{subtaskId}/toggle', [TaskController::class, 'toggleSubtask'])
        ->name('tasks.subtasks.toggle');
    Route::delete('tasks/{task}/subtasks/{subtaskId}', [TaskController::class, 'destroySubtask'])
        ->name('tasks.subtasks.destroy');

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

    Route::resource('pipeline', PipelineLeadController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['pipeline' => 'pipelineLead']);

    Route::get('statistics', StatisticsController::class)->name('statistics.index');

    Route::resource('stack', StackItemController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['stack' => 'stackItem']);
    Route::patch('stack/{stackItem}/pin', [StackItemController::class, 'pin'])
        ->name('stack.pin');
});

require __DIR__.'/settings.php';
