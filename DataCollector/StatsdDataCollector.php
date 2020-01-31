<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\DataCollector;

use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\EventListener;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\EventDispatcher\Event;

class StatsdDataCollector extends DataCollector
{
    /** @var EventListener[] */
    private $eventListeners;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the data collector to initial state
     */
    public function reset()
    {
        $this->eventListeners = [];
        $this->data = [
            'clients' => [],
            'operations' => 0,
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (HttpKernelInterface::MASTER_REQUEST === $event->getRequestType()) {
            foreach ($this->eventListeners as $serviceId => $eventListener) {
                $clientInfo = [
                    'name' => $serviceId,
                    'operations' => [],
                ];
                $metricHandler = $eventListener->getMetricHandler();

                foreach ($metricHandler->getMetrics() as $metric) {
                    if ($metric instanceof MetricInterface) {
                        try {
                            $clientInfo['operations'][] = [
                                'message' => $metricHandler->getFormattedMetric($metric),
                            ];
                            $this->data['operations']++;
                        } catch (MetricException $e) {
                        }
                    }
                }
                $this->data['clients'][] = $clientInfo;
            }
        }
    }

    /**
     * Add a Prometheus event listener to monitor
     */
    public function addEventListener(string $serviceId, EventListener $eventListener)
    {
        $this->eventListeners[$serviceId] = $eventListener;
    }

    /**
     * Collect the data
     *
     * @param Request    $request   The request object
     * @param Response   $response  The response object
     * @param \Exception $exception An exception
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * Return the list of statsd operations
     *
     * @return array operations list
     */
    public function getClients()
    {
        return $this->data['clients'];
    }

    /**
     * Return the number of statsd operations
     *
     * @return int the number of operations
     */
    public function getOperations()
    {
        return $this->data['operations'];
    }

    /**
     * Return the name of the collector
     *
     * @return string data collector name
     */
    public function getName()
    {
        return 'statsd';
    }
}
