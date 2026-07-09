<?php

namespace Tests\Feature;

use App\Support\SentryEventScrubber;
use Sentry\Breadcrumb;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\Options;
use Sentry\SentrySdk;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Tests\TestCase;

class SentryIntegrationTest extends TestCase
{
    public function test_before_send_scrubs_sensitive_request_and_breadcrumb_data(): void
    {
        $transport = new class implements TransportInterface
        {
            /** @var Event[] */
            public array $captured = [];

            public function send(Event $event): Result
            {
                $this->captured[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        $options = new Options([
            'dsn' => 'https://public@o0.ingest.sentry.io/0',
            'before_send' => [SentryEventScrubber::class, 'scrub'],
        ]);
        $options->setTransport($transport);

        $client = (new ClientBuilder($options))->getClient();
        SentrySdk::getCurrentHub()->bindClient($client);

        $event = Event::createEvent();
        $event->setRequest([
            'headers' => [
                'Authorization' => 'Bearer secret-token',
                'X-Custom' => 'keep-me',
            ],
            'data' => [
                'email' => 'user@example.com',
                'password' => 'super-secret',
                'card_number' => '4242424242424242',
            ],
        ]);
        $event->setBreadcrumb([
            new Breadcrumb(
                Breadcrumb::LEVEL_INFO,
                Breadcrumb::TYPE_DEFAULT,
                'test',
                'test breadcrumb',
                ['tax_id' => '12-3456789', 'note' => 'keep-me']
            ),
        ]);

        SentrySdk::getCurrentHub()->captureEvent($event);

        $this->assertCount(1, $transport->captured);
        $sent = $transport->captured[0];

        $request = $sent->getRequest();
        $this->assertSame('[Filtered]', $request['headers']['Authorization']);
        $this->assertSame('keep-me', $request['headers']['X-Custom']);
        $this->assertSame('[Filtered]', $request['data']['password']);
        $this->assertSame('[Filtered]', $request['data']['card_number']);
        $this->assertSame('user@example.com', $request['data']['email']);

        $breadcrumbMetadata = $sent->getBreadcrumbs()[0]->getMetadata();
        $this->assertSame('[Filtered]', $breadcrumbMetadata['tax_id']);
        $this->assertSame('keep-me', $breadcrumbMetadata['note']);
    }

    public function test_unhandled_exception_reaches_sentry(): void
    {
        $transport = new class implements TransportInterface
        {
            /** @var Event[] */
            public array $captured = [];

            public function send(Event $event): Result
            {
                $this->captured[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        $options = new Options([
            'dsn' => 'https://public@o0.ingest.sentry.io/0',
            'before_send' => [SentryEventScrubber::class, 'scrub'],
        ]);
        $options->setTransport($transport);

        $client = (new ClientBuilder($options))->getClient();
        SentrySdk::getCurrentHub()->bindClient($client);

        try {
            throw new \RuntimeException('deliberate test exception');
        } catch (\RuntimeException $exception) {
            SentrySdk::getCurrentHub()->captureException($exception);
        }

        $this->assertCount(1, $transport->captured);
        $this->assertSame('deliberate test exception', $transport->captured[0]->getExceptions()[0]->getValue());
    }
}
