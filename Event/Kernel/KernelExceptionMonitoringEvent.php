<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel;

use M6Web\Bundle\StatsdPrometheusBundle\Event\AbstractMonitoringEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class KernelExceptionMonitoringEvent extends AbstractMonitoringEvent
{
    public static function createFromKernelExceptionEvent(ExceptionEvent $event): self
    {
        $statusCode = 500;
        $exception = $event->getThrowable();
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        return new self([
            'status' => $statusCode,
            // The original event is sent as a parameter, just in case
            'originalEvent' => $event,
        ]);
    }
}
