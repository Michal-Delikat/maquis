<?php

namespace Bga\Games\Maquis;

require_once("constants.inc.php");

class DataService {

    public static function setupBoard(): string {
        return '
            INSERT INTO board (space_id, space_name, is_safe, is_field)
            VALUES
            (1, "Rue Baradat", FALSE, FALSE),
            (2, "Fence", FALSE, FALSE),
            (3, "Pont du Nord", FALSE, FALSE),
            (4, "Radio B", FALSE, FALSE),
            (5, "Doctor", FALSE, FALSE),
            (6, "Poor District", FALSE, FALSE),
            (7, "Black Market", FALSE, FALSE),
            (8, "Spare Room", FALSE, FALSE),
            (9, "Radio A", FALSE, FALSE),
            (10, "Spare Room", FALSE, FALSE),
            (11, "Pont Leveque", FALSE, FALSE),
            (12, "Grocer", FALSE, FALSE),
            (13, "Spare Room", FALSE, FALSE),
            (14, "Field", FALSE, TRUE),
            (15, "Cafe", FALSE, FALSE),
            (16, "Safe House", TRUE, FALSE),
            (17, "Field", FALSE, TRUE);
        ';
    }

    public static function setupActions(): string {
        return '
            INSERT INTO action (action_name, is_safe)
            VALUES
            (\'' . ACTION_GET_WEAPON . '\', FALSE),
            (\'' . ACTION_GET_INTEL . '\', FALSE),
            (\'' . ACTION_AIRDROP . '\', FALSE),
            (\'' . ACTION_GET_MEDICINE . '\', FALSE),
            (\'' . ACTION_PAY_FOR_MORALE . '\', TRUE),
            (\'' . ACTION_GET_MONEY_FOR_FOOD . '\', FALSE),
            (\'' . ACTION_GET_SPARE_ROOM . '\', TRUE),
            (\'' . ACTION_GET_FOOD . '\', FALSE),
            (\'' . ACTION_GET_WORKER . '\', FALSE),
            (\'' . ACTION_GET_MONEY_FOR_MEDICINE . '\', FALSE),
            (\'' . ACTION_COLLECT_ITEMS . '\', FALSE),
            (\'' . ACTION_WRITE_GRAFFITI . '\', TRUE),
            (\'' . ACTION_COMPLETE_OFFICERS_MANSION_MISSION . '\', TRUE),
            (\'' . ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION . '\', TRUE),
            (\'' . ACTION_GET_MONEY . '\', FALSE),
            (\'' . ACTION_GET_EXPLOSIVES . '\', FALSE),
            (\'' . ACTION_GET_3_FOOD . '\', FALSE),
            (\'' . ACTION_GET_3_MEDICINE . '\', FALSE),
            (\'' . ACTION_INCREASE_MORALE . '\', TRUE),
            (\'' . ACTION_INFILTRATE_FACTORY . '\', TRUE),
            (\'' . ACTION_SABOTAGE_FACTORY . '\', TRUE),
            (\'' . ACTION_DELIVER_INTEL . '\', TRUE),
            (\'' . ACTION_INSERT_MOLE . '\', TRUE),
            (\'' . ACTION_RECOVER_MOLE . '\', TRUE),
            (\'' . ACTION_POISON_SHEPARDS . '\', TRUE),
            (\'' . ACTION_COMPLETE_DOUBLE_AGENT_MISSION . '\', TRUE);
        ';
    }

    public static function setupBoardActions(): string {
        return '
            INSERT INTO board_action (space_id, action_name)
            VALUES
            (2, \'' . ACTION_GET_WEAPON . '\'),
            (4, \'' . ACTION_GET_INTEL . '\'),(4, \'' . ACTION_AIRDROP . '\'),
            (5, \'' . ACTION_GET_MEDICINE . '\'),
            (6, \'' . ACTION_PAY_FOR_MORALE . '\'),
            (7, \'' . ACTION_GET_MONEY_FOR_FOOD . '\'),(7, \'' . ACTION_GET_MONEY_FOR_MEDICINE . '\'),
            (8, \'' . ACTION_GET_SPARE_ROOM . '\'),
            (9, \'' . ACTION_GET_INTEL . '\'),(9, \'' . ACTION_AIRDROP . '\'),
            (10, \'' . ACTION_GET_SPARE_ROOM . '\'),
            (12, \'' . ACTION_GET_FOOD . '\'),
            (13, \'' . ACTION_GET_SPARE_ROOM . '\'),
            (14, \'' . ACTION_COLLECT_ITEMS . '\'),
            (15, \'' . ACTION_GET_WORKER . '\'),
            (17, \'' . ACTION_COLLECT_ITEMS . '\');
        ';
    }

    public static function setupBoardPaths(): string {
        return '
            INSERT INTO board_path (space_id_start, space_id_end)
            VALUES
            (1, 2),
            (1, 5),

            (2, 1),
            (2, 6),

            (3, 6),
            (3, 7),

            (4, 7),
            (4, 8),

            (5, 1),
            (5, 9),
            (5, 10),
            (5, 11),

            (6, 2),
            (6, 3),
            (6, 7),
            (6, 11),

            (7, 3),
            (7, 4),
            (7, 6),
            (7, 8),
            (7, 12),

            (8, 4),
            (8, 7),

            (9, 5),
            (9, 10),

            (10, 5),
            (10, 9),

            (11, 5),
            (11, 6),
            (11, 16),

            (12, 7),
            (12, 13),
            (12, 16),

            (13, 12),

            (14, 15),

            (15, 14),
            (15, 16),

            (16, 11),
            (16, 12),
            (16, 15),
            (16, 17),

            (17, 16);
        ';
    }

    public static function setupComponents(): string {
        return '
            INSERT INTO components (name, location, state)
            VALUES
            ("dark_lady_location", "off_board", "available"),
            ("room_' . ROOM_INFORMANT . '", "off_board", "available"),
            ("room_' . ROOM_COUNTERFEITER . '", "off_board", "available"),
            ("room_' . ROOM_SAFE_HOUSE . '", "off_board", "available"),
            ("room_' . ROOM_CHEMISTS_LAB . '", "off_board", "available"),
            ("room_' . ROOM_SMUGGLER . '", "off_board", "available"),
            ("room_' . ROOM_PROPAGANDIST . '", "off_board", "available"),
            ("mission_card_' . MISSION_MILICE_PARADE_DAY . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_OFFICERS_MANSION . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_SABOTAGE . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_UNDERGROUND_NEWSPAPER . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_INFILTRATION . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_GERMAN_SHEPARDS . '", "off_board", "not_selected"),
            ("mission_card_' . MISSION_DOUBLE_AGENT . '", "off_board", "not_selected"),
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
            ("round_marker", "0", "NaN"),
            ("morale_marker", "6", "NaN"),
            ("soldier_marker", "0", "NaN"),
            ("resistance_1", "safe_house", "active"),
            ("resistance_2", "safe_house", "active"),
            ("resistance_3", "safe_house", "active"),
            ("resistance_4", "cafe", "inactive"),
            ("resistance_5", "cafe", "inactive"),
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
        ';
    }
}