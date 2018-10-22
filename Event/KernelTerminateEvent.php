<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Decorates kernel.terminate event with monitoring data
 */
class KernelTerminateEvent extends Event
{
    /** @var PostResponseEvent */
    private $event;

    /** @var float */
    private $eventTime;

    public function __construct(PostResponseEvent $event)
    {
        $this->event = $event;
        $this->eventTime = microtime(true);
    }

    public function getStatusCode(): int
    {
        return $this->event->getResponse()->getStatusCode();
    }

    public function getRouteName(): string
    {
        return $this->event->getRequest()->get('_route', 'undefined');
    }

    public function getMethodName(): string
    {
        return $this->event->getRequest()->getMethod();
    }

    public function getTiming(): float
    {
        return $this->eventTime - $this->event->getRequest()->server->get('REQUEST_TIME_FLOAT');
    }

    public function getMemory(): int
    {
        return memory_get_peak_usage(true);
    }

    public function getHost(): string
    {
        return $this->event->getRequest()->getHost();
    }
}
