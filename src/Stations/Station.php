<?php

namespace App\Stations;

class Station
{
    public string $id;
    public string $name;
    public string $fullName;
    public string $country;

    public array $coordinates;

    public function __construct(string $id, string $name, string $fullName, string $country, array $coordinates)
    {
        $this->id = $id;
        $this->name = $name;
        $this->fullName = $fullName;
        $this->country = $country;
        $this->coordinates = $coordinates;
    }
}
