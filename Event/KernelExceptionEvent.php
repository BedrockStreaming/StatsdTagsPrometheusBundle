<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Decorates kernel.exception event with monitoring data
 */
class KernelExceptionEvent extends Event
{
    /** @var GetResponseForExceptionEvent */
    private $event;

    public function __construct(GetResponseForExceptionEvent $event)
    {
        $this->event = $event;
    }

    public function getStatusCode(): int
    {
        if (method_exists($this->event->getException(), 'getStatusCode')) {
            return $this->event->getException()->getStatusCode();
        }

        return 500;
    }
}
