<?php

namespace Bga\Games\Maquis;

trait PawnsTrait {
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
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'milice%' AND state = 'available' LIMIT 1;");
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

    function recruitWorker(): void {
        $workerID = (string) $this->getUniqueValueFromDb("
            SELECT name
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'inactive'
            LIMIT 1;
        ");

        $this->updateComponent($workerID, 'safe_house', 'active');

        $this->notify->all("workerRecruited", "", array(
            "workerID" => $workerID 
        ));
    }

    function getPlacedResistance(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND (state = 'placed' OR state = 'mole');
        ");
    }

    function getActiveResistance(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND (state = 'active' OR state = 'placed' OR state = 'mole');
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

    protected function getIsMoleInserted(): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT * 
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'mole';
        ");
    }

    protected function getSpaceIdWithMole(): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'mole';
        ");
    }

    public function returnWorker(int $spaceID): void {
        if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);

        $workerID = $this->getWorkerIdByLocation((string) $spaceID);
        $this->updateComponent($workerID, 'safe_house', 'active');

        $this->notify->all("workerReturned", clienttranslate("Worker safely returned from $spaceName"), array(
            "activeSpace" => $spaceID,
            "workerID" => $workerID
        ));
    }

    public function arrestWorker(int $spaceID): void {
        if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $workerID = $this->getWorkerIdByLocation((string) $spaceID);

        $this->updateComponent($workerID, 'arrest', 'arrested');
        
        $this->notify->all("workerArrested", clienttranslate("Worker arrested at " . $spaceName), array(
            "workerID" => $workerID
        ));
    }

    public function removeWorker(int $spaceID): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $this->updateComponent($this->getWorkerIdByLocation((string) $spaceID), 'off_board', 'removed');
        
        $this->notify->all("workerRemoved", clienttranslate("Worker removed from " . $spaceName), array(
            "activeSpace" => $spaceID
        ));
    }

    public function returnOrArrest(int $spaceID): void {
        if ($this->checkEscapeRoute($spaceID)) {
            $this->returnWorker($spaceID);
        } else {
            $this->arrestWorker($spaceID);
        }
    }
}