<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;

interface MetricInterface
{
    /**
     * @throws MetricException
     */
    public function toString(): string;
}
