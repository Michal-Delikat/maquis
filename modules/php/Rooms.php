<?php 

namespace Bga\Games\Maquis;

trait Rooms {
    protected function getRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name, location, state
            FROM components
            WHERE name LIKE 'room%';
        ");
    }

    protected function getAvailableRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name
            FROM components
            WHERE state = 'available' AND name LIKE 'room%';
        ");
    }

    protected function placeRoom(string $roomID, int $location): void {
        self::DbQuery("
            UPDATE components
            SET state = 'placed', location = $location
            WHERE name = '$roomID';
        ");
    }

    protected function getIsRoomPlaced(int $spaceID): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT *
            FROM components
            WHERE location = $spaceID AND name LIKE 'room%';
        ");
    }

    protected function getPlacedRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE 'room%' AND state = 'placed';
        ");
    }

    protected function getIsFixerInLocation(int $spaceID): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT *
            FROM components
            WHERE name = 'room_fixer' AND location = '$spaceID';
        ");
    }
}
