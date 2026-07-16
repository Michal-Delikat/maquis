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

trait Components {
    protected function setupComponents(string $difficultyMode = NORMAL): void {
        $fifthResistanceWorker = in_array($difficultyMode, [TRICKY, HARD, VERY_HARD]) ? '("resistance_5", "off_board", "not_available")' : '("resistance_5", "safe_house", "active")';

        static::DbQuery('
            INSERT INTO components (name, location, state)
            VALUES
            ("dark_lady_location", "off_board", "available"),
            ("room_' . ROOM_INFORMANT . '", "off_board", "available"),
            ("room_' . ROOM_COUNTERFEITER . '", "off_board", "available"),
            ("room_' . ROOM_SAFE_HOUSE . '", "off_board", "available"),
            ("room_' . ROOM_CHEMISTS_LAB . '", "off_board", "available"),
            ("room_' . ROOM_SMUGGLER . '", "off_board", "available"),
            ("room_' . ROOM_PROPAGANDIST . '", "off_board", "available"),
            ("room_' . ROOM_FIXER . '", "off_board", "available"),
            ("room_' . ROOM_PHARMACIST . '", "off_board", "available"),
            ("room_' . ROOM_FORGER . '", "off_board", "available"),
            ("mission_card_' . MISSION_MILICE_PARADE_DAY . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_OFFICERS_MANSION . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_SABOTAGE . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_UNDERGROUND_NEWSPAPER . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_INFILTRATION . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_GERMAN_SHEPARDS . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_DOUBLE_AGENT . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_AID_THE_SPY . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_ASSASSINATION . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_DESTROY_THE_TRAIN . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_LIBERATE_THE_TOWN . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_CODED_MESSAGES . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_TAKE_OUT_THE_BRIDGES . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_BOMB_FOR_THE_OFFICER . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_MILICE_HQ . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_BOMB_THE_BARRACKS . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_FREE_THE_RESISTANCE_LEADER . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_DESTROY_AA_GUNS . '", "off_board", "not_selected"),
            ("mission_marker_1", "off_board", "available"),
            ("mission_marker_2", "off_board", "available"),
            ("mission_marker_3", "off_board", "available"),
            ("mission_marker_4", "off_board", "available"),
            ("mission_marker_5", "off_board", "available"),
            ("mission_marker_6", "off_board", "available"),
            ("mission_marker_7", "off_board", "available"),
            ("mission_marker_8", "off_board", "available"),
            ("mission_marker_9", "off_board", "available"),
            ("mission_marker_10", "off_board", "available"),
            ("food_token_1", "off_board", "available"),
            ("food_token_2", "off_board", "available"),
            ("food_token_3", "off_board", "available"),
            ("food_token_4", "off_board", "available"),
            ("medicine_token_1", "off_board", "available"),
            ("medicine_token_2", "off_board", "available"),
            ("medicine_token_3", "off_board", "available"),
            ("medicine_token_4", "off_board", "available"),
            ("money_token_1", "off_board", "available"),
            ("money_token_2", "off_board", "available"),
            ("money_token_3", "off_board", "available"),
            ("money_token_4", "off_board", "available"),
            ("explosives_token_1", "off_board", "available"),
            ("explosives_token_2", "off_board", "available"),
            ("explosives_token_3", "off_board", "available"),
            ("explosives_token_4", "off_board", "available"),
            ("weapon_token_1", "off_board", "available"),
            ("weapon_token_2", "off_board", "available"),
            ("weapon_token_3", "off_board", "available"),
            ("weapon_token_4", "off_board", "available"),
            ("intel_token_1", "off_board", "available"),
            ("intel_token_2", "off_board", "available"),
            ("intel_token_3", "off_board", "available"),
            ("intel_token_4", "off_board", "available"),
            ("poison_token_1", "off_board", "available"),
            ("poison_token_2", "off_board", "available"),
            ("poison_token_3", "off_board", "available"),
            ("poison_token_4", "off_board", "available"),
            ("fake_id_token_1", "off_board", "available"),
            ("fake_id_token_2", "off_board", "available"),
            ("fake_id_token_3", "off_board", "available"),
            ("fake_id_token_4", "off_board", "available"),
            ("aa_gun_token_1", "off_board", "available"),
            ("aa_gun_token_2", "off_board", "available"),
            ("aa_gun_token_3", "off_board", "available"),
            ("aa_gun_token_4", "off_board", "available"),
            ("round_marker", "0", "NaN"),
            ("morale_marker", "6", "NaN"),
            ("soldier_marker", "0", "NaN"),
            ("resistance_1", "safe_house", "active"),
            ("resistance_2", "safe_house", "active"),
            ("resistance_3", "cafe", "inactive"),
            ("resistance_4", "cafe", "inactive"),'.
            $fifthResistanceWorker .',
            ("milice_1", "barracks", "active"),
            ("milice_2", "barracks", "active"),
            ("milice_3", "barracks", "active"),
            ("milice_4", "barracks", "active"),
            ("milice_5", "barracks", "active"),
            ("soldier_1", "barracks", "inactive"),
            ("soldier_2", "barracks", "inactive"),
            ("soldier_3", "barracks", "inactive"),
            ("soldier_4", "barracks", "inactive"),
            ("soldier_5", "barracks", "inactive");
        ');
    }

    protected function updateComponent(string $componentID, string $location, string $state): void {
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