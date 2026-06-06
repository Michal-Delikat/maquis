<?php

require_once("constants.inc.php");

const PATROL_CARD_ITEMS = [
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 1, 'space_a' => 12, 'space_b' => 6, 'space_c' => 5], // 1. Grocer / Poor District
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 2, 'space_a' => 4, 'space_b' => 12, 'space_c' => 7], // 2. Radio B / Grocer
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 3, 'space_a' => 11, 'space_b' => 3, 'space_c' => 5], // 3. Pont Levaque / Pont Du Nord
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 4, 'space_a' => 4, 'space_b' => 1, 'space_c' => 11], // 4. Radio B / Rue Baradat
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 5, 'space_a' => 11, 'space_b' => 7, 'space_c' => 5], // 5. Pont Levaque / Black Market
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 6, 'space_a' => 9, 'space_b' => 1, 'space_c' => 12], // 6. Radio A / Rue Baradat
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 7, 'space_a' => 9, 'space_b' => 11, 'space_c' => 7], // 7. Radio A / Pont Levaque
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 8, 'space_a' => 2, 'space_b' => 1, 'space_c' => 6],  // 8. Fence / Rue Baradat
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 9, 'space_a' => 12, 'space_b' => 3, 'space_c' => 2], // 9. Grocer / Pont Du Nord
    ['type' => 'PATROL', 'nbr' => 1, 'type_arg' => 10, 'space_a' => 2, 'space_b' => 3, 'space_c' => 11] // 10. Fence / Pont Du Nord
];

const ROOM_DESCRIPTIONS = [
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

const ACTIONS = [
    ACTION_GET_FOOD => ['name' => ACTION_GET_FOOD, 'is_safe' => false],
    ACTION_GET_MEDICINE => ['name' => ACTION_GET_MEDICINE, 'is_safe' => false],
    ACTION_GET_MONEY_FOR_FOOD => ['name' => ACTION_GET_MONEY_FOR_FOOD, 'is_safe' => false],
    ACTION_GET_MONEY_FOR_MEDICINE => ['name' => ACTION_GET_MONEY_FOR_MEDICINE, 'is_safe' => false],
    ACTION_PAY_FOR_MORALE => ['name' => ACTION_PAY_FOR_MORALE, 'is_safe' => true],
    ACTION_AIRDROP => ['name' => ACTION_AIRDROP, 'is_safe' => false],
    ACTION_GET_INTEL => ['name' => ACTION_GET_INTEL, 'is_safe' => false],
    ACTION_BUY_WEAPON => ['name' => ACTION_BUY_WEAPON, 'is_safe' => false],
    ACTION_GET_WORKER => ['name' => ACTION_GET_WORKER, 'is_safe' => false],
    ACTION_COLLECT_ITEMS => ['name' => ACTION_COLLECT_ITEMS, 'is_safe' => false],
    ACTION_GET_SPARE_ROOM => ['name' => ACTION_GET_SPARE_ROOM, 'is_safe' => true],

    ACTION_GET_MONEY => ['name' => ACTION_GET_MONEY, 'is_safe' => false],
    ACTION_BUY_EXPLOSIVES => ['name' => ACTION_BUY_EXPLOSIVES, 'is_safe' => false],
    ACTION_GET_3_FOOD => ['name' => ACTION_GET_3_FOOD, 'is_safe' => false],
    ACTION_GET_3_MEDICINE => ['name' => ACTION_GET_3_MEDICINE, 'is_safe' => false],
    ACTION_INCREASE_MORALE => ['name' => ACTION_INCREASE_MORALE, 'is_safe' => true],
    ACTION_BUY_POISON => ['name' => ACTION_BUY_POISON, 'is_safe' => false],
    ACTION_FORGE_FAKE_ID => ['name' => ACTION_FORGE_FAKE_ID, 'is_safe' => false],

    ACTION_WRITE_GRAFFITI => ['name' => ACTION_WRITE_GRAFFITI, 'is_safe' => true],
    ACTION_COMPLETE_OFFICERS_MANSION_MISSION => ['name' => ACTION_COMPLETE_OFFICERS_MANSION_MISSION, 'is_safe' => true],
    ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION => ['name' => ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION, 'is_safe' => true],
    
    ACTION_INFILTRATE_FACTORY => ['name' => ACTION_INFILTRATE_FACTORY, 'is_safe' => true],
    ACTION_SABOTAGE_FACTORY => ['name' => ACTION_SABOTAGE_FACTORY, 'is_safe' => true],
    ACTION_DELIVER_INTEL => ['name' => ACTION_DELIVER_INTEL, 'is_safe' => true],
    ACTION_INSERT_MOLE => ['name' => ACTION_INSERT_MOLE, 'is_safe' => true],
    ACTION_RECOVER_MOLE => ['name' => ACTION_RECOVER_MOLE, 'is_safe' => true],
    ACTION_POISON_SHEPARDS => ['name' => ACTION_POISON_SHEPARDS, 'is_safe' => true],
    ACTION_COMPLETE_DOUBLE_AGENT_MISSION => ['name' => ACTION_COMPLETE_DOUBLE_AGENT_MISSION, 'is_safe' => true],

    ACTION_DELIVER_2_WEAPONS => ['name' => ACTION_DELIVER_2_WEAPONS, 'is_safe' => true],
    ACTION_DELIVER_MONEY_AND_2_FOOD => ['name' => ACTION_DELIVER_MONEY_AND_2_FOOD, 'is_safe' => true],
    ACTION_DELIVER_3_EXPLOSIVES => ['name' => ACTION_DELIVER_3_EXPLOSIVES, 'is_safe' => true],
    ACTION_TRAIN_A_CRYPTOGRAPHER => ['name' => ACTION_TRAIN_A_CRYPTOGRAPHER, 'is_safe' => false],
    ACTION_DELIVER_2_EXPLOSIVES => ['name' => ACTION_DELIVER_2_EXPLOSIVES, 'is_safe' => true],
    ACTION_DELIVER_EXPLOSIVES_AND_WEAPON => ['name' => ACTION_DELIVER_EXPLOSIVES_AND_WEAPON, 'is_safe' => true],

    ACTION_DISCOVER_THE_PLANS => ['name' => ACTION_DISCOVER_THE_PLANS, 'is_safe' => false],
    ACTION_DELIVER_2_POISON => ['name' => ACTION_DELIVER_2_POISON, 'is_safe' => true],
    ACTION_RECON_THE_BARRACKS => ['name' => ACTION_RECON_THE_BARRACKS, 'is_safe' => false],
    ACTION_BOMB_THE_BARRACKS => ['name' => ACTION_BOMB_THE_BARRACKS, 'is_safe' => true],
    ACTION_BRIBE_THE_CLERK => ['name' => ACTION_BRIBE_THE_CLERK, 'is_safe' => false]
];
