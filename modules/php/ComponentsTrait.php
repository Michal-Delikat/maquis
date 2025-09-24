<?php

trait ComponentsTrait {
    function getResistanceWorkers(): array {
        return $this->getCollectionFromDb("SELECT * FROM components WHERE name LIKE 'resistance%';");
    }

    function getMilice(): array {
        return $this->getCollectionFromDb("SELECT * FROM components WHERE name LIKE 'milice%';");
    }

    function getSoldiers(): array {
        return $this->getCollectionFromDb("SELECT * FROM components WHERE name REGEXP '^soldier_[0-9]+$'");
    }

    function getNextAvailableWorker(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'resistance%' AND state = 'active' LIMIT 1;");
    }

    function getNextAvailableMilice(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'milice%' AND location = 'off_board' LIMIT 1;");
    }

    function getNextAvailableSoldier(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'soldier%' AND location = 'off_board' LIMIT 1;");
    }

    function getNextInactiveSoldier(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'soldier%' AND state = 'inactive' LIMIT 1;");
    }

    function getWorkerIdByLocation(string $location): string {
        return (string) $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'resistance%' AND location = '$location';");
    }

    function getMiliceIdByLocation(string $location): string {
        return (string) $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'milice%' AND location = '$location';");
    }

    function getSoldierIdByLocation(string $location): string {
        return (string) $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'soldier%' AND location = '$location';");
    }

    function updateComponent(string $componentID, string $location, string $state): void {
        static::DbQuery("
            UPDATE components
            SET location = '$location', state = '$state'
            WHERE name = '$componentID';
        ");
    }

    function recruitWorker(): void {
        $workerID = (string) $this->getUniqueValueFromDb("
            SELECT name
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'inactive'
            LIMIT 1;
        ");

        $this->updateComponent($workerID, 'safe_house', 'active');
    }

    function getPlacedResistance(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'placed';
        ");
    }

    function getActiveResistance(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND (state = 'active' OR state = 'placed');
        ");
    }

    function getResistanceToRecruit(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'inactive'; 
        ");
    }

    function getPlacedMilice(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'milice%' AND state = 'placed';
        ");
    }

    function getPlacedSoldiers(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'soldier%' AND state = 'placed'; 
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