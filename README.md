[![Built with Devbox](https://www.jetify.com/img/devbox/shield_moon.svg)](https://www.jetify.com/devbox/docs/contributor-quickstart/)

# Suggest a trip to me
This project is a command-line application built with Symfony's console component. 
It allows users to manage and generate smart itineraries and available trips based on geographical data retrieved via the Google Maps API. 
The application processes trips between multiple cities, calculates distances, and allows users to filter or sort the results in various ways.

### Table of contents
* [Features](#features)
* [Installation](#installation)
* [Available Commands](#available-commands)
  * [Usage](#usage)
    * [Options](#options)
    * [Example](#example)
    * [Output](#output)
    * [Example](#example-1)
  * [Usage](#usage-1)
    * [Options](#options-1)
    * [Example](#example-2)
    * [Output](#output-1)
* [Development](#development)
  * [Requirements](#requirements)
    * [devbox](#devbox)
    * [direnv](#direnv)
  * [Getting started](#getting-started)
    * [devbox](#devbox-1)
    * [direnv](#direnv-1)
  * [PHP](#php)
    * [php.ini](#phpini)
    * [php-fpm](#php-fpm)
    * [Extensions](#extensions)
    * [JetBrains IDEs](#jetbrains-ides)

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
#### Example

![](/result.png)

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

## Development

### Requirements

This project uses [devbox](https://www.jetify.com/devbox/docs/) and [direnv](https://direnv.net/) to manage its development environment.

#### devbox
This project uses **devbox** to creates an isolated, reproducible environment with a defined list of required packages installed (details in the `devbox.json` file).

Install **devbox** following the instructions [here](https://www.jetify.com/devbox/docs/installing_devbox/).

#### direnv
Having to manually type `devbox shell` isn’t a huge burden but making **devbox** totally automatic with **direnv** is a convenience.

The installation of **direnv** has two parts:
1. Install the [package](https://direnv.net/docs/installation.html#from-system-packages) or [binary](https://direnv.net/docs/installation.html#from-binary-builds)
1. [Hook](https://direnv.net/docs/hook.html) into your shell

### Getting started

#### devbox

Installs all packages mentioned in the `devbox.json` file:
```bash
devbox install
```

#### direnv

Create the `.envrc` file and add the missing values:
```bash
cp .envrc.sample .envrc
```

Allow **direnv** to work in the project folder:
```bash
direnv allow
```

### PHP
To show more information you can run:
```bash
devbox info php
```

#### php.ini
Use the `devbox.d/php/php.ini` file for PHP configurations.

#### php-fpm

Use `devbox services start|stop php-fpm` to interact with php-fpm service.
Use the `devbox.d/php/php-fpm.conf` file to configure php-fpm service.
See the `.devbox/virtenv/php/php-fpm.log` file for the php-fpm service logs.

#### Extensions
PHP is compiled with default extensions.
If you would like to use non-default extensions you can add them with devbox add php81Extensions.{extension}.
For example, for the memcache extension you can do `devbox add php81Extensions.memcached`.

#### JetBrains IDEs

In **PhpStorm** settings:
* _PHP_: Add a new CLI Interpreter using `<REPOSITORY_FOLDER_PATH>/.devbox/nix/profile/default/bin/php`;
* _PHP > Composer_: Update the Composer executable using `<REPOSITORY_FOLDER_PATH>/.devbox/nix/profile/default/bin/composer`;

! _Replace `<REPOSITORY_FOLDER_PATH>` with the absolute path of your local repository folder._
