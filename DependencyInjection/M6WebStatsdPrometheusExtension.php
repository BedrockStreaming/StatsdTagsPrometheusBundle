<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\DependencyInjection;

use M6Web\Bundle\StatsdPrometheusBundle\Client\Server;
use M6Web\Bundle\StatsdPrometheusBundle\Client\UdpClient;
use M6Web\Bundle\StatsdPrometheusBundle\DataCollector\StatsdDataCollector;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\ConsoleListener;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\EventListener;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\KernelEventsListener;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class M6WebStatsdPrometheusExtension extends ConfigurableExtension
{
    const CONFIG_ROOT_KEY = 'm6web_statsd_prometheus';

    /** @var bool */
    private $enabled;

    /** @var ContainerBuilder */
    private $container;

    /** @var string */
    private $metricsPrefix = '';

    /** @var array */
    private $clientServiceIds = [];

    /** @var array */
    private $servers;

    /** @var array */
    private $clients;

    /** @var array */
    private $tags;

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $this->container = $container;

        $loader = new Loader\YamlFileLoader($this->container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->enabled = $config['enabled'] ?? false;
        $this->metricsPrefix = $config['metrics']['prefix'] ?? '';
        $this->servers = $config['servers'] ?? [];
        $this->clients = $config['clients'] ?? [];
        $this->tags = $config['tags'] ?? [];

        if (!$this->isEnabled()) {
            //If the bundle is disabled, we don't load the client configuration

            return;
        }

        foreach ($this->clients as $alias => $clientConfig) {
            $this->clientServiceIds[] = $this->setEventListenerAsServiceAndGetServiceId(
                $alias,
                $clientConfig,
                $this->tags
            );
        }

        $this->registerConsoleEventListener();

        $this->container->autowire(KernelEventsListener::class)->setAutoconfigured(true);

        if ($this->container->hasParameter('kernel.debug')
            && $this->container->getParameter('kernel.debug')) {
            $this->loadDebugConfiguration();
        }
    }

    public function getServers(): array
    {
        return $this->servers;
    }

    public function getClients(): array
    {
        return $this->clients;
    }

    public function getAlias(): string
    {
        return self::CONFIG_ROOT_KEY;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Load a client configuration as an event listener service in the container. A client can use multiple servers
     *
     * @param string $clientName   Alias set in the configuration for the current client
     * @param array  $clientConfig Client config with servers config and groups config
     * @param array  $tagsConfig   Optional tags defined at the top level
     */
    protected function setEventListenerAsServiceAndGetServiceId(string $clientName, array $clientConfig, array $tagsConfig): string
    {
        $serviceId = $this->getServiceIdFrom($clientName);

        // Set the event listener service
        $eventListenerDefinition = $this->getEventListenerDefinition($clientName, $clientConfig['server']);

        // Parse config to add "event_listener" tag on the Listener
        $this->addEventListenerTagsOnServiceDefinition($eventListenerDefinition, $clientConfig['groups'], $tagsConfig);

        // Set the max number of metric to queue before sending them.
        if (isset($clientConfig['max_queued_metrics'])) {
            $eventListenerDefinition->addMethodCall('setMaxNumberOfMetricToQueue', [
                $clientConfig['max_queued_metrics'],
            ]);
        }

        // Add service to the container
        $this->container->setDefinition($serviceId, $eventListenerDefinition);

        return $serviceId;
    }

    protected function getServiceIdFrom(string $alias): string
    {
        return ($alias === 'default') ? self::CONFIG_ROOT_KEY : self::CONFIG_ROOT_KEY.$alias;
    }

    /**
     * Add the "kernel event listener" tag on the given event listener service definition
     *
     * @param Definition $eventListenerDefinition
     * @param array      $groupConfig
     * @param array      $tagsConfig
     */
    protected function addEventListenerTagsOnServiceDefinition(Definition $eventListenerDefinition, array $groupConfig, array $tagsConfig): void
    {
        // Define event listener on events
        foreach ($groupConfig as $eventsGroupName => $eventsGroupConfig) {
            foreach ($eventsGroupConfig['events'] as $eventName => $eventConfig) {
                foreach ($eventConfig['metrics'] as &$metricConfig) {
                    // Parse each metric to get configuration tags (client and group tags)
                    // Their values are defined in the configuration
                    // Whereas metrics tags values are defined when the event is actually sent.
                    // So, both "tags" types are handled differently.
                    $metricConfig['configurationTags'] = \array_merge(
                        $tagsConfig,
                        $eventsGroupConfig['tags'] ?? []
                    );
                    // Prefix the metric name.
                    $metricConfig['name'] = $this->metricsPrefix.$metricConfig['name'];
                }
                // Set all the metrics config array n the object
                // One event can send several metrics. Multiple metrics will be handled in the Listener manager.
                $eventListenerDefinition
                    ->addTag('kernel.event_listener', [
                        'event' => $eventName,
                        'method' => 'handleEvent',
                    ])
                    ->addMethodCall('addEventToListen', [
                        $eventName,
                        $eventConfig,
                    ]);
            }
        }

        $eventListenerDefinition
            // Define event listener on kernel terminate
            ->addTag('kernel.event_listener', [
                'event' => 'kernel.terminate',
                'method' => 'onKernelTerminate',
                'priority' => -100,
            ])
            // Define event listener on console terminate
            ->addTag(
                'kernel.event_listener', [
                'event' => 'console.terminate',
                'method' => 'onConsoleTerminate',
                'priority' => -100,
            ]);
    }

    protected function loadDebugConfiguration(): void
    {
        $definition = new Definition(StatsdDataCollector::class);
        $definition->setPublic(true);
        $definition->addTag('data_collector', [
            'template' => '@M6WebStatsd/Collector/statsd.html.twig',
            'id' => 'statsd',
        ]);

        $definition->addTag('kernel.event_listener', [
            'event' => 'kernel.response',
            'method' => 'onKernelResponse',
        ]);

        foreach ($this->clientServiceIds as $serviceId) {
            $definition->addMethodCall('addEventListener', [
                $serviceId,
                new Reference($serviceId),
            ]);
        }

        $this->container->setDefinition('m6.data_collector.statsd', $definition);
    }

    protected function getEventListenerDefinition(string $clientName, string $serverName): Definition
    {
        return (new Definition(EventListener::class))
            ->setPublic(true)
            ->addMethodCall('setMetricHandler', [
                $this->getMetricHandlerDefinition($clientName, $serverName),
            ]);
    }

    protected function getMetricHandlerDefinition(string $clientName, string $serverName): Definition
    {
        return (new Definition(MetricHandler::class))
            ->addMethodCall('setClient', [
                $this->getMetricUdpClientDefinition($clientName, $serverName),
            ])
            // We define here every service that we can inject in metric resolution.
            // We use it only for tags right now. That enables us to use complicated tag names
            // such as '@=container.get('kernel')'
            // See the documentation for further help
            ->addMethodCall('setContainer', [new Reference('service_container')])
            ->addMethodCall('setRequestStack', [new Reference('request_stack')]);
    }

    protected function getMetricUdpClientDefinition(string $clientName, string $serverName): Definition
    {
        return new Definition(UdpClient::class, [
            $this->getClientServerDefinition($clientName, $serverName),
        ]);
    }

    protected function getClientServerDefinition(string $clientName, string $serverName): Definition
    {
        // No server found.
        if (!\array_key_exists($serverName, $this->servers)) {
            throw new InvalidConfigurationException(sprintf(
                'M6WebStatsd client %s used server %s which is not defined in the servers section',
                $clientName,
                $serverName
            ));
        }
        // Matched server configurations.
        return new Definition(Server::class, [
            $serverName,
            $this->servers[$serverName],
        ]);
    }

    protected function registerConsoleEventListener(): void
    {
        $this->container
            ->register('m6.listener.statsd_prometheus.console', ConsoleListener::class)
            ->addTag(
                'kernel.event_listener',
                ['event' => 'console.command', 'method' => 'onCommand']
            )
            ->addTag(
                'kernel.event_listener',
                ['event' => 'console.exception', 'method' => 'onException']
            )
            ->addTag(
                'kernel.event_listener',
                ['event' => 'console.terminate', 'method' => 'onTerminate']
            )
            ->addMethodCall('setEventDispatcher', [new Reference('event_dispatcher')]);
    }
}
