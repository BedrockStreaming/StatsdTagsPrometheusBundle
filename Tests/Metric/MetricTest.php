<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Metric;

use Fixtures\CustomEventTest;
use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use Symfony\Component\EventDispatcher\Event;

class MetricTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getDataEvents
     */
    public function testToStringWithEventReturnsExpected(
        Event $event, array $metricConfig, string $expectedResult
    ) {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Then --
        $this->assertSame($expectedResult, $metric->toString());
    }

    /**
     * @dataProvider getDataMonitoringEvents
     */
    public function testToStringWithMonitoringEventReturnsExpected(
        Event $event, array $metricConfig, string $expectedResult
    ) {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Then --
        $this->assertSame($expectedResult, $metric->toString());
    }

    /**
     * @dataProvider getDataLegacyEvents
     */
    public function testToStringWithLegacyEventReturnsExpected(
        Event $event, array $metricConfig, string $expectedResult
    ) {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Then --
        $this->assertSame($expectedResult, $metric->toString());
    }

    public function testSendCounterEventWithNoParamValueThrowsException()
    {
        // -- Given --
        $event = new Event();
        $metricConfig = [
            'type' => 'counter',
            'name' => 'http.status.200',
            'configurationTags' => [],
            'tags' => [],
        ];
        $metric = new Metric($event, $metricConfig);
        // -- Expects --
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The configuration of the event metric '
            .'"Symfony\Component\EventDispatcher\Event" must define the "param_value" option');
        // -- When --
        $metric->toString();
    }

    public function testSendCounterEventWithNoDefinedFunctionValueThrowsException()
    {
        // -- Given --
        $event = new Event();
        $metricConfig = [
            'type' => 'counter',
            'name' => 'http.status.200',
            'param_value' => 'notDefinedFunction',
            'configurationTags' => [],
            'tags' => [],
        ];
        $metric = new Metric($event, $metricConfig);
        // -- Expects --
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The event class "Symfony\Component\EventDispatcher\Event" '
            .'must have a "notDefinedFunction" method or parameters in order to measure value.');
        // -- When --
        $metric->toString();
    }

    public function getDataEvents()
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
                        'country',
                        'platform',
                    ],
                ],
                // Only the metric name, type, value and configurationTags are returned
                // The custom metric tags are ignored because, the object does not instantiate MonitoringEventInterface
                'expectedResult' => 'http.status.200:1|c|#project:service-6play-users',
            ],
        ];
    }

    public function getDataMonitoringEvents()
    {
        return [
            // Increment: object MonitoringEvent (no tags)
            'test0' => [
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
            'test1' => [
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
                        'country',
                        'platform',
                    ],
                ],
                // We are supposed to have all the metric data and every defined tags
                'expectedResult' => 'http.status.200:124|c|#project:service-6play-users,country:France,platform:m6web',
            ],
            // Counter with custom param: object MonitoringEvent (no tags)
            'test2' => [
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
            'test3' => [
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
                    'tags' => ['country', 'platform'],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query:12456|c|#project:service-6play-users-cloud,country:France,platform:m6web',
            ],
            // Increment with dynamic metric name (no tags)
            'test4' => [
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
            'test+INFINITY' => [
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
                    'tags' => ['country', 'platform', 'myFirstPlaceHolder', 'mySecondPlaceHolder'],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.http:1|c|#project:service-6play-users-cloud,myFirstPlaceHolder:firstPlaceHolderValue,mySecondPlaceHolder:secondPlaceHolderValue',
            ],
        ];
    }

    public function getDataLegacyEvents()
    {
        return [
            // Counter with custom param: object CustomEvent (no tags) => Legacy compatibility
            'test0' => [
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
            'test1' => [
                'event' => (new CustomEventTest(12)),
                'eventConfig' => [
                    'type' => 'counter',
                    'name' => 'specific_sql_query',
                    'param_value' => 'getValue',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => ['country', 'platform'],
                ],
                // Only the metric name, type, value and configurationTags are returned
                // The custom metric tags are ignored because, the object does not instantiate MonitoringEventInterface
                'expectedResult' => 'specific_sql_query:12|c|#project:service-6play-users-cloud',
            ],
            // Increment with dynamic metric name (no tags)
            'test2' => [
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
            'test3' => [
                'event' => (new CustomEventTest(null, 'placeHolder1Value', 'placeHolder2Value')),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query.<placeHolder1>.http.<placeHolder2>',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => ['country', 'platform'],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query.placeHolder1Value.http.placeHolder2Value:1|c|#project:service-6play-users-cloud',
            ],
            // Increment with multiple tags sent with custom event (with property accessors)
            'test4' => [
                'event' => (new CustomEventTest(null, 'placeHolder1Value', 'placeHolder2Value')),
                'eventConfig' => [
                    'type' => 'increment',
                    'name' => 'specific_sql_query_http',
                    'configurationTags' => [
                        'project' => 'service-6play-users-cloud',
                    ],
                    'tags' => [
                        'country',
                        'platform',
                        'placeHolder1',
                        'placeHolder2',
                    ],
                ],
                // We are supposed to get the metric name, type, the custom parameter value and all the tags
                'expectedResult' => 'specific_sql_query_http:1|c|#project:service-6play-users-cloud,placeHolder1:placeHolder1Value,placeHolder2:placeHolder2Value',
            ],
        ];
    }
}
