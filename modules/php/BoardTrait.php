<?php
namespace Bga\Games\Maquis;
trait BoardTrait {
    protected function getBoard() {
        return $this->getCollectionFromDb(
            "SELECT `space_id`, `is_safe`, `dark_lady_location` FROM `board`;"
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

        //TODO: Filter mission spaces with markers

        $spacesWithTokens = $this->getSpacesWithTokens();

        $spacesWithPawns = array_merge(
            $this->getSpacesWithResistanceWorkers(), 
            $this->getSpacesWithMilice(), 
            $this->getSpacesWithSoldiers()
        );

        return array_filter($result, function($space) use ($spacesWithPawns, $spacesWithTokens) {
            return !in_array($space, $spacesWithPawns) && 
                    (!in_array($space, $this->getFields()) || 
                    (in_array($space, $spacesWithTokens) && in_array($space, $spacesWithTokens)));
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
            return !in_array((int) $field, $fieldsWithTokens);
        });

        return $fields;
    }

    protected function getSpacesWithResistanceWorkers(): array {
        $resistanceWorkersLocations = array_map(function ($resistanceWorker) {
            return $resistanceWorker['location'];
        }, $this->getResistanceWorkers());
        if ($this->getIsMissionSelected(MISSION_INFILTRATION) && $this->getIsMoleInserted()) {
            $spaceIdWithMole = $this->getSpaceIdsByMissionName(MISSION_INFILTRATION)[0];

            $resistanceWorkersLocations = array_filter($resistanceWorkersLocations, function($spaceId) use ($spaceIdWithMole) {
                return $spaceId != $spaceIdWithMole;
            });
        }
        return $resistanceWorkersLocations;
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

    protected function getSpacesWithRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id, room_id
            FROM board
            WHERE room_id IS NOT NULL;
        ");
    }

    protected function getSpaceIdsByMissionId(int $missionID): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id
            FROM board
            WHERE mission_id = $missionID;
        ");
    }
    
    protected function getIsRoomPlaced(int $spaceID): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT room_id
            FROM board
            WHERE space_id = $spaceID;
        ");
    }

    protected function setRoomId($spaceID, $roomID) {
        self::DbQuery("
            UPDATE board
            SET room_id = $roomID
            WHERE space_id = $spaceID;
        ");
    }
}