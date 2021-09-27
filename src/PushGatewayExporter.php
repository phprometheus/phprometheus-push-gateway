<?php

declare(strict_types=1);

namespace Phprometheus\PushGateway;

use RuntimeException;
use Psr\Log\LoggerInterface;
use Phprometheus\Prometheus;
use Prometheus\Storage\InMemory;
use Prometheus\CollectorRegistry;
use Phprometheus\AbstractPrometheus;
use PrometheusPushGateway\PushGateway;
use GuzzleHttp\Exception\GuzzleException;

class PushGatewayExporter extends AbstractPrometheus implements Prometheus
{
    /**
     * @var string
     */
    private $job;

    /**
     * @var PushGateway
     */
    private $pushGateway;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @var CollectorRegistry
     */
    protected $registry;

    public function __construct(
        string $namespace,
        string $job,
        PushGateway $pushGateway,
        ?LoggerInterface $logger = null
    ) {
        $this->job = $job;
        $this->pushGateway = $pushGateway;
        $this->logger = $logger;

        parent::__construct($namespace, new CollectorRegistry(new InMemory(), false));
    }

    public function flush(): array
    {
        try {
            $this->pushGateway->push(
                $this->registry,
                $this->job,
            );
        } catch (RuntimeException $e) {
            if (! is_null($this->logger)) {
                $this->logger->error('Failed to push metrics to PushGateway', [
                    'reason' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
            }
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
