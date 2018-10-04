<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class MonitoringEvent extends Event implements MonitoringEventInterface
{
    /** @var array */
    private $parameters;

    /**
     * MonitoringEvent constructor.
     *
     * @param array $parameters parameters can contain metrics values, tags values or/and custom param values
     *
     * @see https://github.com/M6Web/StatsdPrometheusBundle/blob/master/Doc/usage.md
     */
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
