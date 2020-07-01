<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event\Console;

use M6Web\Bundle\StatsdPrometheusBundle\Event\AbstractMonitoringEvent;

class ConsoleErrorMonitoringEvent extends AbstractMonitoringEvent
{
    public static function fromFacade(ConsoleMonitoringEventFacade $facade): ConsoleErrorMonitoringEvent
    {
        return new self($facade->toMonitoringArray());
    }
}
