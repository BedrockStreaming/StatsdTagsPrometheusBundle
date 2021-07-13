<?php

namespace M6Web\Bundle\StatsdPrometheusBundle\Client;

class UdpClient implements ClientInterface
{
    /** @var int max safe size in bytes of one message to send (max official size is 65507) */
    const MAX_MESSAGE_SIZE = 64000;

    /** @var ServerInterface */
    protected $server;

    /** @var bool */
    protected $debugEnabled;

    public function __construct(ServerInterface $server, ?bool $debugEnabled = false)
    {
        $this->server = $server;
        $this->debugEnabled = $debugEnabled;
    }

    /**
     * Split metrics to send them group by group
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

                if (!@fwrite($resource, $this->getFormattedLine($line))) {
                    return false;
                }
            }

            if (fclose($resource)) {
                return true;
            }
        }

        return false;
    }

    protected function getFormattedLine($line)
    {
        if ($this->debugEnabled) {
            //With debug mode on, we add a carriage return to provide more readable data
            return $line."\n";
        }

        return $line;
    }
}
