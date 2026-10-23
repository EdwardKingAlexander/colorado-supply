<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $company = Auth::user()->company;

        return Inertia::render('Company/Index', [
            'company' => $company,
            'locations' => $company?->locations ?? [],
        ]);
    }
}
