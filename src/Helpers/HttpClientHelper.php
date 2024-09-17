<?php

namespace App\Helpers;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class HttpClientHelper
{
    public const int DELAY_MS = 100;
    public const int RETRIES = 3;
    private HttpClientInterface $client;
    private string $baseUrl;
    private string $lang;

    public function __construct()
    {
        $this->client = HttpClient::create();
        $this->baseUrl = $_ENV['API_BASE_URL'];
        $this->lang = $_ENV['LANG'];
    }

    /**
     * @throws DecodingExceptionInterface
     * @throws Exception
     */
    public function fetchFromApi(
        string $path,
        string $requestAlias,
        ?string $id = null,
        ?bool $disableLang = false
    ): array {
        $uri = implode('/', array_filter([
            $this->baseUrl,
            $disableLang ? null : $this->lang,
            $path,
            $id,
        ], fn ($part) => !is_null($part)));

        $retryClient = new RetryableHttpClient(
            $this->client,
            new GenericRetryStrategy([429], mt_rand(4, self::DELAY_MS)),
            self::RETRIES
        );

        try {
            $response = $retryClient->request('GET', $uri, [
                'headers' => [
                    'X-Requested-Alias' => $requestAlias,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new Exception('API Fail, status code: '.$response->getStatusCode());
            }

            return $response->toArray();
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw new Exception('API error: '.$e->getMessage());
        }
    }
}
