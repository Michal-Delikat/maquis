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

class Material {
    public const PATROL_CARD_ITEMS = [
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 1, 'space_a' => GROCER, 'space_b' => POOR_DISTRICT, 'space_c' => DOCTOR],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 2, 'space_a' => RADIO_B, 'space_b' => GROCER, 'space_c' => BLACK_MARKET],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 3, 'space_a' => PONT_LEVEQUE, 'space_b' => PONT_DU_NORD, 'space_c' => DOCTOR],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 4, 'space_a' => RADIO_B, 'space_b' => RUE_BARADAT, 'space_c' => PONT_LEVEQUE],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 5, 'space_a' => PONT_LEVEQUE, 'space_b' => BLACK_MARKET, 'space_c' => DOCTOR],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 6, 'space_a' => RADIO_A, 'space_b' => RUE_BARADAT, 'space_c' => GROCER],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 7, 'space_a' => RADIO_A, 'space_b' => PONT_LEVEQUE, 'space_c' => BLACK_MARKET],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 8, 'space_a' => FENCE, 'space_b' => RUE_BARADAT, 'space_c' => POOR_DISTRICT],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 9, 'space_a' => GROCER, 'space_b' => PONT_DU_NORD, 'space_c' => FENCE],
        ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 10, 'space_a' => FENCE, 'space_b' => PONT_DU_NORD, 'space_c' => PONT_LEVEQUE]
    ];

    public const ROOM_DESCRIPTIONS = [
        ROOM_SMUGGLER => 'Smuggler',
        ROOM_INFORMANT => 'Informant',
        ROOM_SAFE_HOUSE => 'Safe House',
        ROOM_CHEMISTS_LAB => 'Chemist\'s Lab',
        ROOM_PROPAGANDIST => 'Propagandist',
        ROOM_COUNTERFEITER => 'Counterfeiter',
        ROOM_FIXER => 'Fixer',
        ROOM_PHARMACIST => 'Pharmacist',
        ROOM_FORGER => 'Forger'
    ];

    public const ACTIONS = [
        // Basic board actions
        ACTION_GET_FOOD => ['name' => ACTION_GET_FOOD, 'is_safe' => false],
        ACTION_GET_MEDICINE => ['name' => ACTION_GET_MEDICINE, 'is_safe' => false],
        ACTION_GET_MONEY_FOR_FOOD => ['name' => ACTION_GET_MONEY_FOR_FOOD, 'is_safe' => false],
        ACTION_GET_MONEY_FOR_MEDICINE => ['name' => ACTION_GET_MONEY_FOR_MEDICINE, 'is_safe' => false],
        ACTION_PAY_FOR_MORALE => ['name' => ACTION_PAY_FOR_MORALE, 'is_safe' => true],
        ACTION_AIRDROP_FOOD => ['name' => ACTION_AIRDROP_FOOD, 'is_safe' => false],
        ACTION_AIRDROP_MONEY => ['name' => ACTION_AIRDROP_MONEY, 'is_safe' => false],
        ACTION_AIRDROP_WEAPON => ['name' => ACTION_AIRDROP_WEAPON, 'is_safe' => false],
        ACTION_GET_INTEL => ['name' => ACTION_GET_INTEL, 'is_safe' => false],
        ACTION_BUY_WEAPON => ['name' => ACTION_BUY_WEAPON, 'is_safe' => false],
        ACTION_GET_WORKER => ['name' => ACTION_GET_WORKER, 'is_safe' => false],
        ACTION_COLLECT_ITEMS => ['name' => ACTION_COLLECT_ITEMS, 'is_safe' => false],
        ACTION_GET_SPARE_ROOM => ['name' => ACTION_GET_SPARE_ROOM, 'is_safe' => true],
        // Spare Room actions
        ACTION_GET_MONEY => ['name' => ACTION_GET_MONEY, 'is_safe' => false],
        ACTION_BUY_EXPLOSIVES => ['name' => ACTION_BUY_EXPLOSIVES, 'is_safe' => false],
        ACTION_GET_3_FOOD => ['name' => ACTION_GET_3_FOOD, 'is_safe' => false],
        ACTION_GET_3_MEDICINE => ['name' => ACTION_GET_3_MEDICINE, 'is_safe' => false],
        ACTION_INCREASE_MORALE => ['name' => ACTION_INCREASE_MORALE, 'is_safe' => true],
        ACTION_BUY_POISON => ['name' => ACTION_BUY_POISON, 'is_safe' => false],
        ACTION_FORGE_FAKE_ID => ['name' => ACTION_FORGE_FAKE_ID, 'is_safe' => false],
        ACTION_USE_FIXER => ['name' => ACTION_USE_FIXER, 'is_safe' => true],
        // 0-star mission actions
        ACTION_WRITE_GRAFFITI => ['name' => ACTION_WRITE_GRAFFITI, 'is_safe' => true],
        ACTION_COMPLETE_OFFICERS_MANSION_MISSION => ['name' => ACTION_COMPLETE_OFFICERS_MANSION_MISSION, 'is_safe' => true],
        ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION => ['name' => ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION, 'is_safe' => true],
        // 1-star mission actions
        ACTION_INFILTRATE_FACTORY => ['name' => ACTION_INFILTRATE_FACTORY, 'is_safe' => true],
        ACTION_SABOTAGE_FACTORY => ['name' => ACTION_SABOTAGE_FACTORY, 'is_safe' => true],
        ACTION_DELIVER_2_INTEL => ['name' => ACTION_DELIVER_2_INTEL, 'is_safe' => true],
        ACTION_INSERT_MOLE => ['name' => ACTION_INSERT_MOLE, 'is_safe' => true],
        ACTION_RECOVER_MOLE => ['name' => ACTION_RECOVER_MOLE, 'is_safe' => true],
        ACTION_POISON_SHEPARDS => ['name' => ACTION_POISON_SHEPARDS, 'is_safe' => true],
        ACTION_COMPLETE_DOUBLE_AGENT_MISSION => ['name' => ACTION_COMPLETE_DOUBLE_AGENT_MISSION, 'is_safe' => true],
        // 2-star mission actions
        ACTION_DELIVER_2_WEAPONS => ['name' => ACTION_DELIVER_2_WEAPONS, 'is_safe' => true],
        ACTION_DELIVER_MONEY_AND_2_FOOD => ['name' => ACTION_DELIVER_MONEY_AND_2_FOOD, 'is_safe' => true],
        ACTION_DELIVER_3_EXPLOSIVES => ['name' => ACTION_DELIVER_3_EXPLOSIVES, 'is_safe' => true],
        ACTION_TRAIN_A_CRYPTOGRAPHER => ['name' => ACTION_TRAIN_A_CRYPTOGRAPHER, 'is_safe' => true],
        ACTION_PLANT_2_EXPLOSIVES => ['name' => ACTION_PLANT_2_EXPLOSIVES, 'is_safe' => true],
        ACTION_DELIVER_EXPLOSIVES_AND_WEAPON => ['name' => ACTION_DELIVER_EXPLOSIVES_AND_WEAPON, 'is_safe' => true],
        // 3-star mission actions
        ACTION_DISCOVER_THE_PLANS => ['name' => ACTION_DISCOVER_THE_PLANS, 'is_safe' => false],
        ACTION_DELIVER_2_POISON => ['name' => ACTION_DELIVER_2_POISON, 'is_safe' => true],
        ACTION_RECON_THE_BARRACKS => ['name' => ACTION_RECON_THE_BARRACKS, 'is_safe' => false],
        ACTION_BOMB_THE_BARRACKS => ['name' => ACTION_BOMB_THE_BARRACKS, 'is_safe' => true],
        ACTION_BRIBE_THE_CLERK => ['name' => ACTION_BRIBE_THE_CLERK, 'is_safe' => false],
        ACTION_KILL_THE_RESISTANCE_LEADER => ['name' => ACTION_KILL_THE_RESISTANCE_LEADER, 'is_safe' => true],
        ACTION_FREE_THE_RESISTANCE_LEADER => ['name' => ACTION_FREE_THE_RESISTANCE_LEADER, 'is_safe' => true],
        ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES => ['name' => ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES, 'is_safe' => true],
        ACTION_DESTROY_AA_GUN_WITH_WEAPON => ['name' => ACTION_DESTROY_AA_GUN_WITH_WEAPON, 'is_safe' => true],
    ];
}