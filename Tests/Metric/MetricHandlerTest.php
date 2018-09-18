<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Test\Metric;

use Fixtures\CustomEventTest;
use M6Web\Bundle\StatsdPrometheusBundle\Client\UdpClient;
use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @dataProvider getDataEventsWithFormattedMetrics
     */
    public function testGetFormattedMetricsReturnsExpected(
        Event $event, array $metricConfig, string $expectedResult
    ) {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        $metricHandler = $this->getMetricHandlerObject();
        // -- Then --
        $this->assertSame($expectedResult, $metricHandler->getFormattedMetric($metric));
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

    public function getDataEventsWithFormattedMetrics()
    {
        return [
            // Increment: object Event (no tags)
            'test0' => [
                'event' => (new Event()),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                // Only the metric, type and value are returned
                'expectedResult' => 'http.status.200:1|c',
            ],
            // Increment: object Event (With tags)
            'test1' => [
                'event' => (new Event()),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [
                        // This corresponds to a tag defined in the config (client or group config)
                        'project' => 'service-6play-users',
                    ],
                    'tags' => [
                        // these ones with no value corresponds to events tags. they're values have to
                        // be specified in the event object
                        'country' => null,
                        'platform' => null,
                    ],
                ],
                // Only the metric name, type, value and configurationTags are returned
                // The custom metric tags are ignored because, the object does not instantiate MonitoringEventInterface
                'expectedResult' => 'http.status.200:1|c|#project:service-6play-users',
            ],
            // Increment: object MonitoringEvent (no tags)
            'test2' => [
                'event' => (new MonitoringEvent()),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'http.status.200',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'expectedResult' => 'http.status.200:1|c',
            ],
            // Increment: object MonitoringEvent (With tags)
            'test3' => [
                'event' => (new MonitoringEvent([
                    // This param return the metric value
                    'counterValue' => 124,
                    // Those metric tags values are specified when we send the event
                    'country' => 'France',
                    'platform' => 'm6web',
                ])),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'http.status.200',
                    'param_value' => 'counterValue',
                    'configurationTags' => [
                        // This corresponds to a tag defined in the config (client or group config)
                        'project' => 'service-6play-users',
                    ],
                    'tags' => [
                        // these ones with no value corresponds to events tags. they're values have to
                        // be specified in the event object
                        'country' => null,
                        'platform' => null,
                    ],
                ],
                // We are supposed to have all the metric data and every defined tags
                'expectedResult' => 'http.status.200:124|c|#project:service-6play-users,country:France,platform:m6web',
            ],
            // Counter with custom param: object MonitoringEvent (no tags)
            'test4' => [
                'event' => (new MonitoringEvent([
                    'getCustomParam' => 32546,
                ])),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'specific_sql_query',
                    'param_value' => 'getCustomParam',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                // We are supposed to get the metric name, type and the custom parameter value
                'expectedResult' => 'specific_sql_query:32546|c',
            ],
            // Counter with custom param: object MonitoringEvent (With tags)
            'test5' => [
                'event' => (new MonitoringEvent([
                    'getCustomParam' => 12456,
                    'project' => 'service-6play-users',
                    'country' => 'France',
                    'platform' => 'm6web',
                ])),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'specific_sql_query',
                    'param_value' => 'getCustomParam',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => ['country' => null, 'platform' => null],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query:12456|c|#project:service-6play-users-cloud,country:France,platform:m6web',
            ],
            // Increment with dynamic metric name (no tags)
            'test6' => [
                'event' => (new MonitoringEvent([
                    'myFirstPlaceHolder' => 'myPlaceHolderValue',
                ])),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query.<myFirstPlaceHolder>',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.myPlaceHolderValue:1|c',
            ],
            // Increment with multiple dynamic metric name with multiple placeholders (With tags) => COMBO
            'test7' => [
                'event' => (new MonitoringEvent([
                    'myFirstPlaceHolder' => 'firstPlaceHolderValue',
                    'mySecondPlaceHolder' => 'secondPlaceHolderValue',
                ])),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query.http',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => [
                        'country' => null,
                        'platform' => null,
                        'myFirstPlaceHolder' => null,
                        'mySecondPlaceHolder' => null,
                    ],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.http:1|c|#project:service-6play-users-cloud,myFirstPlaceHolder:firstPlaceHolderValue,mySecondPlaceHolder:secondPlaceHolderValue',
            ],
            // Counter with custom param: object CustomEvent (no tags) => Legacy compatibility
            'test8' => [
                'event' => (new CustomEventTest(12)),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'specific_sql_query',
                    'param_value' => 'getValue', // Legacy support <3
                    'configurationTags' => [],
                    'tags' => [],
                ],
                // We have the metric name, type and the custom value
                'expectedResult' => 'specific_sql_query:12|c',
            ],
            // Counter with custom param: object Event (With tags)
            'test9' => [
                'event' => (new CustomEventTest(12)),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'specific_sql_query',
                    'param_value' => 'getValue',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => ['country' => null, 'platform' => null],
                ],
                // Only the metric name, type, value and configurationTags are returned
                // The custom metric tags are ignored because, the object does not instantiate MonitoringEventInterface
                'expectedResult' => 'specific_sql_query:12|c|#project:service-6play-users-cloud',
            ],
            // Increment with dynamic metric name (no tags)
            'test10' => [
                'event' => (new CustomEventTest(12, 'placeHolder1Value')),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query.<placeHolder1>',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.placeHolder1Value:1|c',
            ],
            // Increment with multiple dynamic metric name with multiple placeholders (With tags) => COMBO
            'test11' => [
                'event' => (new CustomEventTest(null, 'placeHolder1Value', 'placeHolder2Value')),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query.<placeHolder1>.http.<placeHolder2>',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => ['country' => null, 'platform' => null],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.placeHolder1Value.http.placeHolder2Value:1|c|#project:service-6play-users-cloud',
            ],
            // Increment with multiple tags sent with custom event (with property accessors)
            'test12' => [
                'event' => (new CustomEventTest(null, 'placeHolder1Value', 'placeHolder2Value')),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query_http',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => [
                        'country' => null,
                        'platform' => null,
                        'placeHolder1' => null,
                        'normalized_tags_name' => 'placeHolder2',
                    ],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query_http:1|c|#project:service-6play-users-cloud,placeHolder1:placeHolder1Value,normalized_tags_name:placeHolder2Value',
            ],
        ];
    }
}
