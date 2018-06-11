<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event;

class MonitoringConsoleEvent extends MonitoringEvent
{
    const COMMAND = 'm6web.console.command';
    const TERMINATE = 'm6web.console.terminate';
    const ERROR = 'm6web.console.error';
    const EXCEPTION = 'm6web.console.exception';
}
