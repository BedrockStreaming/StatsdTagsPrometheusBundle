<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event\Console;

use M6Web\Bundle\StatsdPrometheusBundle\Event\AbstractMonitoringEvent;
use Symfony\Component\Console\Event\ConsoleEvent;

abstract class AbstractConsoleAbstractMonitoringEvent extends AbstractMonitoringEvent
{
    public static function createFromConsoleEvent(ConsoleEvent $event, int $startTime = 0)
    {
        $executionTime = !is_null($startTime) ? microtime(true) - $startTime : null;

        return new static([
            'startTime' => $startTime,
            'executionTime' => $executionTime,
            'executionTimeHumanReadable' => ($executionTime * 1000),
            'peakMemory' => self::getPeakMemoryInBytes(),
            'underscoredCommandName' => self::getUnderscoredEventCommandName($event),
            // The original event is sent as a parameter, just in case
            'originalEvent' => $event,
        ]);
    }

    protected static function getUnderscoredEventCommandName(ConsoleEvent $event): ?string
    {
        if (!is_null($command = $event->getCommand())) {
            return str_replace(':', '_', $command->getName());
        }

        return null;
    }

    protected static function getPeakMemoryInBytes()
    {
        return memory_get_peak_usage(true);
    }
}
