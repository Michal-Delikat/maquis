<?php

const ST_BGA_GAME_SETUP = 1;

const ST_PLAYER_PLACE_WORKER = 2;
const ST_PLAYER_ACTIVATE_WORKER = 3;
const ST_PLAYER_TAKE_ACTION = 4;
const ST_PLAYER_AIRDROP_SELECT_SUPPLIES = 5;
const ST_PLAYER_AIRDROP_SELECT_FIELD = 6;   
const ST_PLAYER_SELECT_ROOM = 7;
const ST_PLAYER_SHOOT_MILICE = 8;
const ST_PLAYER_REMOVE_WORKER = 9;
const ST_PLAYER_REMOVE_BRIDGE = 10;
const ST_PLAYER_USE_FIXER = 11;
const ST_PLAYER_PLACE_FAKE_ID = 12;

const ST_GAME_PLACE_PATROL = 60;
const ST_GAME_NEXT_WORKER = 63;

const ST_GAME_ROUND_END = 90;

const ST_PSEUDO_GAME_END = 98;
const ST_BGA_GAME_END = 99;

const RUE_BARADAT = 1;
const FENCE = 2;
const PONT_DU_NORD = 3;
const RADIO_B = 4;
const DOCTOR = 5;
const POOR_DISTRICT = 6;
const BLACK_MARKET = 7;
const RIGHT_TOP_SPARE_ROOM = 8;
const RADIO_A = 9;
const LEFT_SPARE_ROOM = 10;
const PONT_LEVEQUE = 11;
const GROCER = 12;
const RIGHT_BOTTOM_SPARE_ROOM = 13;
const LEFT_FIELD = 14;
const CAFE = 15;
const SAFE_HOUSE = 16;
const RIGHT_FIELD = 17;
const MISSION_A_SPACE_A = 18;
const MISSION_A_SPACE_B = 19;
const MISSION_A_SPACE_C = 20;
const MISSION_B_SPACE_A = 21;
const MISSION_B_SPACE_B = 22;
const MISSION_B_SPACE_C = 23; 
const TOP_BRIDGE = 24;
const BOTTOM_BRIDGE = 25;
const FIXER = 26;

const MISSION_MILICE_PARADE_DAY = 'milice_parade_day';
const MISSION_OFFICERS_MANSION = 'officers_mansion';

const MISSION_SABOTAGE = 'sabotage';
const MISSION_UNDERGROUND_NEWSPAPER = 'underground_newspaper';
const MISSION_INFILTRATION = 'infiltration';
const MISSION_GERMAN_SHEPARDS = 'german_shepards';
const MISSION_DOUBLE_AGENT = 'double_agent';

const MISSION_AID_THE_SPY = 'aid_the_spy';
const MISSION_ASSASSINATION = 'assassination';
const MISSION_DESTROY_THE_TRAIN = 'destroy_the_train';
const MISSION_LIBERATE_THE_TOWN = 'liberate_the_town';
const MISSION_CODED_MESSAGES = 'coded_messages';
const MISSION_TAKE_OUT_THE_BRIDGES = 'take_out_the_bridges';
const MISSION_BOMB_FOR_THE_OFFICER = 'bomb_for_the_officer';

const MISSION_MILICE_HQ = 'milice_hq';
const MISSION_BOMB_THE_BARRACKS = 'bomb_the_barracks';
const MISSION_FREE_THE_RESISTANCE_LEADER = 'free_the_resistance_leader';
const MISSION_DESTROY_AA_GUNS = 'destroy_aa_guns';

const ACTION_GET_FOOD = 'get_food';
const ACTION_GET_MEDICINE = 'get_medicine';
const ACTION_GET_MONEY_FOR_FOOD = 'get_money_for_food';
const ACTION_GET_MONEY_FOR_MEDICINE = 'get_money_for_medicine';
const ACTION_PAY_FOR_MORALE = 'pay_for_morale';
const ACTION_GET_INTEL = 'get_intel';
const ACTION_BUY_WEAPON = 'buy_weapon';
const ACTION_GET_WORKER = 'get_worker';
const ACTION_COLLECT_ITEMS = 'collect_items';
const ACTION_GET_SPARE_ROOM = 'get_spare_room';
const ACTION_RETURN = 'return';
const ACTION_AIRDROP_FOOD = 'airdrop_food';
const ACTION_AIRDROP_MONEY = 'airdrop_money';
const ACTION_AIRDROP_WEAPON = 'airdrop_weapon';

