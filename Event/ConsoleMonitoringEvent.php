<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

use Symfony\Component\Console\Event\ConsoleEvent;

class ConsoleMonitoringEvent extends MonitoringEvent
{
    const COMMAND = 'statsdprometheus.console.command';
    const TERMINATE = 'statsdprometheus.console.terminate';
    const ERROR = 'statsdprometheus.console.error';
    const EXCEPTION = 'statsdprometheus.console.exception';

    public static function createFromConsoleEvent(ConsoleEvent $event, int $startTime = 0)
    {
        $executionTime = !is_null($startTime) ? microtime(true) - $startTime : null;

        return new self([
            'startTime' => $startTime,
            'executionTime' => $executionTime,
            'executionTimeHumanReadable' => ($executionTime * 1000),
            'peakMemory' => self::getPeakMemory(),
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

    protected static function getPeakMemory()
    {
        $memory = memory_get_peak_usage(true);
        $memory = ($memory > 1024 ? intval($memory / 1024) : 0);

        return $memory;
    }
}
