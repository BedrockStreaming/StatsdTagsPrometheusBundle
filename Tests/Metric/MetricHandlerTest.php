<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Test\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Client\UdpClient;
use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;

class MetricHandlerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetMetricsReturnsExpectedWhenAddMetric()
    {
        // -- Given --
        $metricHandler = $this->getMetricHandlerObject();
        $metric = new Metric(new MonitoringEvent(), [
            'name' => 'myMetricName',
            'type' => 'increment',
            'configurationTags' => [],
            'tags' => [],
        ]);
        $expected = new \SplQueue();
        $expected->enqueue(
            (new Metric(new MonitoringEvent(), [
                'name' => 'myMetricName',
                'type' => 'increment',
                'configurationTags' => [],
                'tags' => [],
            ]))
        );
        // -- When --
        $metricHandler->addMetricToQueue($metric);
        // -- Then --
        $this->assertEquals($expected, $metricHandler->getMetrics());
    }

    public function testIsFlushMetricsQueueReturnsFalseByDefault()
    {
        // -- Given --
        $metricHandler = $this->getMetricHandlerObject();
        // -- When --
        $isFlushMetricsQueue = $metricHandler->isFlushMetricsQueue();
        // -- Then --
        $this->assertFalse($isFlushMetricsQueue);
    }

    public function testIsFlushMetricsQueueReturnsTrueWhenSetFlushMetricsQueueToTrue()
    {
        // -- Given --
        $metricHandler = $this->getMetricHandlerObject();
        // -- When --
        $metricHandler->setFlushMetricsQueue(true);
        // -- Then --
        $this->assertTrue($metricHandler->isFlushMetricsQueue());
    }

    public function testTryToSendMetricsReturnsFalseWhenFlushMetricsQueueIsFalse()
    {
        // -- Given --
        $metricHandler = $this->getMetricHandlerObject();
        // -- When --
        $metricHandler->setFlushMetricsQueue(false);
        // -- Then --
        $this->assertFalse($metricHandler->tryToSendMetrics());
    }

    public function testTryToSendMetricsReturnsTrueWhenFlushMetricsQueueIsTrue()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(false);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setFlushMetricsQueue(true);
        // -- Then --
        $this->assertTrue($metricHandler->tryToSendMetrics());
    }

    public function testClientSendLinesIsCalledWhenFlushMetricsQueueIsTrueAndQueueNotEmpty()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(false);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        $metricHandler->setFlushMetricsQueue(true);
        // -- Expects --
        $client->expects($this->once())
            ->method('sendLines');
        // -- When --
        $metricHandler->tryToSendMetrics();
    }

    public function testClientSendLinesIsNotCalledWhenFlushMetricsQueueIsTrueAndQueueIsEmpty()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        $metricHandler->setFlushMetricsQueue(true);
        // -- Expects --
        $client->expects($this->never())
            ->method('sendLines');
        // -- When --
        $metricHandler->tryToSendMetrics();
    }

    public function testClientSendLinesIsNotCalledWhenFlushMetricsQueueIsFalse()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(false);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        $metricHandler->setFlushMetricsQueue(false);
        // -- Expects --
        $client->expects($this->never())
            ->method('sendLines');
        // -- When --
        $metricHandler->tryToSendMetrics();
    }

    public function testClientSendLinesIsNotCalledWhenFlushMetricsQueueIsFalseAndMaxNumberNotReached()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true, 1); // A queue with 1 element
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        $metricHandler->setFlushMetricsQueue(false);
        $metricHandler->setMaxNumberOfMetricToQueue(2); // A limit at 2 elements
        // -- Expects --
        $client->expects($this->never())
            ->method('sendLines');
        // -- When --
        $metricHandler->tryToSendMetrics();
    }

    public function testIsMaxNumberReachedReturnsTrueWhenMaxNumberIsReached()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true, 10);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setMaxNumberOfMetricToQueue(5);
        // -- Then --
        $this->assertTrue($metricHandler->isMaxNumberOfMetricsReached());
    }

    public function testIsMaxNumberReachedReturnsFalseWhenMaxNumberIsNotReached()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true, 10);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setMaxNumberOfMetricToQueue(15);
        // -- Then --
        $this->assertFalse($metricHandler->isMaxNumberOfMetricsReached());
    }

    public function testHasToSendMetricsReturnsTrueWhenMaxNumberIsReached()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true, 10);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setMaxNumberOfMetricToQueue(5);
        // -- Then --
        $this->assertTrue($metricHandler->hasToSendMetrics());
    }

    public function testHasToSendMetricsReturnsFalseWhenMaxNumberIsNotReached()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(true, 10);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setMaxNumberOfMetricToQueue(15);
        // -- Then --
        $this->assertFalse($metricHandler->hasToSendMetrics());
    }

    public function testHasToSendMetricsReturnsTrueWhenFlushMetricsQueueIsTrue()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(false);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setFlushMetricsQueue(true);
        // -- Then --
        $this->assertTrue($metricHandler->hasToSendMetrics());
    }

    public function testHasToSendMetricsReturnsFalseWhenFlushMetricsQueueIsFalse()
    {
        // -- Given --
        $client = $this->getUdpClientMock();
        $metricsQueue = $this->getMetricsQueueMock(false);
        $metricHandler = $this->getMetricHandlerObject($client, $metricsQueue);
        // -- When --
        $metricHandler->setFlushMetricsQueue(false);
        // -- Then --
        $this->assertFalse($metricHandler->hasToSendMetrics());
    }

    protected function getMetricHandlerObject($client = null, $metricsQueue = null)
    {
        $metricHandler = new MetricHandler();
        if ($client) {
            $metricHandler->setClient($client);
        }
        if ($metricsQueue) {
            $metricHandler->setMetricsQueue($metricsQueue);
        }

        return $metricHandler;
    }

    private function getMetricsQueueMock(bool $isEmpty, int $count = 0)
    {
        $metricsQueue = $this->createMock(\SplQueue::class);
        $metricsQueue->method('isEmpty')
            ->willReturn($isEmpty);
        $metricsQueue->method('count')
            ->willReturn($count);

        return $metricsQueue;
    }

    private function getUdpClientMock()
    {
        return $this->createMock(UdpClient::class);
    }
}
