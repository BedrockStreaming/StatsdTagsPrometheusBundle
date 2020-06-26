<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Fixtures;

use Symfony\Contracts\EventDispatcher\Event;

class CustomEventTest extends Event
{
    /** @var float|null */
    private $value;

    /** @var string|null */
    private $placeHolder1;

    /** @var string|null */
    private $placeHolder2;

    public function __construct(?float $value, string $placeHolder1 = null, string $placeHolder2 = null)
    {
        $this->value = $value;
        $this->placeHolder1 = $placeHolder1;
        $this->placeHolder2 = $placeHolder2;
    }

    public function getValue(): ?float
    {
        return $this->value;
    }

    public function getPlaceHolder1(): ?string
    {
        return $this->placeHolder1;
    }

    public function getPlaceHolder2(): ?string
    {
        return $this->placeHolder2;
    }
}
