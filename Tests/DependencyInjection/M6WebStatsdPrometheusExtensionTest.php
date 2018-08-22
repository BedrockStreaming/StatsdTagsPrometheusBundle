<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\DependencyInjection;

use M6Web\Bundle\StatsdPrometheusBundle\DependencyInjection\M6WebStatsdPrometheusExtension;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class M6WebStatsdPrometheusExtensionTest extends \PHPUnit_Framework_TestCase
{
    use \Symfony\Component\VarDumper\Test\VarDumperTestTrait;

    /** @var ContainerBuilder */
    private $container;

    /** @var M6WebStatsdPrometheusExtension */
    private $extension;

    /**
     * @dataProvider dataProviderForGetServersReturnsExpectation
     */
    public function testGetServersReturnsExpectation(array $config, array $expected)
    {
        // -- When --
        $this->extension->load([$config], $this->container);
        // -- Then --
        $this->assertEquals($expected, $this->extension->getServers());
    }

    /**
     * @dataProvider dataProviderForGetClientsReturnsExpectation
     */
    public function testGetClientsReturnsExpectation(array $config, array $expected)
    {
        // -- When --
        $this->extension->load([$config], $this->container);
        // -- Then --
        $this->assertEquals($expected, $this->extension->getClients());
    }

    public function testLoadCorrectTagsConfigurationDoesNoesNotThrowException()
    {
        // -- Given --
        $config = [
            'tags' => [
                'tagA' => 'tagAValue',
                'tagB' => 'tagAValueB',
            ],
        ];
        // -- Expects --
        // NO exceptions
        // -- When --
        $this->extension->load([$config], $this->container);
    }

    public function testLoadWrongTagsConfigurationDoesThrowsException()
    {
        // -- Given --
        $config = [
            'tagged' => [
                ['tagB'],
            ],
        ];
        // -- Expects --
        $this->expectException(InvalidConfigurationException::class);
        // -- When --
        $this->extension->load([$config], $this->container);
    }

    public function testLoadCorrectYmlConfigurationFileDoesNotThrowException()
    {
        // -- Given --
        $config = Yaml::parseFile(__DIR__.'/../Fixtures/CorrectConfigurationFileTest.yml');
        // -- Expects --
        // NO exceptions
        // -- When --
        $this->extension->load([$config[M6WebStatsdPrometheusExtension::CONFIG_ROOT_KEY]], $this->container);
    }

    public function testLoadWrongYmlConfigurationFileThrowsException()
    {
        // -- Given --
        $config = Yaml::parseFile(__DIR__.'/../Fixtures/WrongConfigurationFileTest.yml');
        // -- Expects --
        $this->expectException(InvalidConfigurationException::class);
        // -- When --
        $this->extension->load([$config[M6WebStatsdPrometheusExtension::CONFIG_ROOT_KEY]], $this->container);
    }

    public function dataProviderForGetServersReturnsExpectation(): array
    {
        return [
            'test1' => [
                // Configuration
                [
                    'servers' => [
                        'default' => [
                            'address' => 'udp://192.168.1.1',
                            'port' => 3000,
                        ],
                    ],
                ],
                // Expected result
                [
                    'default' => [
                        'address' => 'udp://192.168.1.1',
                        'port' => 3000,
                    ],
                ],
            ],
        ];
    }

    public function dataProviderForGetClientsReturnsExpectation()
    {
        return [
            'test1' => [
                // Configuration (long one)
                [
                    'servers' => [
                        'default' => [
                            'address' => 'udp://address',
                            'port' => '3321',
                        ],
                    ],
                    'clients' => [
                        'default' => [
                            'server' => 'default',
                            'groups' => [
                                'groupA' => [
                                    'tags' => [
                                        'tagB' => 'test',
                                    ],
                                    'events' => [
                                        'eventNameA' => [
                                            'flush_metrics_queue' => true,
                                            'metrics' => [
                                                [
                                                    'type' => 'increment',
                                                    'name' => 'metricName',
                                                    'tags' => ['tagA', 'tagB'],
                                                ],
                                                [
                                                    'type' => 'timer',
                                                    'name' => 'metricName',
                                                    'tags' => ['tagC', 'tagD'],
                                                    'param_value' => 'paramValue',
                                                ],
                                            ],
                                        ],
                                        'eventNameB' => [
                                            'metrics' => [
                                                [
                                                    'type' => 'gauge',
                                                    'name' => 'metricName',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                                'groupB' => [
                                    'tags' => [
                                        'tagC' => 'projectName',
                                    ],
                                    'events' => [
                                        'eventNameC' => [
                                            'metrics' => [
                                                [
                                                    'type' => 'counter',
                                                    'name' => 'metricName',
                                                    'tags' => ['tagC', 'tagD'],
                                                ],
                                            ],
                                        ],
                                        'eventNameD' => [
                                            'flush_metrics_queue' => true,
                                            'metrics' => [
                                                [
                                                    'type' => 'timer',
                                                    'name' => 'metricName',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                // Expected Clients
                [
                    'default' => [
                        'server' => 'default',
                        'groups' => [
                            'groupA' => [
                                'tags' => [
                                    'tagB' => 'test',
                                ],
                                'events' => [
                                    'eventNameA' => [
                                        'flush_metrics_queue' => true,
                                        'metrics' => [
                                            [
                                                'type' => 'increment',
                                                'name' => 'metricName',
                                                'tags' => ['tagA', 'tagB'],
                                            ],
                                            [
                                                'type' => 'timer',
                                                'name' => 'metricName',
                                                'tags' => ['tagC', 'tagD'],
                                                'param_value' => 'paramValue',
                                            ],
                                        ],
                                    ],
                                    'eventNameB' => [
                                        'flush_metrics_queue' => false,
                                        'metrics' => [
                                            [
                                                'type' => 'gauge',
                                                'name' => 'metricName',
                                                'tags' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'groupB' => [
                                'tags' => [
                                    'tagC' => 'projectName',
                                ],
                                'events' => [
                                    'eventNameC' => [
                                        'flush_metrics_queue' => false,
                                        'metrics' => [
                                            [
                                                'type' => 'counter',
                                                'name' => 'metricName',
                                                'tags' => ['tagC', 'tagD'],
                                            ],
                                        ],
                                    ],
                                    'eventNameD' => [
                                        'flush_metrics_queue' => true,
                                        'metrics' => [
                                            [
                                                'type' => 'timer',
                                                'name' => 'metricName',
                                                'tags' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = new ContainerBuilder();
        $this->extension = new M6WebStatsdPrometheusExtension();
    }
}
