<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ManufacturingInformationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Manufacturing/Information');
    }
}

