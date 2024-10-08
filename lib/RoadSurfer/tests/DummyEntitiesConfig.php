<?php

$rallyStations = json_decode(
    '[{
    "city": {
        "id": 793,
        "name": "Turin",
        "country": "IT",
        "country_name": "Italy",
        "country_translated": "Italia"
    },
    "id": 58,
    "active_from": "2022-04-29",
    "address": "Via Giulio Natta, 6",
    "name": "Torino",
    "return_from": "09:00:00",
    "return_to": "11:00:00",
    "timezone": "Europe/Berlin",
    "zip": "10148 Torino",
    "google_link": "https://goo.gl/maps/yuXKJaiHwK8HvG2Q8",
    "enabled": true,
    "public": true,
    "one_way": true,
    "backups": [],
    "returns": [],
    "fallback": null
}]',
    true
);
$stations = json_decode(
    '[{
    "id": 58,
    "translations": {
      "de": {
        "name": "Turin"
      },
      "en": {
        "name": "Turin"
      },
      "es": {
        "name": "Turín"
      },
      "fr": {
        "name": "Turin"
      },
      "it": {
        "name": "Torino"
      }
    },
    "country_translations": {
      "de": {
        "name": "Italien"
      },
      "en": {
        "name": "Italy"
      },
      "es": {
        "name": "Italia"
      },
      "fr": {
        "name": "Italie"
      },
      "it": {
        "name": "Italia"
      }
    },
    "country_codes": [
      "IT",
      "ITA"
    ]
  }]',
    true
);
$station = json_decode(
    '{
    "city": {
        "id": 793,
        "name": "Turin",
        "country": "IT",
        "country_name": "Italy",
        "country_translated": "Italia"
    },
    "id": 58,
    "active_from": "2022-04-29",
    "address": "Via Giulio Natta, 6",
    "dynamic_time_slots": [
        {
            "id": null,
            "week_day": null,
            "special_date": "2024-09-24",
            "open_from": "00:00:00",
            "open_to": "00:00:00",
            "type": "daily",
            "station": null,
            "deleted": false
        }
    ],
    "name": "Torino",
    "return_from": "09:00:00",
    "return_to": "11:00:00",
    "time_slots": [],
    "timezone": "Europe/Berlin",
    "zip": "10148 Torino",
    "google_link": "https://goo.gl/maps/yuXKJaiHwK8HvG2Q8",
    "enabled": true,
    "public": true,
    "one_way": true,
    "backups": [],
    "returns": [
        51,
        1
    ],
    "fallback": null
}',
    true
);
$timeFrames = json_decode(
    '[
    {
        "startDate": "2024-10-18T00:00:00+00:00",
        "endDate": "2024-10-25T00:00:00+00:00"
    }
]',
    true
);
