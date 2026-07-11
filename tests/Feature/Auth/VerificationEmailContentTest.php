<?php

use App\Models\User;
use App\Notifications\VerifyEmailAddress;
use Illuminate\Contracts\Queue\ShouldQueue;

test('the verification email carries branded professional content', function () {
    $user = User::factory()->unverified()->create();

    $mail = (new VerifyEmailAddress)->toMail($user);

    expect($mail->subject)->toBe('Verify your email address — Colorado Supply & Procurement')
        ->and($mail->greeting)->toBe('Welcome to Colorado Supply & Procurement')
        ->and($mail->actionText)->toBe('Verify Email Address')
        ->and($mail->actionUrl)->toContain('/verify-email/'.$user->getKey().'/')
        ->and($mail->actionUrl)->toContain('signature=');

    $lines = collect($mail->introLines)->merge($mail->outroLines)->implode(' ');

    expect($lines)->toContain('expires in 60 minutes')
        ->and($lines)->toContain('If you did not create an account');
});

test('the verification email renders with the branded mail theme', function () {
    $user = User::factory()->unverified()->create();

    $html = (string) (new VerifyEmailAddress)->toMail($user)->render();

    expect($html)
        ->toContain('SUPPLY &amp; PROCUREMENT')  // branded header wordmark
        ->toContain('#16334c')                    // navy header/button palette
        ->toContain('Verify Email Address');
});

test('the verification notification is queued', function () {
    expect(new VerifyEmailAddress)->toBeInstanceOf(ShouldQueue::class);
});
