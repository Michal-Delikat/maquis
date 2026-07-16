<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace Bga\Games\Maquis;

trait Markers {
    // ROUND
    function getRoundNumber(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'round_marker';
        ");
    }

    function setRoundNumber(int $round): void {
        self::DbQuery("
            UPDATE components
            SET location = $round
            WHERE name = 'round_marker';  
        ");

        $this->notify->all("roundNumberSet", clienttranslate('Round ${round} begins.'), array(
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

    function setMorale(int $newMorale, bool $notify = true): void {
        if ($newMorale > 7) {
            $newMorale = 7;
        } else if ($newMorale < 0) {
            $newMorale = 0;
        }

        self::DbQuery("
            UPDATE components
            SET location = $newMorale
            WHERE name = 'morale_marker';
        ");

        if ($notify) {
            $this->notify->all("moraleSet", clienttranslate('Morale is ${morale}'), array(
                "morale" => $newMorale
            ));
        }
    }

    function incrementMorale(): void {
       $this->setMorale($this->getMorale() + 1);
    }

    function decrementMorale(): void {
       $this->setMorale($this->getMorale() - 1);
    }

    // SOLDIERS

    function getActiveSoldiers(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'soldier_marker';
        ");
    }

    function setActiveSoldiers(int $soldierNumber): void {
        if ($soldierNumber > 5) {
            $soldierNumber = 5;
        }

        self::DbQuery("
            UPDATE components
            SET location = $soldierNumber
            WHERE name = 'soldier_marker';
        ");

        $this->notify->all("activeSoldiersSet", clienttranslate('There are ${soldierNumber} active soldiers now'), array(
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
            WHERE name = '$markerID';
        ");

        $this->notify->all("markerPlaced", clienttranslate('Marker placed at ${spaceName}'), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID),
            "spaceName" => $this->getSpaceNameById($spaceID)
        ));
    }

    protected function removeMarker(int $spaceID): void {
        self::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE location = '$spaceID' AND name LIKE 'mission_marker%'
            LIMIT 1;
        ");

        $this->notify->all("markerRemoved", clienttranslate('Marker removed from ${spaceName}'), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID),
            "spaceName" => $this->getSpaceNameById($spaceID)
        ));
    }

    protected function checkMarkersInSpaces(array $spaces): bool {
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

    protected function getDarkLadyLocation(): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name = 'dark_lady_location';
        ");
    }

    protected function setDarkLadyLocation(string $spaceID, string $state): void {
        self::DbQuery("
            UPDATE components
            SET location = '$spaceID', state = '$state'
            WHERE name = 'dark_lady_location'
        ");
    }

    protected function getSpacesWithMarkers(): array {
        return (array) $this->getCollectionFromDb("
            SELECT location, COUNT(*) AS marker_number
            FROM components
            WHERE name like 'mission_marker%' AND state = 'placed'
            GROUP BY location;
        ");
    }

    protected function getBridgesWithMarkers(): array {
        $spacesWithMarkers = (array) $this->getCollectionFromDb("
            SELECT location 
            FROM components 
            WHERE name like 'mission_marker%' AND location IN (24, 25);
        ");

        return array_map(function ($space) {
            return $space['location'];
        }, $spacesWithMarkers);
    }
}