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

    protected function placeTokens(int $spaceID, string $tokenType, int $quantity = 1, bool $notify = true): void {
        $quantity = min($quantity, $this->getAvailableResource($tokenType));

        static::DbQuery("
            UPDATE components
            SET location = '$spaceID', state = 'placed'
            WHERE name LIKE '$tokenType%'
            AND location = 'off_board'
            AND state = 'available'
            LIMIT $quantity
        ");

        if ($notify) { 
            $tokens = (array) $this->getCollectionFromDb("
                SELECT name, location
                FROM components
                WHERE name LIKE '$tokenType%' AND state = 'placed' AND location LIKE '$spaceID%';
            ");

            $this->notify->all("tokensPlaced", clienttranslate('${quantity} ${tokenType} placed at ${spaceName}'), array(
                "tokens" => $tokens,
                "tokenType" => $tokenType === TOKEN_FAKE_ID ? "Fake Id" : $tokenType,
                "quantity" => $quantity === 1 ? '' : $quantity,
                "spaceName" => $this->getSpaceNameById($spaceID)
            ));

            $quantityPossesed = $this->getResource($tokenType);
            $this->notify->all("resourcesChanged", '', array(
                "resource_name" => $tokenType,
                "quantity" => $quantityPossesed,
                "available" => $this->getAvailableResource($tokenType)
            ));
        }
    }

    protected function removeFakeId(int $spaceID) {
        static::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE name LIKE 'fake_id_token%' AND location LIKE '$spaceID%';
        ");

        $this->notify->all("fakeIdRemoved", clienttranslate('Fake Id removed from ${display_name}'), array(
            "location" => $spaceID,
            "display_name" => $this->getSpaceNameById($spaceID)
        ));
    }

    protected function getTokenTypeInSpace(int $spaceID): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT SUBSTRING_INDEX(name, '_token', 1)
            FROM components
            WHERE location LIKE '$spaceID%' AND name LIKE '%token%'
            LIMIT 1;
        ");
    }

    protected function checkIsTokenTypeInSpace(int $spaceID, string $tokenType): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT name
            FROM components
            WHERE name LIKE '$tokenType%' AND location = '$spaceID';
        ");
    }

    protected function getTokenQuantityInSpace(int $spaceID): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE location LIKE '$spaceID%' AND name LIKE '%token%';
        ");
    }

    function removeAAGun(int $spaceID): void {
        static::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE name LIKE 'aa_gun_token%' AND location LIKE '$spaceID%';
        ");

        $this->notify->all("aaGunRemoved", clienttranslate('AA gun removed from ${location}'), array(
            "location" => $spaceID
        ));
    }

    function countAAGunsPlaced(): int {
        return (int) static::getUniqueValueFromDb("
            SELECT COUNT(*) 
            FROM components 
            WHERE name LIKE 'aa_gun_token%' AND state = 'placed';
        ");
    }
}