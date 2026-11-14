<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class EyeCareController extends Controller
{
    /**
     * Display the 20-20-20 Eye Care & Focus Timer page with clinical study sources.
     */
    public function __invoke(): Response
    {
        return Inertia::render('EyeCare/Index');
    }
}
