<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use M6Web\Bundle\StatsdPrometheusBundle\Tests\Fixtures\CustomEventTest;
use M6Web\Bundle\StatsdPrometheusBundle\Tests\TestMonitoringEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class MetricTest extends TestCase
{
    /**
     * @dataProvider dataProviderGetMetricsName
     */
    public function testGetResolvedNameReturnsExpected($event, $metricConfig, $expectedResult)
    {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Expects
        $this->assertSame($expectedResult, $metric->getResolvedName());
    }

    public function dataProviderGetMetricsName()
    {
        return [
            [
                new TestMonitoringEvent(),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'http_request_total',
            ],
            [
                new TestMonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total.<placeHolder>',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'http_request_total.custom_name',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderGetMetricsType
     */
    public function testGetResolvedTypeReturnsExpected($event, $metricConfig, $expectedResult)
    {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Expects
        $this->assertSame($expectedResult, $metric->getResolvedType());
    }

    public function dataProviderGetMetricsType()
    {
        return [
            [
                new TestMonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'c',
            ],
            [
                new TestMonitoringEvent(),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'c',
            ],
            [
                new TestMonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'gauge',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'g',
            ],
            [
                new TestMonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'ms',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderGetMetricsValue
     */
    public function testGetResolvedValueReturnsExpected($event, $metricConfig, $expectedResult)
    {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Expects
        $this->assertSame($expectedResult, $metric->getResolvedValue());
    }

    public function dataProviderGetMetricsValue()
    {
        return [
            'increment value' => [
                new TestMonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                '1',
            ],
            'counter with fixed value' => [
                new TestMonitoringEvent(['customValue' => 12]),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '12',
            ],
            'gauge with fixed value' => [
                new TestMonitoringEvent(['customValue' => 205]),
                [
                    'type' => 'gauge',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '205',
            ],
            'timer in ms with fixed value value corrected by 1000' => [
                new TestMonitoringEvent(['customValue' => 12045465]),
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '12045465000',
            ],
            'timer in s with fixed value corrected by 1000' => [
                new TestMonitoringEvent(['customValue' => 12045.465]),
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '12045465',
            ],
            'custom value from object' => [
                new class() {
                    public function getCustomValue()
                    {
                        return 10;
                    }
                },
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'getCustomValue',
                ],
                '10',
            ],
            'custom value from object corrected by 1000' => [
                new class() {
                    public function getCustomValue()
                    {
                        return 10.002;
                    }
                },
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'getCustomValue',
                ],
                '10002',
            ],
            'timer returning null' => [
                new TestMonitoringEvent(['customValue' => null]),
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '0',
            ],
            'custom value from object return null' => [
                new class() {
                    public function getCustomValue()
                    {
                        return null;
                    }
                },
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'getCustomValue',
                ],
                '0',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderGetMetricsTag
     */
    public function testGetResolvedTagsReturnsExpected($event, array $metricConfig, array $resolvers, array $expectedResult)
    {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Expects
        $this->assertSame($expectedResult, $metric->getResolvedTags($resolvers));
    }

    public function dataProviderGetMetricsTag(): \Generator
    {
        $resolvers = [];

        $monitoringEvent = new TestMonitoringEvent(
            [
                'resolved_tag' => 'resolved-value-auto',
                'explicit_tag' => 'explicit-value',
                'configuration' => 'configuration-parameter',
                'tags' => 'tag-parameter',
            ]
        );

        // Working as expected use cases.

        yield 'empty' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
            ],
        ];

        yield 'static-from-config' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'resolved_tag' => 'configuration-value',
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'configuration-value',
            ],
        ];

        yield 'static-from-tags' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                    'resolved_tag' => 'tag-value',
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'tag-value',
            ],
        ];

        yield 'parameter-from-config' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'resolved_tag' => '%=configuration',
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'configuration-parameter',
            ],
        ];

        yield 'parameter-from-tags' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                    'resolved_tag' => '%=tags',
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'tag-parameter',
            ],
        ];

        yield 'implicit-parameter-from-config' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'resolved_tag' => null,
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'resolved-value-auto',
            ],
        ];

        yield 'implicit-parameter-from-tags' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                    'resolved_tag' => null,
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'resolved-value-auto',
            ],
        ];

        $customEvent = new CustomEventTest(null, 'property-value-1', 'property-value-2');

        yield 'property-from-config' => [
            $customEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'resolved_tag' => '->placeholder1',
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'property-value-1',
            ],
        ];

        yield 'property-from-tags' => [
            $customEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                    'resolved_tag' => '->placeholder2',
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'property-value-2',
            ],
        ];

        $resolvers = [
            'container' => new Container(
                new ParameterBag(
                    [
                        'test_configuration_value' => 'container-configuration-value',
                        'test_tag_value' => 'container-tag-value',
                    ]
                )
            ),
        ];

        yield 'container-from-config' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'resolved_tag' => '@=container.getParameter(\'test_configuration_value\')',
                ],
                'tags' => [
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'container-configuration-value',
            ],
        ];

        yield 'container-from-tags' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                ],
                'tags' => [
                    'resolved_tag' => '@=container.getParameter(\'test_tag_value\')',
                ],
            ],
            $resolvers,
            [
                'resolved_tag' => 'container-tag-value',
            ],
        ];

        // Special cases that return null.

        yield 'empty-values-are-filtered-out' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'empty_config' => '',
                ],
                'tags' => [
                    'empty_tag' => 0,
                ],
            ],
            $resolvers,
            [
            ],
        ];

        yield 'parameters-without-MonitoringEventInterface' => [
            $customEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'empty_config' => '%=placeholder1',
                ],
                'tags' => [
                    'resolved_tag' => '%=placeholder2',
                ],
            ],
            $resolvers,
            [
            ],
        ];

        yield 'parameters-missing-from-MonitoringEventInterface' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'empty_config' => '%=placeholder1',
                ],
                'tags' => [
                    'resolved_tag' => '%=placeholder2',
                ],
            ],
            $resolvers,
            [
            ],
        ];

        yield 'parameters-missing-from-MonitoringEventInterface' => [
            $customEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'empty_config' => '->placeholder3',
                ],
                'tags' => [
                    'resolved_tag' => '->placeholder4',
                ],
            ],
            $resolvers,
            [
            ],
        ];

        // All in one.

        yield 'all-in' => [
            $monitoringEvent,
            [
                'type' => 'increment',
                'name' => 'http_request_total',
                'configurationTags' => [
                    'tag1' => '@=container.getParameter(\'test_configuration_value\')',
                    'tag2' => 'static-value',
                    'tag3' => '->PropertyNotFound',
                ],
                'tags' => [
                    'tag4' => null,
                    'tag5' => '%=explicit_tag',
                    'resolved_tag' => null,
                ],
            ],
            $resolvers,
            [
                'tag1' => 'container-configuration-value',
                'tag2' => 'static-value',
                'tag5' => 'explicit-value',
                'resolved_tag' => 'resolved-value-auto',
            ],
        ];
    }
}
