<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\DataCollector;

use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class StatsdDataCollector extends DataCollector
{
    /** @var array */
    private $statsdClients;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset the data collector to initial state
     */
    public function reset()
    {
        $this->statsdClients = [];
        $this->data = [
            'clients' => [],
            'operations' => 0,
        ];
    }

    /**
     * Kernel event
     *
     * @param Event $event The received event
     */
    public function onKernelResponse($event)
    {
        if (HttpKernelInterface::MASTER_REQUEST == $event->getRequestType()) {
            foreach ($this->statsdClients as $clientName => $client) {
                $clientInfo = [
                    'name' => $clientName,
                    'operations' => [],
                ];
                foreach ($client->getMetricHandler()->getMetrics() as $metric) {
                    if ($metric instanceof MetricInterface) {
                        try {
                            $clientInfo['operations'][] = [
                                'message' => $metric->toString(),
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
     * Add a statsd client to monitor
     *
     * @param string $clientAlias  The client alias
     * @param object $statsdClient A statsd client instance
     */
    public function addStatsdClient($clientAlias, $statsdClient)
    {
        $this->statsdClients[$clientAlias] = $statsdClient;
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
