<?php

use App\Services\HaversineService;

it('calculates the correct distance between Turin and Milan', function () {
    $turin = [45.0703, 7.6869];
    $milan = [45.4642, 9.1900];

    $calculator = new HaversineService();

    $distance = $calculator->execute($turin, $milan);

    expect($distance)->toBeBetween(125.50, 125.52);
});
