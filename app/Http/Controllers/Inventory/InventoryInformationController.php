<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InventoryInformationController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Inventory/Information');
    }
}

