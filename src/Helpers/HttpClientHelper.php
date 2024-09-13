<?php

namespace App\Helpers;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HttpClientHelper
{

    private HttpClientInterface $client;
    private string $baseUrl;
    private string $lang;

    public function __construct()
    {
        $this->client = HttpClient::create();
        $this->baseUrl = $_ENV['API_BASE_URL'];
        $this->lang = $_ENV['LANG'];
    }

    public function fetchFromApi(
        string $path,
        string $requestAlias,
        ?string $id = null,
        ?bool $disableLang = false
    ): array
    {
        $uri = implode('/', array_filter([
            $this->baseUrl,
            $disableLang ? null : $this->lang,
            $path,
            $id
        ], fn($part) => !is_null($part)));

        try {
            $response = $this->client->request('GET', $uri, [
                'headers' => [
                    'X-Requested-Alias' => $requestAlias,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('API Fail, status code: '.$response->getStatusCode());
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new \Exception('API error: '.$e->getMessage());
        }
    }
}
