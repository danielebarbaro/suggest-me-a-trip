<?php

namespace Library\RoadSurfer\DTO;

use Carbon\Carbon;

class TimeFrameDTO
{
    public Carbon $startDate;
    public Carbon $endDate;

    public function __construct(
        Carbon $startDate,
        Carbon $endDate
    ) {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}
