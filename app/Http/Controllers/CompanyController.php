<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $company = $user->company;
        $locations = $company->locations;

        return Inertia::render('Company/Index', [
            'company' => $company,
            'locations' => $locations,
        ]);
    }
}
