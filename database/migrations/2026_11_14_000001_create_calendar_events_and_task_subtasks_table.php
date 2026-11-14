<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        if (Schema::hasTable('tasks')) {
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
    }

    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'calendar_event_id')) {
                    $table->dropConstrainedForeignId('calendar_event_id');
                }
                if (Schema::hasColumn('tasks', 'subtasks')) {
                    $table->dropColumn('subtasks');
                }
            });
        }

        Schema::dropIfExists('calendar_events');
    }
};
