<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Metric;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringEventInterface;
use M6Web\Bundle\StatsdPrometheusBundle\Exception\MetricException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Metric implements MetricInterface
{
    const STATSD_TYPE_COUNTER = 'c';
    const STATSD_TYPE_GAUGE = 'g';
    const STATSD_TYPE_TIMER = 'ms';

    const METRIC_TYPE_COUNTER = 'counter';
    const METRIC_TYPE_GAUGE = 'gauge';
    const METRIC_TYPE_INCREMENT = 'increment';
    const METRIC_TYPE_TIMER = 'timer';

    const TAG_SERVICE_RESOLUTION = '@=';
    const TAG_PROPERTY_ACCESSOR = '->';
    const TAG_PARAMETER_KEY = '%=';

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var object */
    private $event;

    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var string */
    private $paramValue;

    /** @var array */
    private $configurationTags = [];

    /** @var array */
    private $tags;

    /** @var ExpressionLanguage */
    private $expressionLanguage;

    /**
     * Metric constructor.
     *
     * @param object $event
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
            return (string)1;
        }
        if (empty($this->paramValue)) {
            //The param value is required for every type, except for increment which is handled above.
            throw new MetricException(\sprintf('The configuration of the event metric "%s" must define the "param_value" option.', \get_class($this->event)));
        }
        if ($this->event instanceof MonitoringEventInterface) {
            // Using the valid event type, values are now in parameters
            return $this->correctValue($this->event->getParameter($this->paramValue));
        }
        if (!\method_exists($this->event, $this->paramValue)) {
            // Legacy compatibility
            throw new MetricException(\sprintf('The event class "%s" must have a "%s" method or parameters in order to measure value.', \get_class($this->event), $this->paramValue));
        }

        return $this->correctValue(\call_user_func([$this->event, $this->paramValue]));
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
        $resolvedTags = [];

        // Add global parameters (configured in client or group)
        foreach (array_merge($this->configurationTags, $this->tags) as $tagName => $tagValue) {
            $resolvedTag = $this->resolveTagValue(
            // By default (~), we look for the parameter with the same name as the tag.
                !is_null($tagValue) ? $tagValue : self::TAG_PARAMETER_KEY.$tagName,
                $resolvers
            );

            if (!empty($resolvedTag)) {
                $resolvedTags[$tagName] = $resolvedTag;
            }
        }

        return $resolvedTags;
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

    private function resolveTagValue(string $valueToResolve, array $resolvers): ?string
    {
        switch (true) {
            case strpos($valueToResolve, self::TAG_SERVICE_RESOLUTION) === 0:
                return $this->expressionLanguage->evaluate(substr($valueToResolve, strlen(self::TAG_SERVICE_RESOLUTION)), $resolvers);
            case strpos($valueToResolve, self::TAG_PROPERTY_ACCESSOR) === 0:
                try {
                    return $this->propertyAccessor->getValue($this->event, substr($valueToResolve, strlen(self::TAG_PROPERTY_ACCESSOR)));
                } catch (\Exception $e) {
                    return null;
                }
            case strpos($valueToResolve, self::TAG_PARAMETER_KEY) === 0:
                $parameter = substr($valueToResolve, strlen(self::TAG_PARAMETER_KEY));
                if (!$this->event instanceof MonitoringEventInterface) {
                    return null;
                }
                if (!$this->event->hasParameter($parameter)) {
                    return null;
                }

                return $this->event->getParameter($parameter);
            default:
                return $valueToResolve;
        }
    }

    private function correctValue($value): string
    {
        if (!is_numeric($value)) {
            $value = 0;
        }

        /* @see https://github.com/prometheus/statsd_exporter/pull/178/files#diff-557eb2a359922e8de5f18397fed0cd99R423 */
        if ($this->getResolvedType() === self::STATSD_TYPE_TIMER) {
            $value = $value * 1000;
        }

        return (string)$value;
    }
}
