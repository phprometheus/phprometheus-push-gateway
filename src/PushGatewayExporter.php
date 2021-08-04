<?php

declare(strict_types=1);

namespace SiteService\Infrastructure\Prometheus;

use Psr\Log\LoggerInterface;
use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use PrometheusPushGateway\PushGateway;
use GuzzleHttp\Exception\GuzzleException;

class PushGatewayExporter extends AbstractPrometheus implements Prometheus
{
    private $job;
    private $pushGateway;
    private $logger;

    public function __construct(
        string $namespace,
        string $job,
        PushGateway $pushGateway,
        ?LoggerInterface $logger = null
    ) {
        $this->job = $job;
        $this->pushGateway = $pushGateway;
        $this->logger = $logger;

        parent::__construct($namespace, new CollectorRegistry(new InMemory()));
    }

    public function flush(): array
    {
        try {
            $this->pushGateway->push(
                $this->collectorRegistry,
                $this->job,
            );
        } catch (GuzzleException $e) {
            if (! is_null($this->logger)) {
                $this->logger->error('Failed to push metrics to PushGateway', [
                    'reason' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            }
        }

        return [];
    }
}
