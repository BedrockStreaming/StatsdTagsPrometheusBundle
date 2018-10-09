<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Tests\Client;

use M6Web\Bundle\StatsdPrometheusBundle\Client\Server;
use M6Web\Bundle\StatsdPrometheusBundle\Exception\ServerException;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testGetAddressReturnsAddressWhenValidServerConfigIsGiven()
    {
        // -- Given --
        $serverName = 'default';
        $serverConfig = [
            'address' => 'udp://address',
            'port' => 3302,
        ];
        $expected = 'udp://address';
        // -- When --
        $server = new Server($serverName, $serverConfig);
        // -- Then --
        $this->assertEquals($expected, $server->getAddress());
    }

    public function testGetPortReturnsPortWhenValidServerConfigIsGiven()
    {
        // -- Given --
        $serverName = 'default';
        $serverConfig = [
            'address' => 'udp://address',
            'port' => 3302,
        ];
        $expected = 3302;
        // -- When --
        $server = new Server($serverName, $serverConfig);
        // -- Then --
        $this->assertEquals($expected, $server->getPort());
    }

    public function testConstructThrowExceptionWhenBadServerConfigIsGiven()
    {
        // -- Expects --
        $this->expectException(ServerException::class);
        // -- When --
        new Server('default', [
            'address' => 'http://address',
            'port' => 3020,
        ]);
    }
}
