<?php

namespace Bga\Games\Maquis;

trait Player {
    protected function setupPlayer(array $players): void {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();
    }

    protected function getPlayerScore(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT player_score
            FROM player
            WHERE player_id = " . $this->getCurrentPlayerID() . ";"
        );
    }

    protected function setPlayerScore(int $playerScore): void {
        static::DbQuery("UPDATE player SET player_score = $playerScore;");
    }

    protected function incrementPlayerScore(): void {
        static::DbQuery("UPDATE player SET player_score = player_score + 1;");
    }
}