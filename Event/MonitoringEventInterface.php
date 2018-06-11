<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

interface MonitoringEventInterface
{
    /**
     * MonitoringEvent constructor.
     *
     * @param array $parameters parameters can contains metrics values, tags values or/and custom param values
     *
     * @see https://github.com/M6Web/StatsdPrometheusBundle/blob/master/Doc/usage.md
     */
    public function __construct(array $parameters = []);

    public function hasParameter(string $key): bool;

    /**
     * @return mixed|null
     */
    public function getParameter(string $key);
}
