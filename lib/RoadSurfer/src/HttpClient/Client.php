<?php

namespace Library\RoadSurfer\HttpClient;

use Carbon\Carbon;
use Exception;
use Library\RoadSurfer\DTO\CityDTO;
use Library\RoadSurfer\DTO\StationDTO;
use Library\RoadSurfer\DTO\TimeFrameDTO;
use Library\RoadSurfer\Exception\APIException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as SymfonyClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Client implements ClientInterface
{
    protected string $baseUrl;
    protected string $lang;

    protected SymfonyClientInterface $client;

    public function __construct(
        SymfonyClientInterface $client,
        string $baseUrl,
        string $lang
    ) {
        $this->client = $client;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->lang = $lang;
    }

    /**
     * @throws APIException
     */
    public function getStations(): array
    {
        $results = [];
        $stations = $this->fetch(
            'translations/stations',
            'station.fetchTranslations',
            null,
            true
        );

        foreach ($stations as $station) {
            $results[$station['id']] = new StationDTO(
                $station['id'],
                $station['translations']['en']['name'],
                new CityDTO(
                    $station['id'],
                    $station['translations']['en']['name'],
                    $station['country_codes'][0],
                    $station['country_translations']['en']['name'],
                    $station['country_translations']['en']['name'],
                ),
                true,
                true,
                false,
            );
        }

        return $results;
    }

    /**
     * @throws APIException
     */
    public function getRallyStations(): array
    {
        $results = [];
        $stations = $this->fetch(
            'rally/stations',
            'rally.startStations'
        );

        foreach ($stations as $station) {
            $results[$station['id']] = new StationDTO(
                $station['id'],
                $station['name'],
                new CityDTO(
                    $station['city']['id'],
                    $station['city']['name'],
                    $station['city']['country'],
                    $station['city']['country_name'],
                    $station['city']['country_translated']
                ),
                $station['enabled'],
                $station['public'],
                $station['one_way'],
                $station['returns'],
            );
        }

        return $results;
    }

    /**
     * @throws APIException
     */
    public function getStationById(?string $resourceId = null): StationDTO
    {
        $station = $this->fetch(
            'rally/stations',
            'rally.fetchRoutes',
            $resourceId
        );

        return new StationDTO(
            $station->id,
            $station->name,
            new CityDTO(
                $station->city['id'],
                $station->city['name'],
                $station->city['country'],
                $station->city['country_name'],
                $station->city['country_translated']
            ),
            $station->enabled,
            $station->public,
            $station->one_way,
            $station->returns,
        );
    }

    /**
     * @throws APIException
     */
    public function getStationTimeFramesByStationIds(?string $resourceId = null): array
    {
        $frames = [];

        $timeFrames = $this->fetch(
            'rally/timeframes',
            'rally.timeframes',
            $resourceId
        );

        foreach ($timeFrames as $timeFrame) {
            $frames[] = new TimeFrameDTO(
                Carbon::parse($timeFrame['startDate']),
                Carbon::parse($timeFrame['endDate'])
            );
        }

        return $frames;
    }

    /**
     * @throws APIException
     */
    public function fetch(
        string $resourcePath,
        string $operationType,
        ?string $resourceId = null,
        ?bool $ignoreLanguage = false
    ): array|object {
        $uri = $this->buildUri($resourcePath, $resourceId, $ignoreLanguage);

        //         dump($uri);

        try {
            $response = $this->client->request('GET', $uri, [
                'headers' => [
                    'X-Requested-Alias' => $operationType,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw APIException::fromStatusCode($response->getStatusCode());
            }

            return $this->parseResponse($response);
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            throw APIException::fromMessage($e->getMessage());
        } catch (DecodingExceptionInterface $e) {
            throw APIException::fromMessage("Decoding error: {$e->getMessage()}");
        } catch (Exception $e) {
            throw APIException::fromMessage("Unknown error: {$e->getMessage()}");
        }
    }

    public function buildUri(
        string $resourcePath,
        ?string $resourceId,
        ?bool $ignoreLanguage
    ): string {
        return implode('/', array_filter([
            $this->baseUrl,
            $ignoreLanguage ? null : $this->lang,
            $resourcePath,
            $resourceId,
        ], fn ($part) => !is_null($part)));
    }

    public function parseResponse(ResponseInterface $response): object|array
    {
        $data = $response->toArray();

        $isAssoc = array_keys($data) !== range(0, count($data) - 1);

        return $isAssoc ? (object) $data : $data;
    }
}
