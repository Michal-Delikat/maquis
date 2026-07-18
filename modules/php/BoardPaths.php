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

trait BoardPaths {
    public function setupBoardPaths(): void {
        static::DbQuery('
            INSERT INTO board_path (space_id_start, space_id_end)
            VALUES
            (1, 2),
            (1, 5),

            (2, 1),
            (2, 6),

            (3, 6),
            (3, 7),

            (4, 7),
            (4, 8),

            (5, 1),
            (5, 9),
            (5, 10),
            (5, 11),

            (6, 2),
            (6, 3),
            (6, 7),
            (6, 11),

            (7, 3),
            (7, 4),
            (7, 6),
            (7, 8),
            (7, 12),

            (8, 4),
            (8, 7),

            (9, 5),
            (9, 10),

            (10, 5),
            (10, 9),

            (11, 5),
            (11, 6),
            (11, 16),

            (12, 7),
            (12, 13),
            (12, 16),

            (13, 12),

            (14, 15),

            (15, 14),
            (15, 16),

            (16, 11),
            (16, 12),
            (16, 15),
            (16, 17),

            (17, 16);
        ');
    }

    protected static function removePath(int $startId, int $endId): void {
        self::DbQuery("
            DELETE FROM board_path
            WHERE (space_id_start = $startId AND space_id_end = $endId)
                OR (space_id_start = $endId AND space_id_end = $startId);
        ");
    }

    protected function getBoardPaths(): array {
        $result = (array) $this->getCollectionFromDb("
            SELECT path_id, space_id_start, space_id_end
            FROM board_path;
        ");

        $paradeCanHappen = $this->getIsMissionSelected(MISSION_MILICE_PARADE_DAY) && !$this->getIsMissionCompleted(MISSION_MILICE_PARADE_DAY);
        
        return array_filter($result, function ($connection) use ($paradeCanHappen) {
            return !(
                    (($connection['space_id_start'] == '1' && $connection['space_id_end'] == '2') || ($connection['space_id_start'] == '2' && $connection['space_id_end'] == '1')) && 
                    $this->isParadeDay() &&
                    $paradeCanHappen
                );
        });
    }

    protected function checkEscapeRoute(): array {
        $activeSpace = $this->getActiveSpace();
        $board = $this->getBoard();
        $boardPaths = $this->getBoardPaths();
        $spacesWithPatrols = array_merge($this->getSpacesWithMilice(), $this->getSpacesWithSoldiers());
        $hasFakeId = $this->checkIsTokenTypeInSpace($activeSpace, TOKEN_FAKE_ID);

        $spacesToCheck = array();
        $visited = array();
        $bestResult = null;

        foreach ($boardPaths as $boardPath) {
            if ($boardPath['space_id_start'] == $activeSpace) {
                $spacesToCheck[] = [$boardPath["space_id_end"], !$hasFakeId];
            }
        }

        for ($i = 0; $i < count($spacesToCheck); $i++) {
            [$spaceID, $patrolSkipped] = $spacesToCheck[$i];

            $visitedKey = "$spaceID-$patrolSkipped";
            if (in_array($visitedKey, $visited)) {
                continue;
            }
            $visited[] = $visitedKey;

            $isSafe = (bool) $board[$spaceID]['is_safe'];

            if ($isSafe) {
                $fakeIdUsed = $patrolSkipped && $hasFakeId;

                if (!$fakeIdUsed) {
                    return ["escapeFound" => true, "fakeIdUsed" => false];
                }

                $bestResult = ["escapeFound" => true, "fakeIdUsed" => true];
                continue;
            }

            $hasPatrol = in_array($spaceID, $spacesWithPatrols);

            if (!$hasPatrol) {
                foreach ($boardPaths as $boardPath) {
                    if ($boardPath['space_id_start'] == $spaceID) {
                        $spacesToCheck[] = [$boardPath["space_id_end"], $patrolSkipped];
                    }
                }
            } else if (!$patrolSkipped) {
                foreach ($boardPaths as $boardPath) {
                    if ($boardPath['space_id_start'] == $spaceID) {
                        $spacesToCheck[] = [$boardPath["space_id_end"], true];
                    }
                }
            }
        }

        return $bestResult ?? ["escapeFound" => false, "fakeIdUsed" => false];
    }
}