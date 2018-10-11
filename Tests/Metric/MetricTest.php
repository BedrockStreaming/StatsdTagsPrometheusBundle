<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\Metric;
use PHPUnit\Framework\TestCase;

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
                new MonitoringEvent(),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'http_request_total',
            ],
            [
                new MonitoringEvent(['placeHolder' => 'custom_name']),
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
                new MonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'c',
            ],
            [
                new MonitoringEvent(),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'c',
            ],
            [
                new MonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'gauge',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                'g',
            ],
            [
                new MonitoringEvent(['placeHolder' => 'custom_name']),
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
            [
                new MonitoringEvent(['placeHolder' => 'custom_name']),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                ],
                '1',
            ],
            [
                new MonitoringEvent(['customValue' => 12]),
                [
                    'type' => 'counter',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '12',
            ],
            [
                new MonitoringEvent(['customValue' => 205]),
                [
                    'type' => 'gauge',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '205',
            ],
            [
                new MonitoringEvent(['customValue' => 12045465]),
                [
                    'type' => 'timer',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [],
                    'param_value' => 'customValue',
                ],
                '12045465',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderGetMetricsTag
     */
    public function testGetResolvedTagsReturnsExpected($event, $metricConfig, $expectedResult)
    {
        // -- Given --
        $metric = new Metric($event, $metricConfig);
        // -- Expects
        $this->assertSame($expectedResult, $metric->getResolvedTags());
    }

    public function dataProviderGetMetricsTag()
    {
        return [
            [
                new MonitoringEvent(['tag1' => 'tag_value']),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [
                        'tag1' => null,
                    ],
                ],
                [
                    'tag1' => 'tag_value',
                ],
            ],
            [
                new MonitoringEvent([
                    'tag1' => 'tag_value',
                    'tag2' => 'tag_value2',
                    'wrongTag' => 'willBeIgnored',
                ]),
                [
                    'type' => 'increment',
                    'name' => 'http_request_total',
                    'configurationTags' => [],
                    'tags' => [
                        'tag1' => null,
                        'tag2' => null,
                    ],
                ],
                [
                    'tag1' => 'tag_value',
                    'tag2' => 'tag_value2',
                ],
            ],
        ];
    }
}
