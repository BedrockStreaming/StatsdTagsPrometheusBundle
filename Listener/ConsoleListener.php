<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\MonitoringConsoleEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ConsoleListener
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher = null;

    /**
     * Time when command started
     *
     * @var float
     */
    protected $startTime = null;

    public function onCommand(ConsoleEvent $event): void
    {
        $this->startTime = microtime(true);
        $this->dispatchConsoleEvent(MonitoringConsoleEvent::COMMAND, $event);
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        // For non-0 exit command, fire an ERROR event
        if ($event->getExitCode() != 0) {
            $this->dispatchConsoleEvent(MonitoringConsoleEvent::ERROR, $event);
        }
        $this->dispatchConsoleEvent(MonitoringConsoleEvent::TERMINATE, $event);
    }

    public function onException(ConsoleEvent $event): void
    {
        $this->dispatchConsoleEvent(MonitoringConsoleEvent::EXCEPTION, $event);
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    protected function dispatchConsoleEvent($eventName, ConsoleEvent $event): void
    {
        if (!is_null($this->eventDispatcher)) {
            $executionTime = !is_null($this->startTime) ? microtime(true) - $this->startTime : null;
            $finaleEvent = new MonitoringConsoleEvent([
                'startTime' => $this->startTime,
                'executionTime' => $executionTime,
                'executionTimeHumanReadable' => ($executionTime * 1000),
                'peakMemory' => self::getPeakMemory(), // @todo: not sure that it's useful
                'underscoredCommandName' => self::getUnderscoredEventCommandName($event),
                // The original event is sent as a parameter, just in case
                'originalEvent' => $event,
            ]);
            $this->eventDispatcher->dispatch($eventName, $finaleEvent);
        }
    }

    private static function getUnderscoredEventCommandName(ConsoleEvent $event): ?string
    {
        if (!is_null($command = $event->getCommand())) {
            return str_replace(':', '_', $command->getName());
        }

        return null;
    }

    private static function getPeakMemory()
    {
        $memory = memory_get_peak_usage(true);
        $memory = ($memory > 1024 ? intval($memory / 1024) : 0);

        return $memory;
    }
}
