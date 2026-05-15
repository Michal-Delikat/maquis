<?php

namespace Bga\Games\Maquis;

trait GlobalsTrait {
    protected function getActiveSpace(): int {
        return $this->getGameStateValue("active_space");
    }

    protected function setActiveSpace(int $spaceID) {
        $this->setGameStateValue("active_space", $spaceID);
    }

    protected function resetActiveSpace() {
        $this->setGameStateValue("active_space", 0);
    }

    protected function getShotToday(): bool {
        return $this->getGameStateValue("shot_today");
    }

    protected function setShotToday(bool $shotToday): void {
        $this->setGameStateValue("shot_today", $shotToday);
    }

    protected function getCanShoot(): bool {
        $weapon = $this->getResource('weapon');
        $placedMilice = $this->getPlacedMilice();
        return ($weapon > 0 && !$this->getShotToday() && $placedMilice > 0) && !($this->getIsMissionSelected(MISSION_GERMAN_SHEPARDS) && !$this->getIsMissionCompleted(MISSION_GERMAN_SHEPARDS));
    }

    protected function getSelectedField(): int {
        return $this->getGameStateValue("selected_field");
    }

    protected function setSelectedField(int $spaceID): void {
        $this->setGameStateValue("selected_field", $spaceID);
    }

}