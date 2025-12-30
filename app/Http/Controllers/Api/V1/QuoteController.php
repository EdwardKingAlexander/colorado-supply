<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Mail\QuotePdfMail;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class QuoteController extends Controller
{
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'customer.name' => ['nullable', 'string', 'max:255'],
            'customer.company' => ['nullable', 'string', 'max:255'],
            'customer.email' => ['nullable', 'email', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.slug' => ['nullable', 'string', 'max:255'],
            'items.*.location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'delivery' => ['nullable', 'in:download,email'],
        ]);

        $delivery = $validated['delivery'] ?? 'download';

        $items = collect($validated['items'])
            ->map(fn (array $item) => [
                'name' => $item['name'],
                'quantity' => (int) $item['quantity'],
                'price' => (float) $item['price'],
                'slug' => $item['slug'] ?? null,
                'product_id' => $item['product_id'] ?? ($item['id'] ?? null),
                'location_id' => $item['location_id'] ?? null,
            ])
            ->values()
            ->all();

        $subtotal = collect($items)->reduce(
            fn (float $carry, array $item) => $carry + ($item['price'] * $item['quantity']),
            0.0,
        );

        $tax = isset($validated['tax']) ? (float) $validated['tax'] : 0.0;
        $total = round($subtotal + $tax, 2);

        $quoteNumber = 'Q-'.Carbon::now()->format('Ymd').'-'.Str::upper(Str::random(4));

        $user = $request->user();

        $quoteRecord = DB::transaction(function () use (
            $quoteNumber,
            $subtotal,
            $tax,
            $total,
            $validated,
            $items,
            $user
        ) {
            $salesRepId = $this->resolveSalesRepId($user);

            $quote = Quote::create([
                'quote_number' => $quoteNumber,
                'status' => 'sent',
                'customer_id' => null,
                'portal_user_id' => $user?->id,
                'walk_in_label' => $validated['customer']['company'] ?? 'web-quote',
                'walk_in_org' => $validated['customer']['company'] ?? null,
                'walk_in_contact_name' => $validated['customer']['name'] ?? null,
                'walk_in_email' => $validated['customer']['email'] ?? null,
                'walk_in_phone' => null,
                'currency' => 'USD',
                'tax_rate' => $tax,
                'discount_amount' => 0,
                'subtotal' => round($subtotal, 2),
                'tax_total' => round($tax, 2),
                'grand_total' => $total,
                'sales_rep_id' => $salesRepId,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $product = null;

                if (! empty($item['product_id'])) {
                    $product = Product::query()->find($item['product_id']);
                } elseif (! empty($item['slug'])) {
                    $product = Product::query()->where('slug', $item['slug'])->first();
                }

                $lineSubtotal = round($item['price'] * $item['quantity'], 2);

                $quote->items()->create([
                    'product_id' => $product?->id,
                    'location_id' => $item['location_id'] ?? null,
                    'sku' => $product->sku ?? $item['slug'],
                    'name' => $item['name'],
                    'qty' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => 0,
                    'line_total' => $lineSubtotal,
                    'uom' => $product->unit ?? null,
                ]);
            }

            return $quote->load('items.location');
        });

        // Group items by location for PDF display
        $itemsGroupedByLocation = $quoteRecord->items->groupBy('location_id')->map(function ($locationItems, $locationId) {
            return [
                'location_id' => $locationId,
                'location_name' => $locationItems->first()->location->name ?? 'Main Store',
                'items' => $locationItems->map(fn ($item) => [
                    'name' => $item->name,
                    'quantity' => (float) $item->qty,
                    'price' => (float) $item->unit_price,
                    'slug' => $item->sku,
                ])->toArray(),
            ];
        })->values()->toArray();

        $quoteData = [
            'id' => $quoteRecord->id,
            'number' => $quoteRecord->quote_number,
            'date' => Carbon::now()->format('F j, Y'),
            'customer' => [
                'name' => data_get($validated, 'customer.name'),
                'company' => data_get($validated, 'customer.company'),
                'email' => data_get($validated, 'customer.email'),
            ],
            'items' => $quoteRecord->items->map(fn ($item) => [
                'name' => $item->name,
                'quantity' => (float) $item->qty,
                'price' => (float) $item->unit_price,
                'slug' => $item->sku,
            ])->toArray(),
            'items_by_location' => $itemsGroupedByLocation,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => $total,
            'notes' => $validated['notes'] ?? null,
            'company' => [
                'name' => 'Colorado Supply & Procurement LLC',
                'address' => '2214 Blake St, Denver, CO 80205',
                'email' => 'edward@cogovsupply.com',
                'phone' => '(720) 555-0199',
                'cage' => '8ABC1',
                'duns' => '123456789',
                'website' => config('app.url'),
            ],
        ];

        $logoPath = resource_path('images/logo-cleansed.svg');
        $logo = file_exists($logoPath)
            ? 'data:image/svg+xml;base64,'.base64_encode(file_get_contents($logoPath))
            : null;

        $pdf = Pdf::loadView('pdf.quote', [
            'quote' => $quoteData,
            'logo' => $logo,
        ])->setPaper('letter');

        $filename = sprintf('Quote-%s.pdf', $quoteNumber);

        if ($delivery === 'email') {
            if (empty($quoteData['customer']['email'])) {
                throw ValidationException::withMessages([
                    'customer.email' => 'An email address is required to send the quote.',
                ]);
            }

            Mail::to($quoteData['customer']['email'])
                ->send(new QuotePdfMail($quoteData, $pdf->output(), $filename));

            return response()->json([
                'message' => 'Quote emailed successfully.',
                'quote_id' => $quoteRecord->id,
                'quote_number' => $quoteRecord->quote_number,
            ]);
        }

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Quote-Id' => (string) $quoteRecord->id,
            'X-Quote-Number' => $quoteRecord->quote_number,
        ]);
    }

    protected function resolveSalesRepId(?User $user): int
    {
        if ($user) {
            return $user->id;
        }

        $fallbackUser = User::query()->oldest()->first();

        if (! $fallbackUser) {
            $fallbackUser = User::query()->create([
                'name' => 'Quote Bot',
                'email' => 'quotes@'.parse_url(config('app.url', 'example.com'), PHP_URL_HOST),
                'password' => Hash::make(Str::random(32)),
                'role' => 'user',
            ]);
        }

        return $fallbackUser->id;
    }
}
