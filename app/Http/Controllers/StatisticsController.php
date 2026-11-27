<?php

namespace App\Http\Controllers;

use App\Support\PortalStatistics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Statistics/Index', [
            'statistics' => PortalStatistics::forUser($request->user()),
        ]);
    }
}
