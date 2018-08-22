<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Client;

interface ClientInterface
{
    /**
     * Define a valid server configuration at instantiation
     */
    public function __construct(ServerInterface $server);

    /**
     * Send metrics data to the configured server
     *
     * @param array $lines array of string lines to send
     */
    public function sendLines(array $lines): void;
}
