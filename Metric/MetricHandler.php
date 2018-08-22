<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Client\ClientInterface;
use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;

class MetricHandler
{
    /** @var int */
    protected $maxNumberOfMetricToQueue;

    /** @var \SplQueue */
    private $metrics;

    private $flushMetricsQueue = false;

    /** @var ClientInterface */
    private $client;

    public function __construct()
    {
        $this->initMetricsQueue();
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    private function initMetricsQueue()
    {
        $this->metrics = new \SplQueue();
    }

    public function setMetricsQueue(\SplQueue $queue)
    {
        $this->metrics = $queue;
    }

    /**
     * This function tries to send the metrics according to some rules.
     * If those rules are not valid, it won't send anything.
     */
    public function tryToSendMetrics(): bool
    {
        if ($this->hasToSendMetrics()) {
            return $this->sendMetrics();
        }

        return false;
    }

    /**
     * We define here some rules to force sending the metrics even if we are not required to.
     *
     * @return bool
     */
    public function hasToSendMetrics(): bool
    {
        return $this->isFlushMetricsQueue() || $this->isMaxNumberOfMetricsReached();
    }

    public function isFlushMetricsQueue(): bool
    {
        return $this->flushMetricsQueue;
    }

    public function setFlushMetricsQueue(bool $flushMetricsQueue): self
    {
        $this->flushMetricsQueue = $flushMetricsQueue;

        return $this;
    }

    public function isMaxNumberOfMetricsReached(): bool
    {
        return
            !empty($this->maxNumberOfMetricToQueue) &&
            ($this->getMetrics()->count() >= $this->maxNumberOfMetricToQueue);
    }

    public function getMetrics(): \SplQueue
    {
        return $this->metrics;
    }

    public function sendMetrics(): bool
    {
        if ($this->metrics->isEmpty()) {
            return true;
        }

        $this->client->sendLines($this->getMetricsAsArray());
        $this->clearMetricsQueue();

        return true;
    }

    /**
     * Format data to send to the server
     */
    private function getMetricsAsArray(): array
    {
        $metrics = [];
        foreach ($this->getMetrics() as $metric) {
            if ($metric instanceof MetricInterface) {
                try {
                    $metrics[] = $metric->toString();
                } catch (MetricException $e) {
                }
            }
        }

        return $metrics;
    }

    private function clearMetricsQueue(): self
    {
        $this->initMetricsQueue();

        return $this;
    }

    public function addMetricToQueue(MetricInterface $metric): self
    {
        $this->metrics->enqueue($metric);

        return $this;
    }

    public function removeLastMetricFromQueue()
    {
        $this->metrics->dequeue();
    }

    public function setMaxNumberOfMetricToQueue($maxNumberOfMetricToQueue): self
    {
        $this->maxNumberOfMetricToQueue = $maxNumberOfMetricToQueue;

        return $this;
    }
}
