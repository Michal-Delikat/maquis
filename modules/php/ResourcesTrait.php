<?php 

namespace Bga\Games\Maquis;

trait ResourcesTrait {
    protected function getResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*) 
            FROM components 
            WHERE name LIKE '$resourceName%' AND state = 'possessed';
        ;");
    }

    protected function getTokenTypeInSpace(int $spaceID): string {
        return explode('_', (string) $this->getUniqueValueFromDb("
            SELECT name
            FROM components
            WHERE location LIKE '$spaceID%' AND name LIKE '%token%'
            LIMIT 1;
        "))[0];
    } 

    protected function getTokenQuantityInSpace(int $spaceID): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*)
            FROM components
            WHERE location LIKE '$spaceID%' AND name LIKE '%token%';
        ");
    }

    protected function getAvailableResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*) 
            FROM components
            WHERE name LIKE '$resourceName%' AND state = 'available';
        ;");
    }

    protected function getPlacedTokens(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE '%token%' AND state = 'placed';
        ");
    }

    protected function placeTokens(int $spaceID, string $tokenType, int $quantity): void {
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

        $this->notify->all("tokensPlaced", clienttranslate("$quantity $tokenType airdropped onto field"), array(
            "tokens" => $tokens,
        ));

        $quantityPossesed = $this->getResource($tokenType);
        $this->notify->all("resourcesChanged", clienttranslate("You have $quantityPossesed $tokenType."), array(
            "resource_name" => $tokenType,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($tokenType)
        ));
    }

    protected function collectTokens(string $tokenType, int $spaceID): void {
        self::DbQuery("
            UPDATE components
            SET location = 'possessed', state = 'possessed'
            WHERE name LIKE '$tokenType%' AND location LIKE '$spaceID%';
        ");

        $this->notify->all("tokensCollected", '', array(
            "tokenType" => $tokenType,
            "location" => $spaceID
        ));

        $quantityPossesed = $this->getResource($tokenType);
        $this->notify->all("resourcesChanged", clienttranslate("You have $quantityPossesed $tokenType."), array(
            "resource_name" => $tokenType,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($tokenType)
        ));
    }

    protected function getAllResources(): array {
        return [
            RESOURCE_FOOD => [RESOURCE_FOOD, $this->getResource(RESOURCE_FOOD), $this->getAvailableResource(RESOURCE_FOOD)],
            RESOURCE_MEDICINE => [RESOURCE_MEDICINE, $this->getResource(RESOURCE_MEDICINE), $this->getAvailableResource(RESOURCE_MEDICINE)],
            RESOURCE_MONEY => [RESOURCE_MONEY, $this->getResource(RESOURCE_MONEY), $this->getAvailableResource(RESOURCE_MONEY)],
            RESOURCE_EXPLOSIVES => [RESOURCE_EXPLOSIVES, $this->getResource(RESOURCE_EXPLOSIVES), $this->getAvailableResource(RESOURCE_EXPLOSIVES)],
            RESOURCE_WEAPON => [RESOURCE_WEAPON, $this->getResource(RESOURCE_WEAPON), $this->getAvailableResource(RESOURCE_WEAPON)],
            RESOURCE_INTEL => [RESOURCE_INTEL, $this->getResource(RESOURCE_INTEL), $this->getAvailableResource(RESOURCE_INTEL)]
        ];
    }

    protected function gainTokens(string $resourceName, int $amount = 1): void {
        self::DbQuery("
            UPDATE components
            SET location = 'possessed', state = 'possessed'
            WHERE name LIKE '$resourceName%' AND state = 'available'
            LIMIT $amount;
        ");

        $quantityPossesed = $this->getResource($resourceName);
        $this->notify->all("resourcesChanged", clienttranslate("You have $quantityPossesed $resourceName."), array(
            "resource_name" => $resourceName,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($resourceName)
        ));
    }

    protected function spendTokens(string $resourceName, int $amount = 1): void {
        self::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE name LIKE '$resourceName%' AND state = 'possessed'
            LIMIT $amount;
        ");
        
        $quantityPossesed = $this->getResource($resourceName);
        $this->notify->all("resourcesChanged", clienttranslate("You have $quantityPossesed $resourceName."), array(
            "resource_name" => $resourceName,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($resourceName)
        ));
    }
}