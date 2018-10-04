<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

interface MonitoringEventInterface
{
    public function hasParameter(string $key): bool;

    /**
     * @return mixed|null
     */
    public function getParameter(string $key);
}
