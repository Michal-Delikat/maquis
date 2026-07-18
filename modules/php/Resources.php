<?php 
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace Bga\Games\Maquis;

trait Resources {
    protected function getResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*) 
            FROM components 
            WHERE name LIKE '$resourceName%' AND state = 'possessed';
        ;");
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
        $this->notify->all("resourcesChanged", clienttranslate('You have ${quantity} ${display_name}'), array(
            "i18n" => ['display_name'],
            "resource_name" => $tokenType,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($tokenType),
            "display_name" => $this->material->resources[$tokenType]
        ));
    }

    protected function getAllResources(): array {
        $resources = $this->getCollectionFromDb("
            SELECT SUBSTRING_INDEX(name, '_token_', 1) AS resource_type, COUNT(*) AS amount
            FROM components
            WHERE name LIKE '%_token_%' AND name NOT LIKE 'aa_gun_token%' AND state = 'possessed'
            GROUP BY resource_type;
        ");
        $allResources = [RESOURCE_FOOD, RESOURCE_MEDICINE, RESOURCE_MONEY, RESOURCE_INTEL, RESOURCE_WEAPON, RESOURCE_EXPLOSIVES, RESOURCE_POISON, RESOURCE_FAKE_ID];
        return array_combine(
            $allResources,
            array_map(fn($type) => $resources[$type] ?? ['resource_type' => $type, 'amount' => 0], $allResources)
        );
    }

    protected function gainResources(string $resourceName, int $amount = 1): void {
        self::DbQuery("
            UPDATE components
            SET location = 'possessed', state = 'possessed'
            WHERE name LIKE '$resourceName%' AND state = 'available'
            LIMIT $amount;
        ");

        $quantityPossesed = $this->getResource($resourceName);
        $this->notify->all("resourcesChanged", clienttranslate('You have ${quantity} ${display_name}'), array(
            "i18n" => ['display_name'],
            "resource_name" => $resourceName,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($resourceName),
            "display_name" => $this->material->resources[$resourceName]
        ));
    }

    protected function spendResources(string $resourceName, int $amount = 1): void {
        self::DbQuery("
            UPDATE components
            SET location = 'off_board', state = 'available'
            WHERE name LIKE '$resourceName%' AND state = 'possessed'
            LIMIT $amount;
        ");
        
        $quantityPossesed = $this->getResource($resourceName);
        $this->notify->all("resourcesChanged", clienttranslate('You have ${quantity} ${display_name}'), array(
            "i18n" => ['display_name'],
            "resource_name" => $resourceName,
            "quantity" => $quantityPossesed,
            "available" => $this->getAvailableResource($resourceName),
            "display_name" => $this->material->resources[$resourceName]
        ));
    }
}