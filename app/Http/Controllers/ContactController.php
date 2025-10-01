<?php

namespace App\Http\Controllers;

use App\Mail\ContactAutoReply;
use App\Mail\ContactFormSubmitted;
use App\Models\ContactMessage;
use App\Services\Google\RecaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ContactController extends Controller
{
    public function __construct(private RecaptchaService $recaptcha)
    {
    }

    public function store(Request $request)
    {
        if ($request->filled('company')) {
            return response()->json(['message' => 'Thanks! Your message was received.'], 201);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
            'captcha_token' => ['required', 'string'],
        ]);

        if (! $this->recaptcha->verify($validated['captcha_token'], $request->ip(), 'contact_form')) {
            throw ValidationException::withMessages([
                'captcha_token' => 'reCAPTCHA verification failed. Please try again.',
            ]);
        }

        unset($validated['captcha_token']);

        ContactMessage::create([
            ...$validated,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Mail::to('Edward@cogovsupply.com')->send(new ContactFormSubmitted($validated));
        Mail::to($validated['email'])->send(new ContactAutoReply($validated));

        return response()->json(['message' => 'Thanks! Your message was received.'], 201);
    }
}
