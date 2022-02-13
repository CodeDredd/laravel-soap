<?php

namespace CodeDredd\Soap\Ray;

use CodeDredd\Soap\Client\Events\RequestSending;
use CodeDredd\Soap\Client\Events\ResponseReceived;
use CodeDredd\Soap\Client\Request;
use CodeDredd\Soap\Client\Response;
use Illuminate\Support\Facades\Event;
use Spatie\LaravelRay\Ray;
use Spatie\LaravelRay\Watchers\Watcher;
use Spatie\Ray\Payloads\TablePayload;

class SoapClientWatcher extends Watcher
{
    public function register(): void
    {
        $this->enabled = config('soap.ray.send_soap_client_requests', false);

        Event::listen(RequestSending::class, function (RequestSending $event) {
            if (! $this->enabled()) {
                return;
            }

            $ray = $this->handleRequest($event->request);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });

        Event::listen(ResponseReceived::class, function (ResponseReceived $event) {
            if (! $this->enabled()) {
                return;
            }

            $ray = $this->handleResponse($event->request, $event->response);

            optional($this->rayProxy)->applyCalledMethods($ray);
        });
    }

    protected function handleRequest(Request $request)
    {
        $payload = new TablePayload([
            'Action' => $request->action(),
            'URL' => $request->url(),
            'Headers' => $request->headers(),
            'Data' => $request->arguments(),
            'Body' => $request->body(),
        ], 'SOAP');

        return app(Ray::class)->sendRequest($payload);
    }

    protected function handleResponse(Request $request, Response $response)
    {
        $payload = new TablePayload([
            'URL' => $request->url(),
            'Real Request' => ! empty($response->handlerStats()),
            'Success' => $response->successful(),
            'Status' => $response->status(),
            'Headers' => $response->headers(),
            'Body' => rescue(function () use ($response) {
                return $response->json();
            }, $response->body(), false),
            'Size' => $response->handlerStats()['size_download'] ?? null,
            'Connection time' => $response->handlerStats()['connect_time'] ?? null,
            'Duration' => $response->handlerStats()['total_time'] ?? null,
            'Request Size' => $response->handlerStats()['request_size'] ?? null,
        ], 'SOAP');

        return app(Ray::class)->sendRequest($payload);
    }
}
