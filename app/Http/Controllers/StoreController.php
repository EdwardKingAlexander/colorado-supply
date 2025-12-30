<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StoreController extends Controller
{
    public function index(Request $request, ?Location $location = null): Response
    {
        // Support both web and admin guards
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();

        if (! $user) {
            abort(401);
        }

        // Admins can view all products, regular users see company-scoped products
        if ($user instanceof \App\Models\Admin) {
            $products = Product::query();
        } else {
            $company = $user->company;

            $products = Product::query();

            // Ensure products are always scoped to the user's company
            // The global scope `CompanyScope` already handles this, but for clarity on specific product filtering.
            // It retrieves products associated with the user's company via `company_products` table.
            // If a specific location is provided, further filter these company-scoped products by that location.
            if ($location) {
                $products->whereHas('locationProducts', function ($query) use ($location) {
                    $query->where('location_id', $location->id)
                        ->where('visible', true); // Only show visible products for this location
                });
            }
        }

        $products = $products->get();

        return Inertia::render('Store/StoreIndex', [
            'location' => $location,
            'products' => $products,
        ]);
    }

    public function show(string $slug): Response
    {
        return Inertia::render('Store/ProductDetail', [
            'slug' => $slug,
        ]);
    }

    public function quote(): Response
    {
        // Support both web and admin guards
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();

        if (! $user) {
            abort(401);
        }

        // Admins see all locations, regular users see their company's locations
        if ($user instanceof \App\Models\Admin) {
            $locations = Location::all();
        } else {
            $locations = $user->company->locations;
        }

        return Inertia::render('Store/QuoteView', [
            'locations' => $locations,
        ]);
    }
}
