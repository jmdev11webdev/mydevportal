<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReadmeController extends Controller
{
    /**
     * Show the README generator with the developer's projects.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Readme/Index', [
            'projects' => $request->user()->projects()
                ->orderBy('name')
                ->get(['id', 'name', 'description', 'tech_stack', 'repository_url', 'live_url']),
        ]);
    }
}
