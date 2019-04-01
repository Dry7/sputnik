<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Mockery\Mock;
use Sputnik\Services\ExchangeService;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Mockery;

class ExchangeServiceTest extends TestCase
{
    public function testGet()
    {
        // arrange
        $data = (object)[
            'orientationZenithAngleDeg' => (object)['set' => 185, 'value' => 185],
            'orientationAzimuthAngleDeg' => (object)['set' => 5, 'value' => 5],
        ];
        $json = '{"orientationZenithAngleDeg":{"set":185,"value":185},"orientationAzimuthAngleDeg":{"set":5,"value":5}}';

        /** @var Client|Mock $client */
        $client = Mockery::mock(Client::class)
            ->shouldReceive('request')
            ->with(Request::METHOD_GET, 'http://localhost/settings/orientationZenithAngleDeg,orientationAzimuthAngleDeg', ['timeout' => 0.1])
            ->andReturn(new Response(HttpResponse::HTTP_OK, [], $json))
            ->once()
            ->getMock();

        $service = new ExchangeService($client, 'http://localhost', 0.1);

        // act
        $result = $service->get(['orientationZenithAngleDeg', 'orientationAzimuthAngleDeg']);

        // assert
        self::assertEquals($data, $result);
    }

    /**
     * @expectedException Sputnik\Exceptions\RequestException
     */
    public function testGetJsonException()
    {
        // arrange
        $json = '{2"orientationZenithAngleDeg":{"set":185,"value":185},"orientationAzimuthAngleDeg":{"set":5,"value":5}}';

        /** @var Client|Mock $client */
        $client = Mockery::mock(Client::class)
            ->shouldReceive('request')
            ->with(Request::METHOD_GET, 'http://localhost/settings/orientationZenithAngleDeg,orientationAzimuthAngleDeg', ['timeout' => 0.1])
            ->andReturn(new Response(HttpResponse::HTTP_OK, [], $json))
            ->once()
            ->getMock();

        $service = new ExchangeService($client, 'http://localhost', 0.1);

        // act
        $service->get(['orientationZenithAngleDeg', 'orientationAzimuthAngleDeg']);
    }

    /**
     * @expectedException Sputnik\Exceptions\RequestException
     */
    public function testGetConnectException()
    {
        // arrange
        /** @var Client|Mock $client */
        $client = Mockery::mock(Client::class)
            ->shouldReceive('request')
            ->with(Request::METHOD_GET, 'http://localhost/settings/orientationZenithAngleDeg,orientationAzimuthAngleDeg', ['timeout' => 0.1])
            ->andThrow(new ConnectException('Timeout', new \GuzzleHttp\Psr7\Request('GET', 'https://ya.ru')))
            ->once()
            ->getMock();

        $service = new ExchangeService($client, 'http://localhost', 0.1);

        // act
        $service->get(['orientationZenithAngleDeg', 'orientationAzimuthAngleDeg']);
    }

    public function testPatch()
    {
        // arrange
        $data = (object)[
            'orientationZenithAngleDeg' => (object)['set' => 20, 'value' => 20],
            'orientationAzimuthAngleDeg' => (object)['set' => 30, 'value' => 30],
        ];
        $json = '{"orientationZenithAngleDeg":{"set":20,"value":20},"orientationAzimuthAngleDeg":{"set":30,"value":30}}';

        /** @var Client|Mock $client */
        $client = Mockery::mock(Client::class)
            ->shouldReceive('request')
            ->with(Request::METHOD_PATCH, 'http://localhost/settings', [
                'timeout' => 0.2,
                RequestOptions::JSON => [
                    'orientationZenithAngleDeg' => 20,
                    'orientationAzimuthAngleDeg' => 30,
                ],
            ])
            ->andReturn(new Response(HttpResponse::HTTP_OK, [], $json))
            ->once()
            ->getMock();

        $service = new ExchangeService($client, 'http://localhost', 0.2);

        // act
        $result = $service->patch([
            'orientationZenithAngleDeg' => 20,
            'orientationAzimuthAngleDeg' => 30,
        ]);

        // assert
        self::assertEquals($data, $result);
    }
}
