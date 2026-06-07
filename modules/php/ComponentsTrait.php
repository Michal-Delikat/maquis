<?php

namespace Bga\Games\Maquis;

trait ComponentsTrait {
    function updateComponent(string $componentID, string $location, string $state): void {
        static::DbQuery("
            UPDATE components
            SET location = '$location', state = '$state'
            WHERE name = '$componentID';
        ");
    }

    function removeToken(int $spaceID, string $tokenType): void {
        static::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE name LIKE $tokenType AND location = '$spaceID';
        ");
    }
}