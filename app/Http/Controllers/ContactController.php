<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Mail\ContactFormSubmitted;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // STEP 2 will send the email using $validated.

        Mail::to('Edward@cogovsupply.com')->send(new ContactFormSubmitted($validated));

        // Auto-reply to the user who submitted the form
        Mail::to($validated['email'])->send(new \App\Mail\ContactAutoReply($validated));

    return response()->json(['message' => 'Thanks—your message was received.'], 201);
    }
}
