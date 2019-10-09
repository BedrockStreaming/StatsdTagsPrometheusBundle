<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel;

use M6Web\Bundle\StatsdPrometheusBundle\Event\AbstractMonitoringEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

class KernelTerminateMonitoringEvent extends AbstractMonitoringEvent
{
    public static function createFromKernelTerminateEvent(TerminateEvent $event): KernelTerminateMonitoringEvent
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
}
