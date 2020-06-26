<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\PropertyAccess;

class EventListener
{
    /** @var array */
    protected $listenedEvents = [];

    /** @var PropertyAccess\PropertyAccessorInterface */
    protected $propertyAccessor;

    /** @var MetricHandler */
    protected $metricHandler;

    public function __construct(MetricHandler $metricHandler)
    {
        $this->metricHandler = $metricHandler;
    }

    /**
     * @param object $event     Event object sent with the event dispatcher
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

    public function onKernelResponse(ResponseEvent $event): void
    {
        //We only need the master request in order to keep all the original request headers
        //This will be used to resolve advanced configuration tags.
        // such as '@=request.get('queryParam')'
        if ($event->isMasterRequest()) {
            $this->metricHandler->setRequest($event->getRequest());
        }
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->metricHandler->sendMetrics();
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->metricHandler->sendMetrics();
    }

    public function addEventToListen(string $eventName, array $eventConfig): self
    {
        $this->listenedEvents[$eventName] = $eventConfig;

        return $this;
    }

    public function getMetricHandler(): MetricHandler
    {
        return $this->metricHandler;
    }

    public function setMaxNumberOfMetricToQueue(int $maxNumberOfMetricToQueue): void
    {
        $this->metricHandler->setMaxNumberOfMetricToQueue($maxNumberOfMetricToQueue);
    }
}
