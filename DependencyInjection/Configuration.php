<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(M6WebStatsdPrometheusExtension::CONFIG_ROOT_KEY);
        $rootNode = $this->getRootNode($treeBuilder, M6WebStatsdPrometheusExtension::CONFIG_ROOT_KEY);

        $this->addMetricsSection($rootNode);
        $this->addServersSection($rootNode);
        $this->addClientsSection($rootNode);
        $this->addTagsSection($rootNode);
        $this->addDefaultEventSection($rootNode);

        return $treeBuilder;
    }

    private function addMetricsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('metrics')
                    ->children()
                        ->scalarNode('prefix')->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addServersSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('servers')
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('address')
                                ->isRequired()
                                ->validate()
                                    ->ifTrue(function ($v) {return substr($v, 0, 6) !== 'udp://'; })
                                    ->thenInvalid("address parameter should begin with 'udp://'")
                                ->end()
                            ->end()
                            ->scalarNode('port')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addClientsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('alias', false)
                    ->prototype('array')
                        ->children()
                            ->scalarNode('server')->cannotBeEmpty()
                            ->end()
                            ->arrayNode('groups')
                                ->cannotBeEmpty()
                                ->useAttributeAsKey('groupName')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('tags')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->append($this->getClientsGroupsEvents())
                                    ->end()
                                ->end()
                            ->end()
                            ->integerNode('max_queued_metrics')->isRequired()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    private function addTagsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('tags')
                    ->prototype('scalar')->end()
                ->end();
    }

    private function addDefaultEventSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->booleanNode('base_collectors')
                    ->defaultFalse()
                ->end()
                ->booleanNode('console_events')
                    ->defaultFalse()
                ->end();
    }

    private function getClientsGroupsEvents()
    {
        $treeBuilder = new TreeBuilder('events');
        $eventsNode = $this->getRootNode($treeBuilder, 'events');

        $eventsNode
            ->cannotBeEmpty()
            ->useAttributeAsKey('eventName')
            ->prototype('array')
                ->children()
                    ->booleanNode('flush_metrics_queue')->defaultFalse()->end()
                    ->arrayNode('metrics')
                        ->cannotBeEmpty()
                        ->prototype('array')
                            ->children()
                                ->enumNode('type')->isRequired()->values(['increment', 'counter', 'gauge', 'timer'])->end()
                                ->scalarNode('name')->isRequired()->end()
                                ->scalarNode('param_value')->cannotBeEmpty()->end()
                                ->arrayNode('tags')
                                    ->prototype('scalar')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $eventsNode;
    }

    private function getRootNode(TreeBuilder $treeBuilder, $name)
    {
        // BC layer for symfony/config 4.1 and older
        if (!\method_exists($treeBuilder, 'getRootNode')) {
            return $treeBuilder->root($name);
        }

        return $treeBuilder->getRootNode();
    }
}
