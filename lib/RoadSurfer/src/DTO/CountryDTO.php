<?php

namespace Library\RoadSurfer\DTO;

class CountryDTO
{
    public int $id;
    public array $translations;
    public array $countryTranslations;
    public array $countryCodes;

    public function __construct(
        int $id,
        array $translations,
        array $countryTranslations,
        array $countryCodes
    ) {
        $this->id = $id;
        $this->translations = $translations;
        $this->countryTranslations = $countryTranslations;
        $this->countryCodes = $countryCodes;
    }
}
