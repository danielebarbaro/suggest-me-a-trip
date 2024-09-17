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
GOOGLE_MAPS_API_KEY=your_api_key_here
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
 - `--steps` (-s): Specify the number of steps (stations) for the itinerary. Default is 3.
 - `--order` (-o): Order the results by distance in ascending (asc) or descending (desc) order.

#### Example
```bash
php console available-itineraries --steps=4 --order=asc
```

This will find itineraries with 4 steps (stations) and order them by distance in ascending order. If an itinerary contains "Italy", the station will be highlighted in yellow.

#### Output
The command outputs a list of itineraries in the format:

```bash
1. Station1 -> Station2 -> Station3 - [distance in km]
2. Station4 -> Station5 -> Station6 - [distance in km]
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
1: Pickup Station1
====================================
Dropoff Station1
Dropoff Station2
```
