<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\KernelMonitoringEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventsListener implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onKernelTerminate(PostResponseEvent $event): void
    {
        $this->dispatcher->dispatch(
            KernelMonitoringEvent::TERMINATE,
            KernelMonitoringEvent::createFromKernelTerminateEvent($event)
        );
    }

    public function onKernelException(GetResponseForExceptionEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->dispatcher->dispatch(
                KernelMonitoringEvent::EXCEPTION,
                KernelMonitoringEvent::createFromKernelExceptionEvent($event)
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }
}
