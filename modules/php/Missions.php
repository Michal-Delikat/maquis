<?php
namespace Bga\Games\Maquis;

trait Missions {
    protected function configureMissions(string $missionAName, string $missionBName): void {
        $this->setSelectedMissions($missionAName, $missionBName);

        $missionsWithSpaces = [
            MISSION_OFFICERS_MANSION, 
            MISSION_SABOTAGE, 
            MISSION_INFILTRATION, 
            MISSION_GERMAN_SHEPARDS, 
            MISSION_UNDERGROUND_NEWSPAPER,
            MISSION_AID_THE_SPY,
            MISSION_DESTROY_THE_TRAIN,
            MISSION_CODED_MESSAGES,
            MISSION_BOMB_FOR_THE_OFFICER,
            MISSION_BOMB_THE_BARRACKS,
            MISSION_FREE_THE_RESISTANCE_LEADER,
            MISSION_DESTROY_AA_GUNS
        ];

        if (in_array($missionAName, $missionsWithSpaces)) {
            $this->addMissionSpace(MISSION_A_SPACE_A);
        }

        if (in_array($missionBName, $missionsWithSpaces)) {
            $this->addMissionSpace(MISSION_B_SPACE_A);
        }

        $missionNames = [$missionAName, $missionBName];

        if (in_array(MISSION_MILICE_PARADE_DAY, $missionNames)) {
            $this->addSpaceAction(RUE_BARADAT, ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION);
        }
        
        if (in_array(MISSION_OFFICERS_MANSION, $missionNames)) {
            $missionSpace = $missionAName === MISSION_OFFICERS_MANSION ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            foreach([RUE_BARADAT, 3, PONT_LEVEQUE] as $space) { 
                $this->addSpaceAction($space, ACTION_WRITE_GRAFFITI);
            }
            $this->addSpaceAction($missionSpace, ACTION_COMPLETE_OFFICERS_MANSION_MISSION);
        }

        if (in_array(MISSION_SABOTAGE, $missionNames)) {
            $missionSpace = $missionAName === MISSION_SABOTAGE ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_INFILTRATE_FACTORY);
        }

