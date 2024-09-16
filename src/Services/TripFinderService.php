<?php

namespace App\Services;

use App\Dto\TripDto;

class CircularTripService
{
    /**
     * Trova e restituisce viaggi ad anello dai tripDto
     *
     * @param array $trips Array di TripDto
     * @return array Viaggi ad anello
     */
    public function findCircularTrips(array $trips): array
    {
        // Mappa delle stazioni -> destinazioni
        $tripMap = [];

        foreach ($trips as $trip) {
            $startStation = $trip->pickupStation->fullName;
            foreach ($trip->dropoffStation as $destination) {
                $tripMap[$startStation][] = $destination->fullName;
            }
        }

        // Array per memorizzare gli anelli trovati
        $circularTrips = [];
        // Visita ogni stazione di partenza per trovare cicli
        foreach ($tripMap as $startStation => $destinations) {
            $this->searchForCircularTrip($startStation, $startStation, $tripMap, [], $circularTrips);
        }

        return $circularTrips;
    }

    /**
     * Funzione ricorsiva per cercare viaggi ad anello
     *
     * @param string $currentStation Stazione attuale
     * @param string $targetStation Stazione di destinazione (anello completo se coincide con la stazione iniziale)
     * @param array $tripMap Mappa delle stazioni -> destinazioni
     * @param array $visited Percorso attuale
     * @param array $circularTrips Elenco degli anelli trovati
     */
    private function searchForCircularTrip($currentStation, $targetStation, $tripMap, $visited, &$circularTrips)
    {
        // Aggiungi la stazione attuale al percorso visitato
        $visited[] = $currentStation;

        // Se la stazione corrente è la stazione target, abbiamo trovato un anello
        if ($currentStation === $targetStation && count($visited) > 1) {
            $circularTrips[] = $visited;

            return;
        }

        // Cerca tra le destinazioni della stazione attuale
        if (!isset($tripMap[$currentStation])) {
            return;
        }

        foreach ($tripMap[$currentStation] as $nextStation) {
            dump($nextStation, $visited);
            // Evita di visitare due volte la stessa stazione in un singolo percorso
            if (!in_array($nextStation, $visited)) {
                $this->searchForCircularTrip($nextStation, $targetStation, $tripMap, $visited, $circularTrips);
            }
        }
    }
}
