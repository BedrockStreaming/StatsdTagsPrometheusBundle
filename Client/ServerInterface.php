<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Client;

use M6Web\Bundle\StatsdPrometheusBundle\Exception\ServerException;

interface ServerInterface
{
    /**
     * @throws ServerException
     */
    public function __construct(string $serverName, array $serverConfig);

    public function getAddress(): string;

    public function getPort(): int;
}
