<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Evènement surchargeant le kernel.terminate
 */
class KernelTerminateEvent extends PostResponseEvent
{
    public function getStatusCode(): int
    {
        return $this->getResponse()->getStatusCode();
    }

    public function getRouteName(): string
    {
        return $this->getRequest()->get('_route', 'undefined');
    }

    public function getMethodName(): string
    {
        return $this->getRequest()->getMethod();
    }

    public function getTiming(): float
    {
        return microtime(true) - $this->getRequest()->server->get('REQUEST_TIME_FLOAT');
    }

    public function getMemory(): int
    {
        return memory_get_peak_usage(true);
    }

    public function getHost(): string
    {
        return str_replace('.', '_', $this->getRequest()->getHost());
    }
}
