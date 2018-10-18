<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess;

class EventListener
{
    protected $listenedEvents = [];

    /** @var PropertyAccess\PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var MetricHandler */
    protected $metricHandler;

    /**
     * @param mixed  $event     Event object sent with the event dispatcher
     * @param string $eventName name of the event set in the config
     */
    public function handleEvent($event, string $eventName): void
    {
        if (!isset($this->listenedEvents[$eventName])) {
            return;
        }
        $eventConfig = $this->listenedEvents[$eventName];
        if (isset($eventConfig['flush_metrics_queue'])) {
            $this->metricHandler->setFlushMetricsQueue($eventConfig['flush_metrics_queue']);
        }

        foreach ($eventConfig['metrics'] as $metricConfig) {
            //In a few cases, we need to handle events without metrics.
            //Those events are mainly "flush events", used to send immediately the queued metrics.
            $this->metricHandler->addMetricToQueue(
                (new Metric($event, $metricConfig))
            );
        }
        // We ask for the metric handler to try to send the message
        // "Try" means that the handler has to follow some rules, and if those rules are not valid,
        // It won't send anything. The handler knows its job.
        $this->metricHandler->tryToSendMetrics();
    }

    /**
     * method called on the kernel.terminate event
     */
    public function onKernelTerminate(PostResponseEvent $event): void
    {
        $this->handleEvent(new MonitoringEvent([
            'statusCode' => $event->getResponse()->getStatusCode(),
            'routeName' => $event->getRequest()->get('_route', 'undefined'),
            'methodName' => $event->getRequest()->getMethod(),
            'timing' => microtime(true) - $event->getRequest()->server->get('REQUEST_TIME_FLOAT'),
            'memory' => memory_get_peak_usage(true),
            'host' => str_replace('.', '_', $event->getRequest()->getHost()),
        ]), KernelEvents::TERMINATE);

        $this->metricHandler->sendMetrics();
    }

    public function addEventToListen(string $eventName, array $eventConfig): self
    {
        $this->listenedEvents[$eventName] = $eventConfig;

        return $this;
    }

    public function setMetricHandler(MetricHandler $metricHandler): self
    {
        $this->metricHandler = $metricHandler;

        return $this;
    }

    public function getMetricHandler(): MetricHandler
    {
        return $this->metricHandler;
    }
}
