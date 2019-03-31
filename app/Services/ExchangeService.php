<?php

declare(strict_types=1);

namespace Sputnik\Services;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface as Response;
use Sputnik\Exceptions\ExchangeException;

class ExchangeService
{
    private const ENDPOINT = '/settings';

    /** @var Client */
    private $client;

    /** @var string */
    private $uri;

    /** @var array */
    private $clientOptions;

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
        echo "\nExchangeService::get " . print_r($variables, true);
        $response = $this->client->get(
            $this->uri . self::ENDPOINT . '/' . implode(',', $variables),
            $this->clientOptions
        );

        return $this->parseResult($response);
    }

    public function patch(array $variables)
    {
        echo "\nExchangeService::patch " . print_r($variables, true);
        $response = $this->client->patch($this->uri . self::ENDPOINT, $this->clientOptions + [
            RequestOptions::JSON => $variables
        ]);

        return $this->parseResult($response);
    }

    private function parseResult(Response $response)
    {
        $html = $response->getBody()->getContents();
        $json = json_decode($html);

        echo "\nExchangeService::parseResult " . $html;
        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            throw ExchangeException::json([
                'html' => $html,
            ]);
        }

        return $json;
    }
}