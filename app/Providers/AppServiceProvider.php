<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->ensureCalendarSchema();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Ensure calendar_events and task subtask/event columns exist when running locally.
     */
    protected function ensureCalendarSchema(): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('tasks')) {
                return;
            }

            if (! Schema::hasTable('calendar_events')) {
                Schema::create('calendar_events', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                    $table->string('title');
                    $table->text('description')->nullable();
                    $table->string('type')->default('sprint');
                    $table->string('color')->default('indigo');
                    $table->date('starts_at');
                    $table->date('ends_at')->nullable();
                    $table->string('start_time', 10)->nullable();
                    $table->string('end_time', 10)->nullable();
                    $table->string('location')->nullable();
                    $table->boolean('is_all_day')->default(false);
                    $table->timestamps();
                });
            }

            if (! Schema::hasColumn('tasks', 'calendar_event_id') || ! Schema::hasColumn('tasks', 'subtasks')) {
                Schema::table('tasks', function (Blueprint $table) {
                    if (! Schema::hasColumn('tasks', 'calendar_event_id')) {
                        $table->foreignId('calendar_event_id')
                            ->nullable()
                            ->after('project_id')
                            ->constrained('calendar_events')
                            ->nullOnDelete();
                    }

                    if (! Schema::hasColumn('tasks', 'subtasks')) {
                        $table->json('subtasks')->nullable()->after('description');
                    }
                });
            }
        } catch (Throwable) {
            // Ignore schema check errors if database is not yet initialized.
        }
    }
}
