<?php
namespace Bga\Games\Maquis;

trait Globals {
    function getDifficultyMode(): string {
        return $this->getGameStateValue("difficulty_mode");
    }

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

    protected function getSelectedField(): int {
        return $this->getGameStateValue("selected_field");
    }

    protected function setSelectedField(int $spaceID): void {
        $this->setGameStateValue("selected_field", $spaceID);
    }

    protected function getExplosivesAtBridgePlanted(): bool {
        return $this->getGameStateValue("explosives_at_bridge_planted");
    }

    protected function setExplosivesAtBridgePlanted(bool $explosivesAtBridgePlanted): void {
        $this->setGameStateValue("explosives_at_bridge_planted", $explosivesAtBridgePlanted);
    }

    protected function setSoldiersDistracted(bool $soldiersDistracted): void {
        $this->setGameStateValue("soldiers_distracted", $soldiersDistracted);
    }

    protected function getSoldiersDistracted(): bool {
        return $this->getGameStateValue("soldiers_distracted");
    }

    protected function setSecondPass(bool $secondPass): void {
        $this->setGameStateValue("second_pass", $secondPass);
    }

    protected function getSecondPass(): bool {
        return $this->getGameStateValue("second_pass");
    }
}