<?php

namespace App\Http\Controllers;

use App\Mail\RepairRequestAutoReply;
use App\Mail\RepairRequestSubmitted;
use App\Models\RepairRequest;
use App\Services\Google\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RepairServiceController extends Controller
{
    public function __construct(private RecaptchaService $recaptcha) {}

    public function index()
    {
        return Inertia::render('RepairServices/Index');
    }

    public function store(Request $request)
    {
        if ($request->filled('website')) {
            return response()->json(['message' => 'Thanks! Your request was received.'], 201);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company' => ['nullable', 'string', 'max:150'],
            'equipment_type' => ['required', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model_number' => ['required', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'issue_description' => ['required', 'string', 'max:2000'],
            'urgency' => ['nullable', 'in:standard,rush'],
            'captcha_token' => ['required', 'string'],
        ]);

        if (! $this->recaptcha->verify($validated['captcha_token'], $request->ip(), 'repair_request_form')) {
            throw ValidationException::withMessages([
                'captcha_token' => 'reCAPTCHA verification failed. Please try again.',
            ]);
        }

        unset($validated['captcha_token']);

        RepairRequest::create([
            ...$validated,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Mail::to('Edward@cogovsupply.com')->send(new RepairRequestSubmitted($validated));
        Mail::to($validated['email'])->send(new RepairRequestAutoReply($validated));

        return response()->json(['message' => 'Thanks! Your request was received.'], 201);
    }
}
