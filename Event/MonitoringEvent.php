<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MonitoringEvent extends Event implements MonitoringEventInterface
{
    /** @var array */
    private $parameters;

    public function __construct(array $parameters = [])
    {
        $this->parameters = $parameters;
    }

    public function hasParameter(string $key): bool
    {
        return isset($this->parameters[$key]);
    }

    /**
     * @return mixed|null
     */
    public function getParameter(string $key)
    {
        return $this->parameters[$key] ?? null;
    }
}
