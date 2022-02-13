<?php

namespace CodeDredd\Soap\Ray;

use Closure;
use Spatie\LaravelRay\RayProxy;
use Spatie\LaravelRay\Watchers\Watcher;
use Spatie\Ray\Ray as SpatieRay;

class LaravelRay
{
    public function register()
    {
        SpatieRay::macro('showSoapClientRequests', function ($callable = null): RayProxy {
            $watcher = app(SoapClientWatcher::class);

            return $this->handleWatcherCallable($watcher, $callable);
        });
        SpatieRay::macro('stopShowingSoapClientRequests', fn () => app(SoapClientWatcher::class)->disable());
    }

    protected function handleWatcherCallable(Watcher $watcher, Closure $callable = null): RayProxy
    {
        $rayProxy = new RayProxy();

        $wasEnabled = $watcher->enabled();

        $watcher->enable();

        if ($rayProxy) {
            $watcher->setRayProxy($rayProxy);
        }

        if ($callable) {
            $callable();

            if (! $wasEnabled) {
                $watcher->disable();
            }
        }

        return $rayProxy;
    }
}
