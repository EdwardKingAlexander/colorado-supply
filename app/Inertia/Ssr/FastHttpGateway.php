<?php

namespace App\Inertia\Ssr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Inertia\Ssr\HttpGateway;
use Inertia\Ssr\Response;
use Throwable;

class FastHttpGateway extends HttpGateway
{
    /**
     * Dispatch the Inertia page to the SSR engine with aggressive timeouts.
     *
     * @param  array<string, mixed>  $page
     */
    public function dispatch(array $page): ?Response
    {
        if (! $this->shouldDispatch()) {
            return null;
        }

        try {
            $response = $this->httpClient()
                ->post($this->getUrl('/render'), $page)
                ->throw()
                ->json();
        } catch (ConnectionException|RequestException $exception) {
            report($exception);

            return null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }

        if ($response === null) {
            return null;
        }

        return new Response(
            implode("\n", $response['head']),
            $response['body']
        );
    }

    public function isHealthy(): bool
    {
        try {
            return $this->httpClient()
                ->get($this->getUrl('/health'))
                ->successful();
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    protected function httpClient()
    {
        return Http::timeout(config('inertia.ssr.request_timeout', 5))
            ->connectTimeout(config('inertia.ssr.connect_timeout', 1.5));
    }
}
