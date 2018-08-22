<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEventInterface;
use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Metric implements MetricInterface
{
    const TYPE_COUNTER = 'c';
    const TYPE_GAUGE = 'g';
    const TYPE_TIMER = 'ms';

    /** @var PropertyAccess */
    protected $propertyAccessor;

    /** @var mixed|Event */
    private $event;

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $paramValue;

    /** @var array */
    private $configurationTags;

    /** @var array */
    private $tags;

    /**
     * Metric constructor.
     *
     * @param Event|mixed $event
     * @param array       $metricConfig
     */
    public function __construct($event, array $metricConfig = [])
    {
        $this->event = $event;

        $this->name = $metricConfig['name'];
        $this->type = $metricConfig['type'];
        $this->paramValue = $metricConfig['param_value'] ?? null;
        $this->configurationTags = $metricConfig['configurationTags'];
        $this->tags = $metricConfig['tags'];

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * DogStatsD FORMAT :
     * <metric>:<value>|<type>|@<sample rate>|#tag:value,another_tag:another_value
     *
     * @throws MetricException
     */
    public function toString(): string
    {
        return vsprintf('%s:%s|%s%s', [
            // Required fields
            $this->getResolvedMetricName(),
            $this->getMetricsValue(),
            $this->getMetricType(),
            // Optional fields
            $this->getMetricTags(),
        ]);
    }

    /**
     * @throws MetricException
     */
    private function getResolvedMetricName(): string
    {
        // We don't want to alter the original metric name
        $resolvedName = $this->name;
        if (preg_match_all('/<([^>]+)>/', $resolvedName, $placeholders) > 0) {
            // We want to get all the matching results with the 1st parenthesis in the Regex.
            // $placeholders[0] will give the entire string with matching pattern
            // $placeholders[1] will give only matching pattern results: that's what we want
            if (isset($placeholders[1])) {
                try {
                    $resolvedName = $this->resolvePlaceholdersInMetricName($resolvedName, $placeholders[1]);
                } catch (\Exception $e) {
                    // We try to throw only MetricExceptions to shut bad configurations exceptions
                    // We consider that we are supposed to know what we do (professional power).
                    throw new MetricException($e->getMessage());
                }
            }
        }

        return $resolvedName;
    }

    private function resolvePlaceholdersInMetricName(string $metricName, array $placeholders)
    {
        foreach ($placeholders as $placeholder) {
            if ($this->event instanceof MonitoringEventInterface) {
                $value = $this->event->getParameter($placeholder);
            } else {
                // Legacy support
                $value = $this->propertyAccessor->getValue($this->event, $placeholder);
            }
            // Replace placeholders with the associated value
            $metricName = str_replace('<'.$placeholder.'>', $value, $metricName);
        }

        return $metricName;
    }

    /**
     * @throws MetricException
     */
    private function getMetricsValue(): float
    {
        switch ($this->type) {
            case 'increment':
                return 1;
            case 'decrement':
                return -1;
        }
        if (empty($this->paramValue)) {
            // This is not allowed anymore for other types than increment and decrement
            throw new MetricException(
                \sprintf('The configuration of the event metric "%s" must define the "param_value" option.',
                    \get_class($this->event))
            );
        }
        if ($this->event instanceof MonitoringEventInterface) {
            // Using the valid event type, values are now in parameters
            return $this->event->getParameter($this->paramValue);
        }
        if (!\method_exists($this->event, $this->paramValue)) {
            // Legacy compatibility
            throw new MetricException(
                \sprintf('The event class "%s" must have a "%s" method or parameters in order to measure value.',
                    \get_class($this->event), $this->paramValue)
            );
        }

        return \call_user_func([$this->event, $this->paramValue]);
    }

    /**
     * @throws MetricException
     */
    private function getMetricType(): string
    {
        switch ($this->type) {
            case 'counter':
            case 'increment':
            case 'decrement':
                return self::TYPE_COUNTER;
            case 'gauge':
                return self::TYPE_GAUGE;
            case 'timer':
                return self::TYPE_TIMER;
        }

        throw new MetricException('This metric type is not handled');
    }

    /**
     * @return string metric labels on format "#label1:value1,label2:value2,label3:value3"
     */
    private function getMetricTags(): string
    {
        $tags = [];

        // Add global parameters (configured in client or group)
        if (!empty($this->configurationTags) && is_array($this->configurationTags)) {
            $tags += $this->configurationTags;
        }
        foreach ($this->tags as $tagName) {
            // Recommended Event type
            if ($this->event instanceof MonitoringEventInterface) {
                // If the event is a valid type, we can access custom Tags values
                if ($this->event->hasParameter($tagName)) {
                    // Add every metric "tag" parameters, configured in the event metric
                    $tags[$tagName] = $this->event->getParameter($tagName);
                }
            } else {
                // Legacy support
                // Try to get the tag value from a function in the event
                try {
                    $tags[$tagName] = $this->propertyAccessor->getValue($this->event, $tagName);
                } catch (\Exception $e) {
                }
            }
        }

        return $this->formatTagsInline($tags);
    }

    private function formatTagsInline(array $tags): string
    {
        $formatLines = array_map(
            function ($key, $value) {
                return sprintf('%s:%s', $key, $value);
            },
            array_keys($tags),
            $tags
        );
        $inlineTags = implode(',', $formatLines);

        return !empty($inlineTags) ? '|#'.$inlineTags : '';
    }
}