        if (in_array(MISSION_UNDERGROUND_NEWSPAPER, $missionNames)) {
            $missionSpace = $missionAName === MISSION_UNDERGROUND_NEWSPAPER ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_DELIVER_2_INTEL);
        }

        if (in_array(MISSION_INFILTRATION, $missionNames)) {
            $missionSpace = $missionAName === MISSION_INFILTRATION ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_INSERT_MOLE);
        }

        if (in_array(MISSION_GERMAN_SHEPARDS, $missionNames)) {
            $missionSpace = $missionAName === MISSION_GERMAN_SHEPARDS ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_POISON_SHEPARDS);
        }

        if (in_array(MISSION_AID_THE_SPY, $missionNames)) {
            $missionSpace = $missionAName === MISSION_AID_THE_SPY ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_DELIVER_2_WEAPONS);
        }

        if (in_array(MISSION_DESTROY_THE_TRAIN, $missionNames)) {
            $missionSpace = $missionAName === MISSION_DESTROY_THE_TRAIN ? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_DELIVER_3_EXPLOSIVES);
        }

        if (in_array(MISSION_CODED_MESSAGES, $missionNames)) {
            $missionSpace = $missionAName === MISSION_CODED_MESSAGES? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_TRAIN_A_CRYPTOGRAPHER);
        }

        if (in_array(MISSION_TAKE_OUT_THE_BRIDGES, $missionNames)) {
            $this->addSpaceAction(BLACK_MARKET, ACTION_PLANT_2_EXPLOSIVES);
        }

        if (in_array(MISSION_BOMB_FOR_THE_OFFICER, $missionNames)) {
            $missionSpace = $missionAName === MISSION_BOMB_FOR_THE_OFFICER? MISSION_A_SPACE_A : MISSION_B_SPACE_A;
            $this->addSpaceAction($missionSpace, ACTION_DELIVER_EXPLOSIVES_AND_WEAPON);
        }

        if ($missionBName === MISSION_MILICE_HQ) {
            $this->addSpaceAction(RUE_BARADAT, ACTION_DISCOVER_THE_PLANS);
            $this->setMorale(4, false);
        } else if ($missionBName === MISSION_BOMB_THE_BARRACKS) {
            $this->addSpaceAction(MISSION_B_SPACE_A, ACTION_RECON_THE_BARRACKS);
            $this->setActiveSoldiers(3);
        } else if ($missionBName === MISSION_FREE_THE_RESISTANCE_LEADER) {
            $this->addSpaceAction(MISSION_B_SPACE_A, ACTION_BRIBE_THE_CLERK);
        } else if ($missionBName === MISSION_DESTROY_AA_GUNS) {
            $this->placeTokens(RUE_BARADAT, 'aa_gun', 1, false);
            $this->placeTokens(BLACK_MARKET, 'aa_gun', 1, false);
            $this->placeTokens(LEFT_FIELD, 'aa_gun', 1, false);
            $this->placetokens(RIGHT_FIELD, 'aa_gun', 1, false);
            $this->addSpaceAction([RUE_BARADAT, BLACK_MARKET, LEFT_FIELD, RIGHT_FIELD, MISSION_B_SPACE_A], ACTION_DESTROY_AA_GUN_WITH_WEAPON);
            $this->addSpaceAction([RUE_BARADAT, BLACK_MARKET, LEFT_FIELD, RIGHT_FIELD, MISSION_B_SPACE_A], ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES);
        }
    }

    protected function addMissionSpace(int $spaceID): void {
        if (in_array((int) $spaceID, [MISSION_A_SPACE_A, MISSION_A_SPACE_B, MISSION_A_SPACE_C])) {
            static::DbQuery("
                INSERT INTO board (`space_id`, `space_name`) 
                VALUES ($spaceID, \"Mission A\");
            ");

            static::DbQuery("
                INSERT INTO board_path (`space_id_start`, `space_id_end`)
                VALUES (2, $spaceID), ($spaceID, 2);
            ");
        } else if (in_array((int) $spaceID, [MISSION_B_SPACE_A, MISSION_B_SPACE_B, MISSION_B_SPACE_C])) {
            static::DbQuery("
                INSERT INTO board (`space_id`, `space_name`) 
                VALUES ($spaceID, \"Mission B\");
            ");

            static::DbQuery("
                INSERT INTO board_path (`space_id_start`, `space_id_end`)
                VALUES (3, $spaceID), ($spaceID, 3);
            ");
        }
    }

    protected function completeMission(string $missionName, bool $majorSuccess = false): void {
        static::DbQuery("
            UPDATE components
            SET state = 'completed'
            WHERE name = 'mission_card_$missionName';
        ");

        $spaceIDs = $this->getSpaceIdsByMissionName($missionName);
        foreach ($spaceIDs as $spaceID) {
            $this->removeMissionSpace((int) $spaceID);
        }
        if ($majorSuccess) {
            $this->setPlayerScore($this->getPlayerScore() + 2);
        } else {
            $this->incrementPlayerScore();
        }
        
        $this->notify->all("missionCompleted", clienttranslate("Mission completed"), array(
            "missionName" => $missionName, 
            "playerScore" => $this->getPlayerScore(),
            "playerId" => $this->getCurrentPlayerId()
        ));

        if ($this->getIsMissionSelected(MISSION_ASSASSINATION) && !$this->getIsMissionCompleted(MISSION_ASSASSINATION) && $this->getPlacedMilice() <= 0) {
            $this->completeMission(MISSION_ASSASSINATION);
        }
    }
    
    protected function removeMissionSpace(int $spaceID) {
        static::DbQuery("
            DELETE FROM board_action
            WHERE space_id = $spaceID;
        ");
        
        static::DbQuery("
            DELETE FROM board
            WHERE space_id = $spaceID;    
        ");

        static::DbQuery("
            DELETE FROM board_path
            WHERE space_id_start = $spaceID OR space_id_end = $spaceID;
        ");
    }

    protected function getIsMissionCompleted(string $missionName): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT *
            FROM components
            WHERE name = 'mission_card_$missionName' AND state = 'completed';
        ");
    } 

    protected function getSpaceIdsByMissionName(string $missionName): array {
        $result = (array) $this->getCollectionFromDB("
            SELECT location
            FROM components
            WHERE name = 'mission_card_$missionName' AND location != 'off_board';
        ");

        return array_keys($result);
    }

    protected function setSelectedMissions(string $missionAName, string $missionBName): void {
        self::DbQuery("
            UPDATE components
            SET state = 'selected'
            WHERE name = 'mission_card_$missionAName' OR name = 'mission_card_$missionBName';"
        );

        self::DbQuery("
            UPDATE components
            SET location = 'mission_card_a'
            WHERE name = 'mission_card_$missionAName';
        ");

        self::DbQuery("
            UPDATE components
            SET location = 'mission_card_b'
            WHERE name = 'mission_card_$missionBName';
        ");
    }

    protected function getLocationByMissionName(string $missionName): string {
        return self::getUniqueValueFromDB("
            SELECT location
            FROM components
            WHERE name = 'mission_card_$missionName';
        ");
    }

    protected function getSelectedMissions(): array {
        return (array) array_values($this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE 'mission_card%' AND (state = 'selected' OR state = 'completed');
        "));
    }

    protected function getIsMissionSelected(string $missionName): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT *
            FROM components
            WHERE name = 'mission_card_$missionName' AND (state = 'selected' OR state = 'completed');
        ");
    }

    protected function getCompletedMissions(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE 'mission_card%' AND state = 'completed';
        ");
    }

    protected function getIsThreeStarMissionSelected(): bool {
        return (bool) self::getUniqueValueFromDb("
            SELECT name
            FROM components 
            WHERE (state = 'selected' OR state = 'completed') AND name IN ('mission_card_milice_hq', 'mission_card_bomb_the_barracks', 'mission_card_free_the_resistance_leader', 'mission_card_destroy_aa_guns')
            LIMIT 1;
        ");
    }
}