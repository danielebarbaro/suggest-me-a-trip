<?php

namespace Library\RoadSurfer\DTO;

use Carbon\Carbon;

class StationDTO
{
    public int $id;
    public CityDTO $city;
    public ?Carbon $activeFrom;
    public string $address;
    public array $dynamicTimeSlots = [];
    public string $name;
    public string $fullName;

    public ?string $returnFrom;
    public ?string $returnTo;
    public ?array $timeSlots = [];
    public string $timezone;
    public ?string $zip;
    public ?string $googleLink;
    public bool $enabled;
    public bool $isPublic;
    public bool $oneWay;
    public ?array $backups = [];
    public array $returns;
    public string $fallback;

    public function __construct(
        int $id,
        string $name,
        CityDTO $city,
        bool $enabled,
        bool $isPublic,
        bool $oneWay,
        ?array $returns = [],
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->city = $city;
        $this->fullName = $this->fullName();
        $this->enabled = $enabled;
        $this->isPublic = $isPublic;
        $this->oneWay = $oneWay;
        $this->returns = $returns;
    }

    public function fullName(): string
    {
        return "{$this->city->name}, {$this->city->countryName}";
    }
}
