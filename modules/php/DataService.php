<?php

namespace Bga\Games\Maquis;

require_once("constants.inc.php");

class DataService {
    public static function setupRoundData(): string {
        return '
            INSERT INTO round_data (active_space)
            VALUES
            (0);
        ';
    }

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
            INSERT INTO action (action_id, action_name, action_description, is_safe)
            VALUES
            (1, "' . ACTION_GET_WEAPON . '", "Pay 1 money to gain 1 weapon", FALSE),
            (2, "' . ACTION_GET_INTEL . '", "Gain 1 intel", FALSE),
            (3, "' . ACTION_AIRDROP . '", "Airdop supplies onto an empty field", FALSE),
            (4, "' . ACTION_GET_MEDICINE . '", "Gain 1 medicine", FALSE),
            (5, "' . ACTION_PAY_FOR_MORALE . '", "Pay 1 food and 1 medicine to gain 1 morale", TRUE),
            (6, "' . ACTION_GET_MONEY_FOR_FOOD . '", "Pay 1 food to gain 1 money and lose 1 morale", FALSE),
            (7, "' . ACTION_GET_SPARE_ROOM . '", "Pay 2 money to gain a spare room", TRUE),
            (8, "' . ACTION_GET_FOOD . '", "Gain 1 food", FALSE),
            (9, "' . ACTION_GET_WORKER . '", "Pay 1 food to gain 1 worker", FALSE),
            (10, "' . ACTION_GET_MONEY_FOR_MEDICINE . '", "Pay 1 medicine to gain 1 money and lose 1 morale", FALSE),
            (11, "' . ACTION_COLLECT_ITEMS . '", "Collect items", FALSE),
            (12, "' . ACTION_WRITE_GRAFFITI . '", "Write anti-fascist graffiti", TRUE),
            (13, "' . ACTION_COMPLETE_OFFICERS_MANSION_MISSION . '", "Complete Mission", TRUE),
            (14, "' . ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION . '", "Complete Mission", TRUE),
            (15, "' . ACTION_GET_MONEY . '", "Gain 1 money", FALSE),
            (16, "' . ACTION_GET_EXPLOSIVES . '", "Pay 1 medicine to gain 1 explosives", FALSE),
            (17, "' . ACTION_GET_3_FOOD . '", "Gain 3 food", FALSE),
            (18, "' . ACTION_GET_3_MEDICINE . '", "Gain 3 medicine", FALSE),
            (19, "' . ACTION_INCREASE_MORALE . '", "Increase morale by 1", TRUE),
            (20, "' . ACTION_GET_POISON . '", "Pay 2 medicine to gain 1 poison", FALSE),
            (21, "' . ACTION_GET_FAKE_ID . '", "Pay 1 money and 2 intel to gain 1 fake id", FALSE),
            (22, "' . ACTION_INFILTRATE_FACTORY . '", "Infiltrate Factory", TRUE),
            (23, "' . ACTION_SABOTAGE_FACTORY . '", "Sabotage Factory", TRUE),
            (24, "' . ACTION_DELIVER_INTEL . '", "Deliver 2 Intel", TRUE),
            (25, "' . ACTION_INSERT_MOLE . '", "Insert Mole", TRUE),
            (26, "' . ACTION_RECOVER_MOLE . '", "Recover mole and complete mission", TRUE),
            (27, "' . ACTION_POISON_SHEPARDS . '", "Poison Shepards", TRUE),
            (28, "' . ACTION_COMPLETE_DOUBLE_AGENT_MISSION . '", "Complete the mission", TRUE);
        ';
    }

    public static function setupBoardActions(): string {
        return '
            INSERT INTO board_action (space_id, action_id)
            VALUES
            (2, 1),
            (4, 2),(4, 3),
            (5, 4),
            (6, 5),
            (7, 6), (7, 10),
            (8, 7),
            (9, 2),(9, 3),
            (10, 7),
            (12, 8),
            (13, 7),
            (14, 11),
            (15, 9),
            (17, 11);
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
            ("milice_1", "off_board", "NaN"),
            ("milice_2", "off_board", "NaN"),
            ("milice_3", "off_board", "NaN"),
            ("milice_4", "off_board", "NaN"),
            ("milice_5", "off_board", "NaN"),
            ("soldier_1", "off_board", "inactive"),
            ("soldier_2", "off_board", "inactive"),
            ("soldier_3", "off_board", "inactive"),
            ("soldier_4", "off_board", "inactive"),
            ("soldier_5", "off_board", "inactive");
        ';
    }
}