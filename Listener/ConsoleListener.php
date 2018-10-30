<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\ConsoleMonitoringEvent;
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
        $this->eventDispatcher->dispatch(
            ConsoleMonitoringEvent::COMMAND,
            ConsoleMonitoringEvent::createFromConsoleEvent($event, $this->startTime)
        );
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        if ($event->getExitCode() != 0) {
            // For non-0 exit command, fire an ERROR event
            $this->eventDispatcher->dispatch(
                ConsoleMonitoringEvent::ERROR,
                ConsoleMonitoringEvent::createFromConsoleEvent($event, $this->startTime)
            );
        }
        $this->eventDispatcher->dispatch(
            ConsoleMonitoringEvent::TERMINATE,
            ConsoleMonitoringEvent::createFromConsoleEvent($event, $this->startTime)
        );
    }

    public function onException(ConsoleEvent $event): void
    {
        $this->eventDispatcher->dispatch(
            ConsoleMonitoringEvent::EXCEPTION,
            ConsoleMonitoringEvent::createFromConsoleEvent($event, $this->startTime)
        );
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }
}
