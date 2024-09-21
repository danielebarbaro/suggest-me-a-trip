# Suggest a trip to me
This project is a command-line application built with Symfony's console component. 
It allows users to manage and generate smart itineraries and available trips based on geographical data retrieved via the Google Maps API. 
The application processes trips between multiple cities, calculates distances, and allows users to filter or sort the results in various ways.

## Features
 - **Available Itineraries**: Generate and list smart itineraries with customizable steps (number of stations) and sort them by distance.
 - **Available Trips**: List all available trips, with an option to filter by specific countries.
 - **Distance Calculation**: Utilizes the Haversine formula to calculate the real-world distances between stations.

## Installation
1. Clone repo
```bash
git clone
```

2. Install dependencies using Composer:
```bash
composer install
```

3. Set up the .env file by copying .env.example to .env and adding your Google Maps API key:
```bash
cp .env.example .env
```

4. env
```bash
GEOCODE_PROVIDER_API_KEY=your_api_key_here
```

## Available Commands

1. `available-itineraries`
   
This command lists available itineraries generated from trips in multiple steps, allowing you to specify the number of steps and sort the results by distance.

### Usage
```bash
php console available-itineraries [options]
```

#### Options

`php console available-itineraries [options]`
 - `--min-steps` (-s): Specify the minimum number of steps (stations) for the itinerary. Default is 3.
 - `--order` (-o): Order the results by distance in ascending (asc) or descending (desc) order.
 - `--check-time-frame` (-c): By default, the command will check the time frame of the trip. To disable this, use the `--check-time-frame=off` option.
 - `--visit-country-just-once` (-sc): By default, the command will **allow the same country** to be visited more than once. 
To disable this, and visit just one country for each itinerary use the `--visit-country-just-once=off` option.

#### Example
```bash
php console available-itineraries --steps=2 --order=asc
```

This will find itineraries with 2 steps (stations) and order them by distance in ascending order. If an itinerary contains "Italy", the station will be highlighted in yellow.

#### Output
The command outputs a list of itineraries in the format:

```bash
#10. Total distance: 1237 km
	Graz, Austria -> Munich, Germany | 2024-10-22 2024-10-29 | [625.79 Km]
	Munich, Germany -> Venice, Italy | 2024-10-19 2024-10-26 | [611.45 Km]

...	
```

If no itineraries are found, it displays an error message.


2 . `available-trips`
   This command lists all available trips, with the option to filter trips by a specific country.

### Usage
```bash
php console available-trips [options]
```

#### Options
 - `--filter-by-country` (-f): Filter trips by specifying a country.

#### Example
```bash
php console available-trips --filter-by-country=germany
```

This will display only the trips that involve destinations in Germany.

#### Output
The command outputs each trip's starting station and its drop-off stations, highlighting the country of origin.

```bash
1: Aartselaar, Belgium > Frankfurt, Germany
2: Aartselaar, Belgium > Leipzig, Germany
3: Aartselaar, Belgium > Marburg, Germany
4: Aartselaar, Belgium > Mainz, Germany
...
```
