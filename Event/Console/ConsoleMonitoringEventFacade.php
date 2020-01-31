<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Event\Console;

use Symfony\Component\Console\Event\ConsoleEvent;

class ConsoleMonitoringEventFacade
{
    /**
     * @var int
     */
    protected $startTime;
    /**
     * @var int
     */
    protected $executionTime;
    /**
     * @var int
     */
    protected $memoryPeakInBytes;
    /**
     * @var string
     */
    protected $commandName;

    public function __construct(
        int $startTime,
        int $executionTime,
        int $memoryPeakInBytes,
        string $commandName
    ) {
        $this->startTime = $startTime;
        $this->executionTime = $executionTime;
        $this->memoryPeakInBytes = $memoryPeakInBytes;
        $this->commandName = $commandName;
    }

    public static function fromEvent(ConsoleEvent $event, int $startTime): ConsoleMonitoringEventFacade
    {
        return new self(
            $startTime,
            $startTime !== null ? microtime(true) - $startTime : null,
            self::getPeakMemoryInBytes(),
            self::getUnderscoredEventCommandName($event)
        );
    }

    public function toMonitoringArray(): array
    {
        return [
            'startTime' => $this->getStartTime(),
            'executionTime' => $this->getExecutionTime(),
            'executionTimeHumanReadable' => ($this->getExecutionTime() * 1000),
            'peakMemory' => $this->getMemoryPeakInBytes(),
            'underscoredCommandName' => $this->getCommandName(),
        ];
    }

    protected static function getUnderscoredEventCommandName(ConsoleEvent $event): ?string
    {
        if (($command = $event->getCommand()) !== null) {
            return str_replace(':', '_', $command->getName());
        }

        return null;
    }

    protected static function getPeakMemoryInBytes(): int
    {
        return memory_get_peak_usage(true);
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getExecutionTime(): int
    {
        return $this->executionTime;
    }

    public function getMemoryPeakInBytes(): int
    {
        return $this->memoryPeakInBytes;
    }

    public function getCommandName(): string
    {
        return $this->commandName;
    }
}
