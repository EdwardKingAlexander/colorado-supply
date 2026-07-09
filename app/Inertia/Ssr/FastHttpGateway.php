<?php

namespace App\Inertia\Ssr;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Vite;
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
    public function dispatch(array $page, ?Request $request = null): ?Response
    {
        if (! $this->ssrIsEnabled($request ?? request())) {
            return null;
        }

        $isHot = Vite::isRunningHot();

        if (! $isHot && $this->shouldEnsureBundleExists() && ! $this->bundleExists()) {
            return null;
        }

        $url = $isHot
            ? $this->getHotUrl('/__inertia_ssr')
            : $this->getProductionUrl('/render');

        try {
            $response = $this->httpClient()
                ->post($url, $page)
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
            implode("\n", $response['head'] ?? []),
            $response['body'] ?? ''
        );
    }

    public function isHealthy(): bool
    {
        try {
            return $this->httpClient()
                ->get($this->getProductionUrl('/health'))
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
