<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\DependencyInjection;

use M6Web\Bundle\StatsdPrometheusBundle\Client\Server;
use M6Web\Bundle\StatsdPrometheusBundle\Client\UdpClient;
use M6Web\Bundle\StatsdPrometheusBundle\DataCollector\StatsdDataCollector;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel\KernelExceptionMonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel\KernelTerminateMonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\ConsoleEventsSubscriber;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\EventListener;
use M6Web\Bundle\StatsdPrometheusBundle\Listener\KernelEventsSubscriber;
use M6Web\Bundle\StatsdPrometheusBundle\Metric\MetricHandler;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\HttpKernel\KernelEvents;

class M6WebStatsdPrometheusExtension extends ConfigurableExtension
{
    const CONFIG_ROOT_KEY = 'm6web_statsd_prometheus';

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

    /** @var array */
    private $dispatchedEvents;

    public function loadInternal(array $config, ContainerBuilder $container): void
    {
        $this->container = $container;

        $this->metricsPrefix = $config['metrics']['prefix'] ?? '';
        $this->servers = $config['servers'] ?? [];
        $this->clients = $config['clients'] ?? [];
        $this->tags = $config['tags'] ?? [];
        $this->dispatchedEvents = $config['dispatched_events'];

        foreach ($this->clients as $alias => $clientConfig) {
            $this->clientServiceIds[] = $this->setEventListenerAsServiceAndGetServiceId(
                $alias,
                $clientConfig,
                $this->tags
            );
        }

        $this->registerKernelEventsSubscriber();
        $this->registerConsoleEventsSubscriber();
        $this->loadDebugConfiguration();
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
                // Set all the metrics config array in the object
                // One event can send several metrics. Multiple metrics will be handled in the EventListener.
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

        // Define event listener on kernel terminate
        $eventListenerDefinition->addTag(
            'kernel.event_listener',
            [
                'event' => KernelEvents::TERMINATE,
                'method' => 'onKernelTerminate',
                'priority' => -100,
            ]
        );
        $eventListenerDefinition->addTag(
            'kernel.event_listener',
            [
                'event' => KernelEvents::RESPONSE,
                'method' => 'onKernelResponse',
                'priority' => -100,
            ]
        );
        if ($this->isSymfonyConsoleComponentLoaded()) {
            // Define event listener on console terminate
            $eventListenerDefinition->addTag(
                'kernel.event_listener',
                [
                    'event' => ConsoleEvents::TERMINATE,
                    'method' => 'onConsoleTerminate',
                    'priority' => -100,
                ]
            );
        }
    }

    protected function loadDebugConfiguration(): void
    {
        if (!$this->isDebugEnabled()) {
            return;
        }

        $definition = new Definition(StatsdDataCollector::class);
        $definition->setPublic(true);
        $definition->addTag('data_collector', [
            'template' => '@M6WebStatsdPrometheus/Collector/statsd_prometheus.html.twig',
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
            ->setArguments([
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
            ->addMethodCall('setContainer', [new Reference('service_container')]);
    }

    protected function getMetricUdpClientDefinition(string $clientName, string $serverName): Definition
    {
        return new Definition(UdpClient::class, [
            $this->getClientServerDefinition($clientName, $serverName),
            $this->isDebugEnabled(),
        ]);
    }

    protected function getClientServerDefinition(string $clientName, string $serverName): Definition
    {
        // No server found.
        if (!\array_key_exists($serverName, $this->servers)) {
            throw new InvalidConfigurationException(sprintf('M6WebStatsd client %s used server %s which is not defined in the servers section', $clientName, $serverName));
        }
        // Matched server configurations.
        return new Definition(Server::class, [
            $serverName,
            $this->servers[$serverName],
        ]);
    }

    protected function registerKernelEventsSubscriber(): void
    {
        if (!$this->dispatchedEvents['http']['enable']) {
            return;
        }

        $this->container->autowire(KernelEventsSubscriber::class)
            ->setAutoconfigured(true)
            ->setArgument('$routesForWhichKernelTerminateEventWontBeDispatched', $this->dispatchedEvents['http'][KernelTerminateMonitoringEvent::class]['dispatch_except_for_routes'])
            ->setArgument('$routesForWhichKernelExceptionEventWontBeDispatched', $this->dispatchedEvents['http'][KernelExceptionMonitoringEvent::class]['dispatch_except_for_routes'])
        ;
    }

    protected function registerConsoleEventsSubscriber(): void
    {
        if (
            !$this->dispatchedEvents['console']['enable']
            || !$this->isSymfonyConsoleComponentLoaded()
        ) {
            return;
        }

        $this->container->autowire(ConsoleEventsSubscriber::class)->setAutoconfigured(true);
    }

    protected function isSymfonyConsoleComponentLoaded(): bool
    {
        return class_exists(ConsoleEvents::class);
    }

    protected function isDebugEnabled(): bool
    {
        return $this->container->hasParameter('kernel.debug')
            && $this->container->getParameter('kernel.debug');
    }
}
