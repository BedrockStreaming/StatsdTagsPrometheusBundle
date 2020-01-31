<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\Console\ConsoleCommandMonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Console\ConsoleExceptionMonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Console\ConsoleMonitoringEventFacade;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Console\ConsoleTerminateMonitoringEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventsSubscriber implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher = null;

    /**
     * Time when command started
     *
     * @var float
     */
    protected $startTime = null;

    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::TERMINATE => 'onTerminate',
            ConsoleEvents::ERROR => 'onException',
        ];
    }

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public function onCommand(ConsoleEvent $event): void
    {
        $this->startTime = microtime(true);
        $this->eventDispatcher->dispatch(
            //ConsoleCommandMonitoringEvent::createFromConsoleEvent($event, $this->startTime)
            ConsoleCommandMonitoringEvent::fromFacade(
                ConsoleMonitoringEventFacade::fromEvent($event, $this->startTime)
            )
        );
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        if ($event->getExitCode() != 0) {
            // For non-0 exit command, fire an ERROR event
            $this->eventDispatcher->dispatch(
                ConsoleCommandMonitoringEvent::fromFacade(
                    ConsoleMonitoringEventFacade::fromEvent($event, $this->startTime)
                )
            );
        }
        $this->eventDispatcher->dispatch(
              ConsoleTerminateMonitoringEvent::fromFacade(
                  ConsoleMonitoringEventFacade::fromEvent($event, $this->startTime)
              )
        );
    }

    public function onException(ConsoleEvent $event): void
    {
        $this->eventDispatcher->dispatch(
            ConsoleExceptionMonitoringEvent::fromFacade(
                  ConsoleMonitoringEventFacade::fromEvent($event, $this->startTime)
              )
        );
    }
}
