<?php

namespace Bga\Games\Maquis;

trait RoundTrait {
    protected function getRoundData(): array {
        return (array) $this->getObjectFromDb(
            "SELECT `round`, `morale`, `active_soldiers`, `active_resistance`, `resistance_to_recruit`, `placed_resistance`, `placed_milice`, `placed_soldiers`, `milice_in_game` FROM `round_data`"
        );
    }

    protected function getActiveSpace(): int {
        return (int) $this->getUniqueValueFromDb("SELECT active_space FROM round_data");
    }

    protected function getActiveResistance(): int {
        return (int) $this->getUniqueValueFromDb("SELECT active_resistance FROM round_data");
    }

    protected function getResistanceToRecruit(): int {
        return (int) $this->getUniqueValueFromDb("SELECT resistance_to_recruit FROM round_data");
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
        $placedMilice = $this->getRoundData()['placed_milice'];
        return ($weapon > 0 && !$this->getShotToday() && $placedMilice > 0) && !($this->getIsMissionSelected(MISSION_GERMAN_SHEPARDS) && !$this->getIsMissionCompleted(MISSION_GERMAN_SHEPARDS));
    }

    protected function getIsMoleInserted(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT mole_inserted FROM round_data");
    }

    protected function updateActiveResistance($newNumber) {
        self::DbQuery("
            UPDATE round_data
            SET active_resistance = $newNumber;
        ");

        $this->notify->all("activeResistanceUpdated", clienttranslate("You have $newNumber active resistance operatives"), array(
            "active_resistance" => $newNumber
        ));
    }

    protected function updatePlacedResistance($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_resistance = ' . $newNumber . ';'
        );

        $this->notify->all("placedResistanceUpdated", '', array(
            "placedResistance" => $newNumber,
        ));
    }

    protected function updateResistanceToRecruit($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET resistance_to_recruit = ' . $newNumber . ';'
        );

        $this->notify->all("resistanceToRecruitUpdated", '', array(
            "resistanceToRecruit" => $newNumber,
        ));
    }

    protected function updatePlacedMilice($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_milice = ' . $newNumber . ';'
        );

        $this->notify->all("placedMiliceUpdated", '', array(
            "placedMilice" => $newNumber,
        ));
    }

    protected function updateMiliceInGame($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET milice_in_game = ' . $newNumber . ';'
        );

        $this->notify->all("miliceInGameUpdated", '', array(
            "miliceInGame" => $newNumber,
        ));
    }

    protected function updatePlacedSoldiers($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_soldiers = ' . $newNumber . ';'
        );
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

    protected function updateRoundData($round, $morale, $placedMilice = 0, $placedSoldiers = 0): void {
        self::DbQuery("
            UPDATE round_data
            SET round = $round, morale = $morale, placed_milice = $placedMilice, placed_soldiers = $placedSoldiers;  
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

    protected function updateSoldiers($newNumber): void {
        self::DbQuery("
            UPDATE round_data
            SET active_soldiers = $newNumber;
        ");

        $this->notify->all("soldiersUpdated", clienttranslate("There are $newNumber active soldiers now"), array(
            "newNumber" => $newNumber
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

    protected function setResistanceToRecruit(int $resistanceToRecruit): void {
        self::DbQuery("
            UPDATE round_data
            SET resistance_to_recruit = $resistanceToRecruit;
        ");
    }

    protected function incrementMorale(): void {
       $this->updateMorale($this->getMorale() + 1);
    }

    protected function decrementMorale(): void {
       $this->updateMorale($this->getMorale() - 1);
    }
}