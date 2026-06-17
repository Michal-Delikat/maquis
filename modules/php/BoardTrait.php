<?php
namespace Bga\Games\Maquis;
trait BoardTrait {
    protected function getBoard() {
        return $this->getCollectionFromDb(
            "SELECT `space_id`, `is_safe` FROM `board`;"
        );
    }

    protected function getSpaceNameById(int $spaceID): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT space_name
            FROM board
            WHERE space_id = $spaceID;
        ");
    }

    protected function getEmptySpaces(): array {
        $result = array_keys($this->getCollectionFromDB('
            SELECT space_id
            FROM board
            WHERE is_safe = 0;
        '));

        $spacesWithMarkers = array_keys($this->getSpacesWithMarkers());

        $spacesWithTokens = $this->getSpacesWithTokens();

        $spacesWithPawns = array_merge(
            $this->getSpacesWithResistanceWorkers(), 
            $this->getSpacesWithMilice(), 
            $this->getSpacesWithSoldiers()
        );

        $fieldSpaces = $this->getFields();
        $spaceWithMole = $this->getSpaceIdWithMole();
        $missionSpaces = [MISSION_A_SPACE_A, MISSION_A_SPACE_B, MISSION_A_SPACE_C, MISSION_B_SPACE_A, MISSION_B_SPACE_B, MISSION_B_SPACE_C];

        return array_filter($result, function($space) use ($spacesWithPawns, $spacesWithTokens, $fieldSpaces, $spacesWithMarkers, $spaceWithMole, $missionSpaces) {
            return !in_array($space, $spacesWithPawns) && 
                    (string) $space !== (string) $spaceWithMole && 
                    (!in_array($space, $fieldSpaces) || (in_array($space, $fieldSpaces) && in_array($space, $spacesWithTokens))) && 
                    ((!in_array($space, $spacesWithMarkers) && in_array((int) $space, $missionSpaces)) || !in_array((int) $space, $missionSpaces)) &&
                    !($space === RIGHT_BOTTOM_SPARE_ROOM && $this->getIsMissionSelected(MISSION_BOMB_FOR_THE_OFFICER) && !$this->getIsMissionCompleted(MISSION_BOMB_FOR_THE_OFFICER));
        });
    }

    protected function getFields(): array {
        $result = (array) $this->getCollectionFromDb('
            SELECT space_id
            FROM board
            WHERE is_field = 1;
        ');

        return array_keys($result);
    }

    protected function getEmptyFields(): array {
        $fields = $this->getFields();

        $fieldsWithTokens = array_map('intval', $this->getSpacesWithTokens());

        $fields = array_filter($fields, function($field) use ($fieldsWithTokens) {
            return !in_array((int) $field, $fieldsWithTokens) && !($field === RIGHT_FIELD && $this->getIsMissionSelected(MISSION_BOMB_FOR_THE_OFFICER) && !$this->getIsMissionCompleted(MISSION_BOMB_FOR_THE_OFFICER));
        });

        return array_values($fields);
    }

    protected function getSpacesWithResistanceWorkers(): array {
        return array_map(function ($resistanceWorker) {
            return $resistanceWorker['location'];
        }, $this->getResistanceWorkers());
    }
    
    protected function getSpacesWithMilice(): array {
        return array_map(function ($milice) {
            return $milice['location'];
        }, $this->getMilice());
    }

    protected function getSpacesWithSoldiers(): array {
        return array_map(function ($milice) {
            return $milice['location'];
        }, $this->getSoldiers());
    }
    
    protected function getSpacesWithTokens(): array {
        $result = (array) $this->getCollectionFromDb("
            SELECT location
            FROM components
            WHERE name LIKE '%token%' AND state = 'placed';
        ");

        $result = array_map(function($space) {
            return explode("_", $space['location'])[0];
        }, $result);

        return array_unique($result);
    }

    protected function getSpaceIdsByMissionId(int $missionID): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id
            FROM board
            WHERE mission_id = $missionID;
        ");
    }

    protected function updateFieldsSafety(int $spaceID, $isSafe = false): void {
        self::DbQuery('
            UPDATE board
            SET is_safe = ' . (int) $isSafe . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    public static function removePath(int $startId, int $endId): void {
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
                if ($patrolSkipped) {
                    return ["escapeFound" => true, "fakeIdUsed" => $hasFakeId];
                } else {
                    return ["escapeFound" => true, "fakeIdUsed" => false];
                } 
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

        return ["escapeFound" => false, "fakeIdUsed" => false];
    }
}