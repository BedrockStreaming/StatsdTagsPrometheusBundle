<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Client;

class UdpClient implements ClientInterface
{
    /** @var int max safe size in bytes of one message to send (max official size is 65507) */
    const MAX_MESSAGE_SIZE = 64000;

    /** @var ServerInterface */
    protected $server;

    public function __construct(ServerInterface $server)
    {
        $this->server = $server;
    }

    /**
     * Split metrics to send them group by group
     *
     * @param array $lines
     */
    public function sendLines(array $lines): void
    {
        $parts = array_chunk($lines, 30);
        foreach ($parts as $linesToSend) {
            $this->writeLines($linesToSend);
        }
    }

    protected function writeLines(array $lines): bool
    {
        if ($resource = @fsockopen($this->server->getAddress(), $this->server->getPort())) {
            foreach ($lines as $line) {
                if (strlen($line) >= self::MAX_MESSAGE_SIZE) {
                    // Ignore too big lines
                    continue;
                }

                if (!@fwrite($resource, $line)) {
                    return false;
                }
            }

            if (fclose($resource)) {
                return true;
            }
        }

        return false;
    }
}
