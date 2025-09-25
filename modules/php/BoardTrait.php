<?php
namespace Bga\Games\Maquis;
trait BoardTrait {
    protected function getBoard() {
        return $this->getCollectionFromDb(
            "SELECT `space_id`, `is_safe`, `marker_number`, `mission_id`, `dark_lady_location` FROM `board`;"
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
            WHERE is_safe = 0 AND is_field = 0 AND (mission_id = 0 || marker_number = 0);
        '));

        $spacesWithPawns = array_merge(
            $this->getSpacesWithResistanceWorkers(), 
            $this->getSpacesWithMilice(), 
            $this->getSpacesWithSoldiers()
        );

        return array_filter($result, function($space) use ($spacesWithPawns) {
            return !in_array($space, $spacesWithPawns);
        });
    }

    protected function getEmptyFields(): array {
        $result = $this->getCollectionFromDb('
            SELECT space_id
            FROM board
            WHERE is_field = 1;
        ');

        return $result;
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
    
    protected function getSpacesWithItems(): array {
        return $this->getCollectionFromDb("
            SELECT space_id, item, quantity
            FROM board
            WHERE has_item = TRUE;
        ");
    }

    protected function getSpacesWithRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id, room_id
            FROM board
            WHERE room_id IS NOT NULL;
        ");
    }

    protected function checkMarkersInSpaces($spaces): bool {
        $result = $this->getCollectionFromDb("
            SELECT space_id, marker_number
            FROM board
            WHERE space_id IN (" . implode(",", $spaces) . ");");

        $this->dump("result", $result);

        $allSpacesHaveMarkers = true;
        foreach ($result as $space) {
            if ((int) $space["marker_number"] <= 0) {
                $allSpacesHaveMarkers = false;
                break;
            }
        }
        return $allSpacesHaveMarkers;
    }

    protected function countMarkers(int $spaceID): int {
        return (int) $this->getUniqueValueFromDb("SELECT marker_number FROM board WHERE space_id = $spaceID;");
    }

    protected function countMarkersInSpaces(array $spaces): int {
        $result = $this->getCollectionFromDb("
            SELECT space_id, marker_number
            FROM board
            WHERE space_id IN (" . implode(",", $spaces) . ");
        ");

        return array_reduce($result, function($carry, $space) {
            $carry += (int) $space['marker_number'];
            return $carry;
        }, 0);
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

    protected function updateSpace($spaceID, $hasWorker = false, $hasMilice = false, $hasSoldier = false) {
        self::DbQuery('
            UPDATE board
            SET has_worker = ' . (int) $hasWorker . ', has_milice = ' . (int) $hasMilice . ', has_soldier = ' . (int) $hasSoldier . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    protected function setRoomId($spaceID, $roomID) {
        self::DbQuery("
            UPDATE board
            SET room_id = $roomID
            WHERE space_id = $spaceID;
        ");
    }

    protected function placeMarker(int $spaceID): void {
        static::DbQuery("UPDATE board SET marker_number = marker_number + 1 WHERE space_id = $spaceID;");

        $this->notify->all("markerPlaced", clienttranslate("Marker placed at " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID)
        ));
    }

    protected function removeMarker(int $spaceID): void {
        static::DbQuery("UPDATE board SET marker_number = marker_number - 1 WHERE space_id = $spaceID;");

        $this->notify->all("markerRemoved", clienttranslate("Marker removed from " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID,
            "markerNumber" => $this->countMarkers($spaceID)
        ));
    }

    protected function updateFieldsSafety(int $spaceID, $isSafe = false): void {
        self::DbQuery('
            UPDATE board
            SET is_safe = ' . (int) $isSafe . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    protected function updateDarkLadyLocation(int $spaceID, bool $darkLadyLocation): void {
        self::DbQuery('
            UPDATE board
            SET dark_lady_location = ' . (int) $darkLadyLocation . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }
}