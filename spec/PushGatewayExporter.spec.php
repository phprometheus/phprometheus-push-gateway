<?php

declare(strict_types=1);

use Kahlan\Arg;
use Kahlan\Plugin\Double;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Phprometheus\PushGateway\PushGatewayExporter;
use PrometheusPushGateway\PushGateway;

describe(PushGatewayExporter::class, function () {
    given('client', function () {
        return Double::instance([
            'implements' => ClientInterface::class,
        ]);
    });

    it('pushes to a Prometheus Push Gateway rather than exposing a metrics endpoint', function () {
        allow($this->client)
            ->toReceive('request')
            ->andReturn(new Response());
        expect($this->client)
            ->toReceive('request');

        $prometheus = new PushGatewayExporter('ns', 'job', new PushGateway('127.0.0.1', $this->client));

        $prometheus->flush();
    });

    it('gracefully handles a failing request', function () {
        allow($this->client)
            ->toReceive('request')
            ->andReturn(new Response(502));
        expect($this->client)
            ->toReceive('request');

        $prometheus = new PushGatewayExporter('ns', 'job', new PushGateway('127.0.0.1', $this->client));

        $prometheus->flush();
    });

    it('gracefully handles a failing connection', function () {
        allow($this->client)
            ->toReceive('request')
            ->andRun(function () {
                throw new ClientException('oops', new Request('POST', '/prometheus'), new Response());
            });
        expect($this->client)
            ->toReceive('request');

        $prometheus = new PushGatewayExporter('ns', 'job', new PushGateway('127.0.0.1', $this->client));

        $prometheus->flush();
    });
});
