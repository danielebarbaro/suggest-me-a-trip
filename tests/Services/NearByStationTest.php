<?php

use App\Services\NearByStationService;
use App\Services\HaversineService;

it('groups nearby cities based on distance', function () {
    $haversineServiceMock = Mockery::mock(HaversineService::class);

    $cities = [
        'Turin' => [45.0703, 7.6869],
        'Milan' => [45.4642, 9.1900],
        'Rome' => [41.9028, 12.4964],
    ];

    $haversineServiceMock->shouldReceive('execute')
        ->with([45.0703, 7.6869], [45.4642, 9.1900]) // Torino - Milano
        ->andReturn(140);

    $haversineServiceMock->shouldReceive('execute')
        ->with([45.0703, 7.6869], [41.9028, 12.4964]) // Torino - Roma
        ->andReturn(530);

    $nearByStationService = new NearByStationService($haversineServiceMock);

    $result = $nearByStationService->execute($cities, 200);

    expect($result)->toBe([
        ['Turin', 'Milan'],
        ['Rome'],
    ]);
});
