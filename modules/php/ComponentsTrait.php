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

    protected function placeTokens(int $spaceID, string $tokenType, int $quantity = 1): void {
        $quantity = min($quantity, $this->getAvailableResource($tokenType));

        for ($i = 1; $i <= $quantity; $i++) {
            $location = $spaceID . "_" . $i;

            static::DbQuery("
                UPDATE components
                SET location = '$location', state = 'placed'
                WHERE name LIKE '$tokenType%'
                AND location = 'off_board'
                AND state = 'available'
                LIMIT 1
            ");
        }

        $tokens = (array) $this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE '$tokenType%' AND state = 'placed' AND location LIKE '$spaceID%';
        ");

        $this->notify->all("tokensPlaced", clienttranslate('${quantity} ${tokenType} airdropped onto field'), array(
            "tokens" => $tokens,
            "tokenType" => $tokenType,
            "quantity" => $quantity
        ));

        $quantityPossesed = $this->getResource($tokenType);
        $this->notify->all("resourcesChanged", clienttranslate('You have ${quantity} ${resource_name}'), array(
            "resource_name" => $tokenType,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($tokenType)
        ));
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
}