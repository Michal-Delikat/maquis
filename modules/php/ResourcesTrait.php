<?php 

namespace Bga\Games\Maquis;

trait ResourcesTrait {
    protected function getResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT quantity 
            FROM resource 
            WHERE resource_name = \"$resourceName\"
        ;");
    }

    protected function getAvailableResource(string $resourceName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT available 
            FROM resource 
            WHERE resource_name = \"$resourceName\"
        ;");
    }

    protected function getResources(array $resourceNames): array {
        return (array) $this->getCollectionFromDb("
            SELECT quantity
            FROM resource
            WHERE resource_name IN (\"" . implode("\",", $resourceNames) . "\");"
        );
    }

    protected function getAllResources(): array {
        return (array) $this->getCollectionFromDb("SELECT * FROM resource");
    }

    protected function updateResourceQuantity(string $resourceName, int $amount): void {
        self::DbQuery("
            UPDATE resource
            SET quantity = quantity + $amount, available = available - $amount
            WHERE resource_name = \"$resourceName\";
        ");

        $result = (array) $this->getObjectFromDb("
            SELECT quantity, available
            FROM resource
            WHERE resource_name = \"$resourceName\";
        ");

        $this->notify->all("resourcesChanged", clienttranslate("You have " . $result["quantity"] . " $resourceName."), array(
            "resource_name" => $resourceName,
            "quantity" => $result["quantity"],
            "available" => $result["available"]
        ));
    }

    protected function updateResourceQuantityFromCollectingAirdrop(string $resourceName, int $amount): void {
        self::DbQuery("
            UPDATE resource
            SET quantity = quantity + $amount
            WHERE resource_name = \"$resourceName\";
        ");

        $result = (array) $this->getObjectFromDb("
            SELECT quantity, available
            FROM resource
            WHERE resource_name = \"$resourceName\";
        ");

        $this->notify->all("resourcesChanged", clienttranslate("You have " . $result["quantity"] . " $resourceName."), array(
            "resource_name" => $resourceName,
            "quantity" => $result["quantity"],
            "available" => $result["available"]
        ));
    }

    protected function incrementResourceQuantity(string $resourceName, int $amount = 1): void {
        $availableResource = $this->getAvailableResource($resourceName);
        $amount = min($amount, $availableResource);
        
        $this->updateResourceQuantity($resourceName, $amount);
    }

    protected function decrementResourceQuantity(string $resourceName, int $amount = 1): void {
        $this->updateResourceQuantity($resourceName,  -$amount);
    }
}