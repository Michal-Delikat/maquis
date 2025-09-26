<?php

namespace Bga\Games\Maquis;

trait MarkersTrait {
    // ROUND
    function getRoundNumber(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'round_marker';
        ");
    }

    function updateRoundNumber($round): void {
        self::DbQuery("
            UPDATE components
            SET location = $round
            WHERE name = 'round_marker';  
        ");

        $this->notify->all("roundNumberUpdated", clienttranslate("Round $round begins."), array(
            "round" => $round,
        ));
    }

    // MORALE

    function getMorale(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'morale_marker';
        ");
    }

    function updateMorale(int $newMorale): void {
        self::DbQuery("
            UPDATE components
            SET location = $newMorale
            WHERE name = 'morale_marker';
        ");

        $this->notify->all("moraleUpdated", clienttranslate("Morale is $newMorale"), array(
            "morale" => $newMorale
        ));
    }

    function incrementMorale(): void {
       $this->updateMorale($this->getMorale() + 1);
    }

    function decrementMorale(): void {
       $this->updateMorale($this->getMorale() - 1);
    }

    // SOLDIERS

    function getActiveSoldiers(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'soldier_marker';
        ");
    }

    function updateActiveSoldiers(int $soldierNumber): void {
        self::DbQuery("
            UPDATE components
            SET location = $soldierNumber
            WHERE name = 'soldier_marker';
        ");

        $this->notify->all("activeSoldiersUpdated", clienttranslate("There are $soldierNumber active soldiers now"), array(
            "soldierNumber" => $soldierNumber
        ));
    }

    // MISSION MARKERS

    protected function getNextAvailableMarker(): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT name
            FROM components
            WHERE name LIKE 'mission_marker%' AND state = 'available'
            LIMIT 1;
        ");
    }

    protected function placeMarker(int $spaceID): void {
        $markerID = $this->getNextAvailableMarker();

        static::DbQuery("
            UPDATE components
            SET location = $spaceID, state = 'placed'
            WHERE name = '$markerID' AND name LIKE 'mission_marker%';
        ");

        $this->notify->all("markerPlaced", clienttranslate("Marker placed at " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID)
        ));
    }

    protected function removeMarker(int $spaceID): void {
        self::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE location = '$spaceID' AND name LIKE 'mission_marker%'
            LIMIT 1;
        ");

        $this->notify->all("markerRemoved", clienttranslate("Marker removed from " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID)
        ));
    }

    protected function checkMarkersInSpaces($spaces): bool {
        $allSpacesHaveMarkers = true;
        foreach ($spaces as $space) {
            if ((int) $this->countMarkers($space) <= 0) {
                $allSpacesHaveMarkers = false;
                break;
            }
        }
        return $allSpacesHaveMarkers;
    }

    protected function countMarkers(int $spaceID): int {
        return (int) $this->getUniqueValueFromDb("SELECT COUNT(*) AS marker_number FROM components WHERE location = '$spaceID' AND name LIKE 'mission_marker%';");
    }

    protected function countMarkersInSpaces(array $spaces): int {
        $result = $this->getCollectionFromDb("
            SELECT location, COUNT(*) AS marker_number
            FROM components
            WHERE location IN (" . implode(",", $spaces) . ") AND name LIKE 'mission_marker%'
            GROUP BY location;
        ");

        return array_reduce($result, function($carry, $space) {
            $carry += (int) $space['marker_number'];
            return $carry;
        }, 0);
    }

    protected function updateFieldsSafety(int $spaceID, $isSafe = false): void {
        self::DbQuery('
            UPDATE board
            SET is_safe = ' . (int) $isSafe . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    protected function updateDarkLadyLocation(int $spaceID, bool $darkLadyLocation): void {
        self::DbQuery('
            UPDATE board
            SET dark_lady_location = ' . (int) $darkLadyLocation . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    protected function getSpacesWithMarkers(): array {
        return (array) $this->getCollectionFromDb("
            SELECT location, COUNT(*) AS marker_number
            FROM components
            WHERE name like 'mission_marker%' AND state = 'placed'
            GROUP BY location;
        ");
    }
}