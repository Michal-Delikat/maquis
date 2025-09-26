<?php

trait ComponentsTrait {
    function updateComponent(string $componentID, string $location, string $state): void {
        static::DbQuery("
            UPDATE components
            SET location = '$location', state = '$state'
            WHERE name = '$componentID';
        ");
    }

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
}