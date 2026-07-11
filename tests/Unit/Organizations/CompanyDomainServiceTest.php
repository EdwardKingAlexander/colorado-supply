<?php

use App\Services\Organizations\CompanyDomainService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

it('normalizes approved email domains', function () {
    $service = new CompanyDomainService;

    expect($service->normalize(' @Example.COM. '))->toBe('example.com')
        ->and($service->emailDomain('Person@Example.COM'))->toBe('example.com');
});

it('rejects URLs paths ports and local names as email domains', function (string $domain) {
    expect(fn () => (new CompanyDomainService)->normalize($domain))
        ->toThrow(ValidationException::class);
})->with([
    'https://example.com',
    'example.com/path',
    'example.com:443',
    'localhost',
    'example.com@other.com',
]);

it('does not treat a suffix lookalike as the same domain', function () {
    $service = new CompanyDomainService;

    expect($service->emailDomain('user@notexample.com'))->toBe('notexample.com')
        ->and($service->emailDomain('user@example.com'))->toBe('example.com');
});
