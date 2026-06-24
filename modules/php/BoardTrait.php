<?php
namespace Bga\Games\Maquis;

trait BoardTrait {
    protected function setupBoard(): void {
        static::DbQuery('
            INSERT INTO board (space_id, space_name, is_safe, is_field)
            VALUES
            (1, "Rue Baradat", FALSE, FALSE),
            (2, "Fence", FALSE, FALSE),
            (3, "Pont du Nord", FALSE, FALSE),
            (4, "Radio B", FALSE, FALSE),
            (5, "Doctor", FALSE, FALSE),
            (6, "Poor District", FALSE, FALSE),
            (7, "Black Market", FALSE, FALSE),
            (8, "Spare Room", FALSE, FALSE),
            (9, "Radio A", FALSE, FALSE),
            (10, "Spare Room", FALSE, FALSE),
            (11, "Pont Leveque", FALSE, FALSE),
            (12, "Grocer", FALSE, FALSE),
            (13, "Spare Room", FALSE, FALSE),
            (14, "Field", FALSE, TRUE),
            (15, "Cafe", FALSE, FALSE),
            (16, "Safe House", TRUE, FALSE),
            (17, "Field", FALSE, TRUE),
            (26, "Fixer", FALSE, FALSE);
        ');
    }

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

    protected function getIsWorkerAtBarracksInBombTheBarracksMission(): bool {
        return $this->getIsMissionSelected(MISSION_BOMB_THE_BARRACKS) && !$this->getIsMissionCompleted(MISSION_BOMB_THE_BARRACKS) && $this->getWorkerIdByLocation(MISSION_B_SPACE_C);
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
                    (!in_array($space, $fieldSpaces) || (in_array($space, $fieldSpaces) && (in_array($space, $spacesWithTokens) || $this->getIsWorkerAtBarracksInBombTheBarracksMission()))) && 
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
}