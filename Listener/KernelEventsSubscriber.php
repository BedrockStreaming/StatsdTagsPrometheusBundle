<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel\KernelExceptionMonitoringEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Event\Kernel\KernelTerminateMonitoringEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventsSubscriber implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->dispatcher->dispatch(
            KernelTerminateMonitoringEvent::createFromKernelTerminateEvent($event)
        );
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if ($event->isMasterRequest()) {
            $this->dispatcher->dispatch(
                KernelExceptionMonitoringEvent::createFromKernelExceptionEvent($event)
            );
        }
    }
}
