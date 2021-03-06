<?php

declare(strict_types=1);

namespace Sputnik\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface as Response;
use Sputnik\Exceptions\RequestException;

class ExchangeService
{
    private const ENDPOINT = '/settings';

    private Client $client;
    private string $uri;
    private array $clientOptions;

    public function __construct(Client $client, string $uri, float $timeout)
    {
        $this->client = $client;
        $this->uri = $uri;
        $this->clientOptions = [
            'timeout' => $timeout,
        ];
    }

    public function get(array $variables)
    {
        Log::info('ExchangeService::get', $variables);

        $response = $this->request(
            Request::METHOD_GET,
            $this->uri . self::ENDPOINT . '/' . implode(',', $variables),
            $this->clientOptions
        );

        return $this->parseResult($response);
    }

    public function patch(array $variables)
    {
        Log::info('ExchangeService::patch', $variables);

        $response = $this->request(Request::METHOD_PATCH, $this->uri . self::ENDPOINT, $this->clientOptions + [
            RequestOptions::JSON => $variables
        ]);

        return $this->parseResult($response);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     *
     * @return mixed|Response
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function request(string $method, string $url, array $options = [])
    {
        try {
            return $this->client->request(
                $method,
                $url,
                $options
            );
        } catch (ConnectException $exception) {
            throw RequestException::timeout(['method' => $method, 'url' => $url, 'options' => $options]);
        }
    }

    private function parseResult(Response $response)
    {
        $html = $response->getBody()->getContents();
        $json = json_decode($html);
        $isJsonDecode = $json === null && json_last_error() !== JSON_ERROR_NONE;

        Log::info('ExchangeService::parseResult', ['html' => $html]);

        if ($isJsonDecode) {
            throw RequestException::json([
                'html' => $html,
            ]);
        }

        return $json;
    }
}
