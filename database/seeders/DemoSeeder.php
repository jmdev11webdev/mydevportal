<?php

namespace Database\Seeders;

use App\Models\CareerEntry;
use App\Models\LearningResource;
use App\Models\Note;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@mydevportal.app';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Alex Rivera',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        if ($user->projects()->exists()) {
            return;
        }

        $projects = collect([
            [
                'name' => 'Orbit',
                'description' => 'A privacy-first product analytics dashboard. Cookie-free, aggregate-only, and built for teams who care about their users.',
                'status' => 'active',
                'color' => 'indigo',
                'tech_stack' => ['Vue 3', 'TypeScript', 'Laravel', 'SQLite'],
                'repository_url' => 'https://github.com/alexrivera/orbit',
                'started_at' => now()->subDays(42)->toDateString(),
                'due_at' => now()->addDays(18)->toDateString(),
            ],
            [
                'name' => 'Pulse API',
                'description' => 'Internal REST API powering notifications, billing and background jobs across the product suite.',
                'status' => 'active',
                'color' => 'emerald',
                'tech_stack' => ['Laravel', 'PostgreSQL', 'Redis'],
                'repository_url' => 'https://github.com/alexrivera/pulse-api',
                'started_at' => now()->subDays(28)->toDateString(),
                'due_at' => now()->addDays(32)->toDateString(),
            ],
            [
                'name' => 'Devfolio',
                'description' => 'A fast, minimal personal site that doubles as a living changelog of things I build and learn.',
                'status' => 'planning',
                'color' => 'amber',
                'tech_stack' => ['Astro', 'Tailwind CSS'],
                'due_at' => now()->addDays(54)->toDateString(),
            ],
            [
                'name' => 'CLI Toolkit',
                'description' => 'A small set of developer utilities for scaffolding projects and keeping local environments tidy.',
                'status' => 'completed',
                'color' => 'sky',
                'tech_stack' => ['Rust', 'Clap'],
                'repository_url' => 'https://github.com/alexrivera/cli-toolkit',
                'started_at' => now()->subDays(120)->toDateString(),
                'due_at' => now()->subDays(14)->toDateString(),
            ],
        ])->mapWithKeys(function (array $attributes) use ($user) {
            $project = Project::create([...$attributes, 'user_id' => $user->id]);

            return [$project->name => $project];
        });

        $tasks = [
            ['Write aggregate event ingestion spec', 'Orbit', 'in_progress', 'high', 0, null],
            ['Polish onboarding empty states', 'Orbit', 'todo', 'medium', 0, null],
            ['Audit third-party scripts for trackers', 'Orbit', 'todo', 'high', -1, null],
            ['Add rate limiting to webhook endpoint', 'Pulse API', 'in_progress', 'high', 1, null],
            ['Document queue retry strategy', 'Pulse API', 'todo', 'low', 3, null],
            ['Draft copy for the about page', 'Devfolio', 'todo', 'medium', 5, null],
            ['Pick a domain name', 'Devfolio', 'todo', 'low', 7, null],
            ['Set up CI pipeline for releases', 'CLI Toolkit', 'done', 'medium', null, 2],
            ['Publish v0.4.0 changelog', 'CLI Toolkit', 'done', 'low', null, 4],
            ['Refactor auth guards into policies', 'Pulse API', 'done', 'high', null, 1],
            ['Weekly review + plan next sprint', null, 'todo', 'medium', 0, null],
            ['Read 30 pages of DDIA', null, 'todo', 'low', 0, null],
            ['Prepare system design interview notes', null, 'in_progress', 'high', 2, null],
            ['Cancel unused SaaS subscriptions', null, 'done', 'low', null, 5],
        ];

        foreach ($tasks as [$title, $projectName, $status, $priority, $dueOffset, $completedDaysAgo]) {
            Task::create([
                'user_id' => $user->id,
                'project_id' => $projectName === null ? null : $projects[$projectName]->id,
                'title' => $title,
                'status' => $status,
                'priority' => $priority,
                'due_date' => $dueOffset === null ? null : now()->addDays($dueOffset)->toDateString(),
                'completed_at' => $completedDaysAgo === null ? null : now()->subDays($completedDaysAgo)->setTime(15, 30),
            ]);
        }

        $resources = [
            [
                'title' => 'Advanced TypeScript: Type-Level Programming',
                'provider' => 'Frontend Masters',
                'url' => 'https://frontendmasters.com/courses/advanced-typescript/',
                'category' => 'TypeScript',
                'format' => 'course',
                'status' => 'in_progress',
                'progress' => 62,
                'notes' => 'Conditional types and inference are finally clicking. Revisit the variance chapter.',
            ],
            [
                'title' => 'Designing Data-Intensive Applications',
                'provider' => "O'Reilly",
                'category' => 'Architecture',
                'format' => 'book',
                'status' => 'in_progress',
                'progress' => 35,
                'notes' => 'Chapter 5 on replication — great mental model for the Pulse read replicas.',
            ],
            [
                'title' => 'Laravel 13 Deep Dive',
                'provider' => 'Laracasts',
                'url' => 'https://laracasts.com',
                'category' => 'Backend',
                'format' => 'course',
                'status' => 'in_progress',
                'progress' => 80,
            ],
            [
                'title' => 'The Rust Programming Language',
                'provider' => 'Rust Book',
                'url' => 'https://doc.rust-lang.org/book/',
                'category' => 'Systems',
                'format' => 'book',
                'status' => 'planned',
                'progress' => 0,
            ],
            [
                'title' => 'Web Accessibility Fundamentals',
                'provider' => 'freeCodeCamp',
                'category' => 'Frontend',
                'format' => 'video',
                'status' => 'completed',
                'progress' => 100,
            ],
            [
                'title' => 'The Pragmatic Programmer',
                'provider' => 'Addison-Wesley',
                'category' => 'Craft',
                'format' => 'book',
                'status' => 'completed',
                'progress' => 100,
            ],
        ];

        foreach ($resources as $resource) {
            LearningResource::create([
                ...$resource,
                'user_id' => $user->id,
                'completed_at' => $resource['status'] === 'completed' ? now()->subDays(12) : null,
            ]);
        }

        $notes = [
            [
                'title' => 'SQLite pragmas worth remembering',
                'body' => "PRAGMA journal_mode = WAL;\nPRAGMA synchronous = NORMAL;\nPRAGMA foreign_keys = ON;\nPRAGMA busy_timeout = 5000;\n\nWAL + a sane busy timeout removes most \"database is locked\" surprises for small apps.",
                'tags' => ['sqlite', 'performance'],
                'color' => 'amber',
                'is_pinned' => true,
            ],
            [
                'title' => 'Interview prep: system design prompts',
                'body' => "Practice out loud, 35 minutes each:\n\n1. Design a URL shortener with analytics.\n2. Design a notification pipeline with retries.\n3. Design a multi-tenant audit log.\n4. Design a feature flag service.\n\nFocus on trade-offs, not perfect answers.",
                'tags' => ['career', 'system-design'],
                'color' => 'violet',
                'is_pinned' => true,
            ],
            [
                'title' => 'Postgres index ideas for Pulse',
                'body' => "- Composite index on (user_id, created_at desc) for the activity feed.\n- Partial index on notifications where read_at is null.\n- Re-check the webhook deliveries query plan after the next migration.",
                'tags' => ['postgres', 'pulse'],
                'color' => 'sky',
                'is_pinned' => false,
            ],
            [
                'title' => 'Snippet: debounce composable',
                'body' => "export function useDebounce<T>(value: Ref<T>, delay = 250) {\n    const debounced = ref(value.value);\n    let timer: number | undefined;\n\n    watch(value, (next) => {\n        window.clearTimeout(timer);\n        timer = window.setTimeout(() => (debounced.value = next), delay);\n    });\n\n    return debounced;\n}",
                'tags' => ['vue', 'typescript'],
                'color' => 'emerald',
                'is_pinned' => false,
            ],
            [
                'title' => 'Q4 reading list',
                'body' => "- Staff Engineer — Will Larson\n- Shape Up — Basecamp\n- A Philosophy of Software Design — John Ousterhout\n- The Mom Test — Rob Fitzpatrick",
                'tags' => ['reading'],
                'color' => 'slate',
                'is_pinned' => false,
            ],
            [
                'title' => 'Weekly review prompts',
                'body' => "What moved the needle this week?\nWhat did I avoid, and why?\nWhat is the one thing that would make next week feel lighter?\nWhat did I learn that future-me should know?",
                'tags' => ['reflection', 'ritual'],
                'color' => 'rose',
                'is_pinned' => false,
            ],
        ];

        foreach ($notes as $note) {
            Note::create([...$note, 'user_id' => $user->id]);
        }

        $career = [
            [
                'title' => 'Land a senior product engineer role',
                'type' => 'goal',
                'organization' => 'Series B product teams',
                'description' => 'Targeting small teams where I can own a surface end-to-end, not just tickets.',
                'status' => 'in_progress',
                'target_date' => now()->addDays(120)->toDateString(),
            ],
            [
                'title' => 'Ship two open-source tools',
                'type' => 'goal',
                'organization' => 'Personal',
                'description' => 'CLI Toolkit is the first. The second should be something people can use in under a minute.',
                'status' => 'in_progress',
                'target_date' => now()->addDays(180)->toDateString(),
            ],
            [
                'title' => 'Staff Engineer — Linear',
                'type' => 'application',
                'organization' => 'Linear',
                'description' => 'Applied through referral. Take-home is a small sync engine.',
                'status' => 'in_progress',
                'target_date' => now()->addDays(5)->toDateString(),
            ],
            [
                'title' => 'Senior Engineer — Raycast',
                'type' => 'application',
                'organization' => 'Raycast',
                'description' => 'Waiting for the role to open back up. Warm intro via Dana.',
                'status' => 'planned',
                'target_date' => now()->addDays(21)->toDateString(),
            ],
            [
                'title' => 'Promoted to Senior Software Engineer',
                'type' => 'achievement',
                'organization' => 'Northwind Labs',
                'description' => 'Led the migration to Laravel and mentored two junior engineers through their first production launch.',
                'status' => 'achieved',
                'target_date' => null,
                'completed_at' => now()->subDays(64),
            ],
            [
                'title' => 'AWS Solutions Architect — Associate',
                'type' => 'certification',
                'organization' => 'Amazon Web Services',
                'description' => 'Passed the exam on the first attempt.',
                'status' => 'achieved',
                'target_date' => null,
                'completed_at' => now()->subDays(130),
            ],
        ];

        foreach ($career as $entry) {
            CareerEntry::create([...$entry, 'user_id' => $user->id]);
        }
    }
}
