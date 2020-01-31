<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Listener\EventListener;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use M6Web\Bundle\StatsdPrometheusBundle\Tests\TestMonitoringEvent;
use PHPUnit\Framework\TestCase;

class EventListenerTest extends TestCase
{
    public function testSetFlushMetricsQueueIsAlwaysCalledWhenHandledEventIsSentInHandleEvent()
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

    public function testSetFlushMetricsQueueIsNotCalledWhenNotHandledEventIsSentInHandleEvent()
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

    public function testAddMetricToQueueFunctionIsCalledOnceWhenHandledEventWithOneMetricIsSentInHandleEventFunction()
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

    public function testAddMetricToQueueFunctionIsCalledTwiceWhenHandledEventWithTwoMetricsIsSentInHandleEventFunction()
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

    public function testAddMetricToQueueFunctionIsNotCalledTwiceWhenHandledEventWithNoMetricIsSentInHandleEventFunction()
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

    public function testAddMetricToQueueFunctionIsNotCalledTwiceWhenNotHandledEventIsSentInHandleEventFunction()
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

    public function testTryToSendMetricsFunctionIsCalledOnceWhenHandledEventIsSent()
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

    public function testTryToSendMetricsFunctionIsNotCalledOnceWhenNotHandledEventIsSent()
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMetricHandlerMock()
    {
        return $this->createMock(MetricHandler::class);
    }

    private function getEventListenerObject($metricHandler): EventListener
    {
        return new EventListener($metricHandler);
    }
}
