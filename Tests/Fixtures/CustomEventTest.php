<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Fixtures;

use Symfony\Contracts\EventDispatcher\Event;

class CustomEventTest extends Event
{
    private $value;
    private $placeHolder1;
    private $placeHolder2;

    public function __construct($value, $placeHolder1 = null, $placeHolder2 = null)
    {
        $this->value = $value;
        $this->placeHolder1 = $placeHolder1;
        $this->placeHolder2 = $placeHolder2;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return mixed
     */
    public function getPlaceHolder1()
    {
        return $this->placeHolder1;
    }

    /**
     * @return mixed
     */
    public function getPlaceHolder2()
    {
        return $this->placeHolder2;
    }
}
