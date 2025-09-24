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

    protected function getAvailableResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT COUNT(*) 
            FROM components
            WHERE name LIKE '$resourceName%' AND state = 'available';
        ;");
    }

    // protected function getResources(array $resourceNames): array {
    //     return (array) $this->getCollectionFromDb("
    //         SELECT quantity
    //         FROM resource
    //         WHERE resource_name IN (\"" . implode("\",", $resourceNames) . "\");"
    //     );
    // }

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

    protected function updateResourceQuantity(string $resourceName, int $amount): void {
        

        $quantityPossesed = $this->getResource($resourceName);

        
    }

    protected function incrementResourceQuantity(string $resourceName, int $amount = 1): void {
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

    protected function decrementResourceQuantity(string $resourceName, int $amount = 1): void {
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