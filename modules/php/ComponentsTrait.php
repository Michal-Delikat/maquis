<?php

trait ComponentsTrait {
    function getResistanceWorkers(): array {
        return $this->getCollectionFromDb("SELECT * FROM components WHERE name LIKE 'resistance%';");
    }

    function getNextAvailableWorker(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE state = 'active' LIMIT 1;");
    }

    function getWorkerIdByLocation(string $location): string {
        return (string) $this->getUniqueValueFromDb("SELECT name FROM components WHERE location = '$location';");
    }

    function updateResistanceWorkerLocation(string $workerID, string $location, string $state): void {
        static::DbQuery("
            UPDATE components
            SET location = '$location', state = '$state'
            WHERE name = '$workerID';
        ");
    }
}