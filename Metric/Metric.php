<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEventInterface;
use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Metric implements MetricInterface
{
    const STATSD_TYPE_COUNTER = 'c';
    const STATSD_TYPE_GAUGE = 'g';
    const STATSD_TYPE_TIMER = 'ms';

    const METRIC_TYPE_COUNTER = 'counter';
    const METRIC_TYPE_GAUGE = 'gauge';
    const METRIC_TYPE_INCREMENT = 'increment';
    const METRIC_TYPE_TIMER = 'timer';

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

    /** @var ExpressionLanguage */
    private $expressionLanguage;

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
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * @throws MetricException
     */
    public function getResolvedName(): string
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

    /**
     * @throws MetricException
     */
    public function getResolvedValue(): string
    {
        if ($this->type === self::METRIC_TYPE_INCREMENT) {
            return 1;
        }
        if (empty($this->paramValue)) {
            //The param value is required for every type, except for increment which is handled above.
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

    public function getResolvedType(): string
    {
        switch ($this->type) {
            case self::METRIC_TYPE_COUNTER:
            case self::METRIC_TYPE_INCREMENT:
                return self::STATSD_TYPE_COUNTER;
            case self::METRIC_TYPE_GAUGE:
                return self::STATSD_TYPE_GAUGE;
            case self::METRIC_TYPE_TIMER:
                return self::STATSD_TYPE_TIMER;
        }

        throw new MetricException('This metric type is not handled');
    }

    public function getResolvedTags(array $resolvers = []): array
    {
        $tags = [];

        // Add global parameters (configured in client or group)
        if (is_array($this->configurationTags)) {
            if ($this->event instanceof KernelEvent && $this->event->isMasterRequest()) {
                $resolvers['request'] = $this->event->getRequest();
            }

            foreach ($this->configurationTags as $tagKey => $tagValue) {
                $tags[$tagKey] = $this->resolveTagValue($tagValue, $resolvers);
            }
        }

        foreach ($this->tags as $tagName => $tagAccessor) {
            // Recommended Event type
            if ($this->event instanceof MonitoringEventInterface) {
                // If the event is a valid type, we can access custom Tags values
                if ($this->event->hasParameter($tagAccessor)) {
                    // Add every metric "tag" parameters, configured in the event metric
                    $tags[$tagName] = $this->event->getParameter($tagAccessor);
                }
            } else {
                // Legacy support
                // Try to get the tag value from a function in the event
                try {
                    if (is_null($tagAccessor)) {
                        // fallback in the case a tag accessor has not been defined
                        //we try to access the value with the tag name
                        $tagAccessor = $tagName;
                    }
                    $tags[$tagName] = $this->propertyAccessor->getValue($this->event, $tagAccessor);
                } catch (\Exception $e) {
                }
            }
        }

        return $tags;
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

    private function resolveTagValue(string $valueToResolve, array $resolvers): string
    {
        if (strpos($valueToResolve, '@=') === 0) {
            // Service resolution
            $resolvedValue = $this->expressionLanguage->evaluate(substr($valueToResolve, 2), $resolvers);
        } else {
            $resolvedValue = $valueToResolve;
        }

        return $resolvedValue;
    }
}
