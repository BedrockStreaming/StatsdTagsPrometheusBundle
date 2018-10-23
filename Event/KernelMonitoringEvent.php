<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class KernelMonitoringEvent extends MonitoringEvent
{
    const TERMINATE = 'statsdprometheus.kernel.terminate';
    const EXCEPTION = 'statsdprometheus.kernel.exception';

    public static function createFromKernelTerminateEvent(PostResponseEvent $event): KernelMonitoringEvent
    {
        return new self([
            'host' => $event->getRequest()->getHost(),
            'method' => $event->getRequest()->getMethod(),
            'memory' => memory_get_peak_usage(true),
            'route' => $event->getRequest()->get('_route', 'undefined'),
            'status' => $event->getResponse()->getStatusCode(),
            'timing' => microtime(true) - $event->getRequest()->server->get('REQUEST_TIME_FLOAT'),
            // The original event is sent as a parameter, just in case
            'originalEvent' => $event,
        ]);
    }

    public static function createFromKernelExceptionEvent(GetResponseForExceptionEvent $event): KernelMonitoringEvent
    {
        $statusCode = 500;
        if (method_exists($event->getException(), 'getStatusCode')) {
            $statusCode = $event->getException()->getStatusCode();
        }

        return new self([
            'status' => $statusCode,
            // The original event is sent as a parameter, just in case
            'originalEvent' => $event,
        ]);
    }
}
