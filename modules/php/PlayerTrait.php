<?php

namespace Bga\Games\Maquis;

trait PlayerTrait {
    protected function getPlayerScore(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT player_score
            FROM player
            WHERE player_id = " . $this->getCurrentPlayerID() . ";"
        );
    }

    protected function incrementPlayerScore(): void {
        static::DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id = " . $this->getCurrentPlayerId() . ";");
    }
}