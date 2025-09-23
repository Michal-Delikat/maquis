<?php

namespace Bga\Games\Maquis;

trait RoundTrait {
    protected function getRoundData(): array {
        return (array) $this->getObjectFromDb(
            "SELECT `round`, `morale` FROM `round_data`"
        );
    }

    protected function getActiveSpace(): int {
        return (int) $this->getUniqueValueFromDb("SELECT active_space FROM round_data");
    }

    protected function getActionTaken(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT action_taken FROM round_data");
    }

    protected function getMorale(): int {
        return (int) $this->getUniqueValueFromDb("SELECT morale from round_data;");
    }
    
    protected function getSelectedField(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT selected_field
            FROM round_data;
        ");
    }
    
    protected function getShotToday(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT shot_today FROM round_data");
    }

    protected function getCanShoot(): bool {
        $weapon = $this->getResource('weapon');
        $placedMilice = $this->getPlacedMilice();
        return ($weapon > 0 && !$this->getShotToday() && $placedMilice > 0) && !($this->getIsMissionSelected(MISSION_GERMAN_SHEPARDS) && !$this->getIsMissionCompleted(MISSION_GERMAN_SHEPARDS));
    }

    protected function getIsMoleInserted(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT mole_inserted FROM round_data");
    }

    protected function updateActiveSpace($spaceID) {
        self::DbQuery('
            UPDATE round_data
            SET active_space = ' . $spaceID . ';'
        );
    }

    protected function resetActiveSpace() {
        self::DbQuery('
            UPDATE round_data
            SET active_space = 0;'
        );
    }

    protected function updateActionTaken() {
        self::DbQuery('
            UPDATE round_data
            SET action_taken = TRUE;
        ');
    }

    protected function resetActionTaken(): void {
        self::DbQuery('
            UPDATE round_data
            SET action_taken = FALSE;
        ');
    }

    protected function updateRoundData($round, $morale): void {
        self::DbQuery("
            UPDATE round_data
            SET round = $round, morale = $morale;  
        ");

        $this->notify->all("roundDataUpdated", clienttranslate("Round $round begins."), array(
            "round" => $round,
            "morale" => $morale
        ));
    }

    protected function updateMorale(int $newMorale): void {
        self::DbQuery("
            UPDATE round_data
            SET morale = $newMorale;
        ");

        $this->notify->all("moraleUpdated", clienttranslate("Morale is $newMorale"), array(
            "player_id" => $this->getCurrentPlayerId(),
            "morale" => $newMorale
        ));
    }

    protected function setSelectedField(int $spaceID): void {
        self::DbQuery("
            UPDATE round_data
            SET selected_field = $spaceID;
        ");
    }

    protected function setShotToday(bool $shotToday): void {
        self::DbQuery("
            UPDATE round_data
            SET shot_today = " . (int) $shotToday . ";"
        );
    }

    protected function setMoleInserted(bool $moleInserted = false): void {
        self::DbQuery("UPDATE round_data SET mole_inserted = " . (int) $moleInserted . ";");
    }

    protected function incrementMorale(): void {
       $this->updateMorale($this->getMorale() + 1);
    }

    protected function decrementMorale(): void {
       $this->updateMorale($this->getMorale() - 1);
    }
}