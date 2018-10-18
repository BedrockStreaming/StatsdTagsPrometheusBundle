<?php

declare(strict_types=1);

namespace M6Web\Bundle\StatsdPrometheusBundle\Listener;

use M6Web\Bundle\StatsdPrometheusBundle\Event\KernelExceptionEvent;
use M6Web\Bundle\StatsdPrometheusBundle\Event\KernelTerminateEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class KernelEventsListener implements EventSubscriberInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        $this->dispatcher->dispatch('statsdtagsprometheus.kernelterminate',
            new KernelTerminateEvent(
                $event->getKernel(),
                $event->getRequest(),
                $event->getResponse()
            ));
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (method_exists($event->getException(), 'getStatusCode')) {
            $this->dispatcher->dispatch(
                'statsdtagsprometheus.kernelexception',
                new KernelExceptionEvent($event->getException()->getStatusCode())
            );
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }
}
