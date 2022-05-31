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

    /** @var array */
    private $routesForWhichKernelTerminateEventWontBeDispatched;

    /** @var array */
    private $routesForWhichKernelExceptionEventWontBeDispatched;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function __construct(
        EventDispatcherInterface $dispatcher,
        array $routesForWhichKernelTerminateEventWontBeDispatched,
        array $routesForWhichKernelExceptionEventWontBeDispatched
    ) {
        $this->dispatcher = $dispatcher;
        $this->routesForWhichKernelTerminateEventWontBeDispatched = $routesForWhichKernelTerminateEventWontBeDispatched;
        $this->routesForWhichKernelExceptionEventWontBeDispatched = $routesForWhichKernelExceptionEventWontBeDispatched;
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        if (\in_array($event->getRequest()->attributes->get('_route'), $this->routesForWhichKernelTerminateEventWontBeDispatched, true)) {
            return;
        }

        $this->dispatcher->dispatch(
            KernelTerminateMonitoringEvent::createFromKernelTerminateEvent($event)
        );
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $isMainRequest = method_exists($event, 'isMainRequest') ? $event->isMainRequest() : $event->isMasterRequest();
        if (
            !$isMainRequest
            || \in_array($event->getRequest()->attributes->get('_route'), $this->routesForWhichKernelExceptionEventWontBeDispatched, true)
        ) {
            return;
        }

        $this->dispatcher->dispatch(
            KernelExceptionMonitoringEvent::createFromKernelExceptionEvent($event)
        );
    }
}