const ACTION_GET_MONEY = 'get_money';
const ACTION_BUY_EXPLOSIVES = 'buy_explosives';
const ACTION_GET_3_FOOD = 'get_3_food';
const ACTION_GET_3_MEDICINE = 'get_3_medicine';
const ACTION_INCREASE_MORALE = 'increase_morale';
const ACTION_BUY_POISON = 'buy_poison';
const ACTION_FORGE_FAKE_ID = 'forge_fake_id';
const ACTION_USE_FIXER = 'use_fixer';

const ACTION_WRITE_GRAFFITI = 'write_graffiti';
const ACTION_COMPLETE_OFFICERS_MANSION_MISSION = 'complete_officers_mansion_mission';
const ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION = 'complete_milice_parade_day_mission';

const ACTION_INFILTRATE_FACTORY = 'infiltrate_factory';
const ACTION_SABOTAGE_FACTORY = 'sabotage_factory';
const ACTION_DELIVER_INTEL = 'deliver_intel';
const ACTION_INSERT_MOLE = 'insert_mole';
const ACTION_RECOVER_MOLE = 'recover_mole';
const ACTION_POISON_SHEPARDS = 'poison_shepards';
const ACTION_COMPLETE_DOUBLE_AGENT_MISSION = 'complete_double_agent_mission';

const ACTION_DELIVER_2_WEAPONS = 'deliver_2_weapons';
const ACTION_DELIVER_MONEY_AND_2_FOOD = 'deliver_money_and_2_food';
const ACTION_DELIVER_3_EXPLOSIVES = 'deliver_3_explosives';
const ACTION_TRAIN_A_CRYPTOGRAPHER = 'train_a_cryptographer';
const ACTION_DELIVER_2_EXPLOSIVES = 'deliver_2_explosives';
const ACTION_DELIVER_EXPLOSIVES_AND_WEAPON = 'deliver_explosives_and_weapon';

const ACTION_DISCOVER_THE_PLANS = 'discover_the_plans';
const ACTION_DELIVER_2_POISON = 'deliver_2_poison';
const ACTION_RECON_THE_BARRACKS = 'recon_the_barracks';
const ACTION_BOMB_THE_BARRACKS = 'bomb_the_barracks';
const ACTION_BRIBE_THE_CLERK = 'bribe_the_clerk';
const ACTION_KILL_THE_RESISTANCE_LEADER = 'kill_the_resistance_leader';
const ACTION_FREE_THE_RESISTANCE_LEADER = 'free_the_resistance_leader';
const ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES = 'destroy_aa_gun_with_explosives';
const ACTION_DESTROY_AA_GUN_WITH_WEAPON = 'destroy_aa_gun_with_weapon';

const RESOURCE_FOOD = 'food';
const RESOURCE_MEDICINE = 'medicine';
const RESOURCE_WEAPON = 'weapon';
const RESOURCE_INTEL = 'intel';
const RESOURCE_MONEY = 'money';
const RESOURCE_EXPLOSIVES = 'explosives';
const RESOURCE_POISON = 'poison';
const RESOURCE_FAKE_ID = 'fake_id';

const ROOM_INFORMANT = 'informant';
const ROOM_COUNTERFEITER = 'counterfeiter';
const ROOM_SAFE_HOUSE = 'safe_house';
const ROOM_CHEMISTS_LAB = 'chemists_lab';
const ROOM_SMUGGLER = 'smuggler';
const ROOM_PROPAGANDIST = 'propagandist';
const ROOM_FIXER = 'fixer';
const ROOM_PHARMACIST = 'pharmacist';
const ROOM_FORGER = 'forger';

const TOKEN_AA_GUN = 'aa_gun';
const TOKEN_FAKE_ID = 'fake_id';

?>