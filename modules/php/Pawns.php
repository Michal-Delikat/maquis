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

trait Pawns {
    
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

    function getNextActiveMilice(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'milice%' AND state = 'active' LIMIT 1;");
    }

    function getNextActiveSoldier(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'soldier%' AND NOT state = 'placed' LIMIT 1;");
    }

    function getLastAvailableWorker(): string {
        return $this->getUniqueValueFromDb("SELECT name FROM components WHERE name LIKE 'resistance%' AND state = 'active' ORDER BY name DESC LIMIT 1;");
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
            WHERE name LIKE 'resistance%' AND state = 'placed';
        ");
    }

    function getActiveResistance(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'resistance%' AND state IN ('active', 'placed');
        ");
    }

    function getActiveMilice(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE name LIKE 'milice%' AND state = 'active';
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

    function getIsMoleInserted(): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT * 
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'mole';
        ");
    }

    function getIsCryptographerPlaced(): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT * 
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'cryptographer';
        ");
    }

    function getSpaceIdWithMole(): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'mole';
        ");
    }

    function getSpaceIdWithCryptographer(): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT location
            FROM components
            WHERE name LIKE 'resistance%' AND state = 'cryptographer';
        ");
    }

    function returnWorker(int $spaceID): void {
        if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);

        $workerID = $this->getWorkerIdByLocation((string) $spaceID);
        $this->updateComponent($workerID, 'safe_house', 'active');
        
        if ($this->checkEscapeRoute($spaceID)['fakeIdUsed']) {
            $this->removeFakeId($spaceID);
        } else if ($this->checkIsTokenTypeInSpace($spaceID, TOKEN_FAKE_ID)) {
            $this->collectTokens(TOKEN_FAKE_ID, $spaceID);
        }

        $this->notify->all("workerReturned", clienttranslate('Worker safely returned from ${spaceName}'), array(
            "activeSpace" => $spaceID,
            "workerID" => $workerID,
            "spaceName" => $spaceName
        ));
    }

    function arrestWorker(int $spaceID): void {
        if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $workerID = $this->getWorkerIdByLocation((string) $spaceID);

        $this->updateComponent($workerID, 'arrest', 'arrested');

        if ($this->checkIsTokenTypeInSpace($spaceID, TOKEN_FAKE_ID)) {
            $this->removeFakeId($spaceID);
        }

        $this->notify->all("workerArrested", clienttranslate('Worker arrested at ${spaceName}'), array(
            "workerID" => $workerID,
            "spaceName" => $spaceName
        ));

        if ($this->getDifficultyMode() === VERY_HARD) {
            $this->setMorale($this->getMorale() - 1);
        }
    }
    
    function returnOrArrest(int $spaceID): void {
        if ($this->checkEscapeRoute($spaceID)['escapeFound']) {
            $this->returnWorker($spaceID);
        } else {
            $this->arrestWorker($spaceID);
        }
    }

    function removeWorker(int $spaceID): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $workerID = $this->getWorkerIdByLocation((string) $spaceID);
        $this->updateComponent($workerID, 'off_board', 'removed');

        $this->notify->all("workerRemoved", clienttranslate('Worker removed from ${spaceName}'), array(
            "workerID" => $workerID,
            "spaceName" => $spaceName
        ));
    }

    function insertMole(string $workerID, string $location): void {
        $this->updateComponent($workerID, $location, 'mole');

        $this->notify->all("moleInserted", clienttranslate('Mole inserted'));
    }
}