<?php

namespace Library\RoadSurfer\DTO;

final class CityDTO
{
    public int $id;
    public string $name;
    public string $country;
    public string $countryName;
    public string $countryTranslated;

    public function __construct(
        int $id,
        string $name,
        string $country,
        string $countryName,
        string $countryTranslated
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->country = $country;
        $this->countryName = $countryName;
        $this->countryTranslated = $countryTranslated;
    }
}
