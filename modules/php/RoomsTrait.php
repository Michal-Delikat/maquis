<?php 

namespace Bga\Games\Maquis;

trait RoomsTrait {
    protected function getRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT room_id, room_name, available
            FROM room;
        ");
    }

    protected function getAvailableRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT room_id, room_name
            FROM room
            WHERE available = TRUE;
        ");
    }

    protected function setIsRoomAvailable(int $roomID, bool $isAvailable): void {
        self::DbQuery("
            UPDATE room
            SET available = " . (int) $isAvailable . " 
            WHERE room_id = $roomID;
        ");
    }
}