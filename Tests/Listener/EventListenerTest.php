<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Listener\EventListener;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use M6Web\Bundle\StatsdPrometheusBundle\Tests\TestMonitoringEvent;
use PHPUnit\Framework\TestCase;

class EventListenerTest extends TestCase
{
    public function testSetFlushMetricsQueueIsAlwaysCalledWhenHandledEventIsSentInHandleEvent(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $eventConfig = [
            'flush_metrics_queue' => false,
            'metrics' => [
                [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
            ],
        ];
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock)
            ->addEventToListen($eventName, $eventConfig);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->once())
            ->method('setFlushMetricsQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testSetFlushMetricsQueueIsNotCalledWhenNotHandledEventIsSentInHandleEvent(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->never())
            ->method('setFlushMetricsQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testAddMetricToQueueFunctionIsCalledOnceWhenHandledEventWithOneMetricIsSentInHandleEventFunction(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $eventConfig = [
            'flush_metrics_queue' => false,
            'metrics' => [
                [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
            ],
        ];
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock)
            ->addEventToListen($eventName, $eventConfig);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->once())
            ->method('addMetricToQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testAddMetricToQueueFunctionIsCalledTwiceWhenHandledEventWithTwoMetricsIsSentInHandleEventFunction(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $eventConfig = [
            'flush_metrics_queue' => false,
            'metrics' => [
                [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                [
                    'type' => 'counter',
                    'name' => 'http.status.200',
                    'param_value' => 'counter',
                    'configurationTags' => [],
                    'tags' => [],
                ],
            ],
        ];
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock)
            ->addEventToListen($eventName, $eventConfig);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->exactly(2))
            ->method('addMetricToQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testAddMetricToQueueFunctionIsNotCalledTwiceWhenHandledEventWithNoMetricIsSentInHandleEventFunction(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $eventConfig = [
            'flush_metrics_queue' => false,
            'metrics' => [],
        ];
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock)
            ->addEventToListen($eventName, $eventConfig);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->never())
            ->method('addMetricToQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testAddMetricToQueueFunctionIsNotCalledTwiceWhenNotHandledEventIsSentInHandleEventFunction(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->never())
            ->method('addMetricToQueue');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testTryToSendMetricsFunctionIsCalledOnceWhenHandledEventIsSent(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $eventConfig = [
            'flush_metrics_queue' => false,
            'metrics' => [
                [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
            ],
        ];
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock)
            ->addEventToListen($eventName, $eventConfig);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->once())
            ->method('tryToSendMetrics');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    public function testTryToSendMetricsFunctionIsNotCalledOnceWhenNotHandledEventIsSent(): void
    {
        // -- Given --
        $monitoringEvent = new TestMonitoringEvent();
        $eventName = 'http_status_listener';
        $metricHandlerMock = $this->getMetricHandlerMock();
        $eventListener = $this->getEventListenerObject($metricHandlerMock);
        // -- Expects --
        $metricHandlerMock
            ->expects($this->never())
            ->method('tryToSendMetrics');
        // -- When --
        $eventListener->handleEvent($monitoringEvent, $eventName);
    }

    /**
     * @return MetricHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getMetricHandlerMock()
    {
        return $this->createMock(MetricHandler::class);
    }

    private function getEventListenerObject(MetricHandler $metricHandler): EventListener
    {
        return new EventListener($metricHandler);
    }
}
