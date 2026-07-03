<?php
namespace Bga\Games\Maquis;

trait Missions {
    protected function configureMissions(string $missionAName, string $missionBName): void {
        $this->setSelectedMissions($missionAName, $missionBName);

        $missionNames = [$missionAName, $missionBName];

        $missionsWithSpaces = [
            MISSION_OFFICERS_MANSION, MISSION_SABOTAGE, MISSION_INFILTRATION,
            MISSION_GERMAN_SHEPARDS, MISSION_UNDERGROUND_NEWSPAPER, MISSION_AID_THE_SPY,
            MISSION_DESTROY_THE_TRAIN, MISSION_CODED_MESSAGES, MISSION_BOMB_FOR_THE_OFFICER,
            MISSION_BOMB_THE_BARRACKS, MISSION_FREE_THE_RESISTANCE_LEADER, MISSION_DESTROY_AA_GUNS,
        ];

        if (in_array($missionAName, $missionsWithSpaces)) {
            $this->addMissionSpace(MISSION_A_SPACE_A);
        }
        if (in_array($missionBName, $missionsWithSpaces)) {
            $this->addMissionSpace(MISSION_B_SPACE_A);
        }

        $missionSpaceActions = [
            MISSION_OFFICERS_MANSION      => ACTION_COMPLETE_OFFICERS_MANSION_MISSION,
            MISSION_SABOTAGE              => ACTION_INFILTRATE_FACTORY,
            MISSION_UNDERGROUND_NEWSPAPER => ACTION_DELIVER_2_INTEL,
            MISSION_INFILTRATION          => ACTION_INSERT_MOLE,
            MISSION_GERMAN_SHEPARDS       => ACTION_POISON_SHEPARDS,
            MISSION_AID_THE_SPY           => ACTION_DELIVER_2_WEAPONS,
            MISSION_DESTROY_THE_TRAIN     => ACTION_DELIVER_3_EXPLOSIVES,
            MISSION_CODED_MESSAGES        => ACTION_TRAIN_A_CRYPTOGRAPHER,
            MISSION_BOMB_FOR_THE_OFFICER  => ACTION_DELIVER_EXPLOSIVES_AND_WEAPON,
        ];

        foreach ($missionSpaceActions as $mission => $action) {
            if ($mission === $missionAName) {
                $this->addSpaceAction(MISSION_A_SPACE_A, $action);
            } else if ($mission === $missionBName) {
                $this->addSpaceAction(MISSION_B_SPACE_A, $action);
            }
        }

        if (in_array(MISSION_MILICE_PARADE_DAY, $missionNames)) {
            $this->addSpaceAction(RUE_BARADAT, ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION);
        }

        if (in_array(MISSION_OFFICERS_MANSION, $missionNames)) {
            foreach ([RUE_BARADAT, PONT_DU_NORD, PONT_LEVEQUE] as $space) {
                $this->addSpaceAction($space, ACTION_WRITE_GRAFFITI);
            }
        }

        if (in_array(MISSION_TAKE_OUT_THE_BRIDGES, $missionNames)) {
            $this->addSpaceAction(BLACK_MARKET, ACTION_PLANT_2_EXPLOSIVES);
        }

        match ($missionBName) {
            MISSION_MILICE_HQ                  => $this->setupMiliceHQ(),
            MISSION_BOMB_THE_BARRACKS          => $this->setupBombTheBarracks(),
            MISSION_FREE_THE_RESISTANCE_LEADER => $this->addSpaceAction(MISSION_B_SPACE_A, ACTION_BRIBE_THE_CLERK),
            MISSION_DESTROY_AA_GUNS            => $this->setupDestroyAAGuns(),
        };
    }

    protected function setupMiliceHQ(): void {
        $this->addSpaceAction(RUE_BARADAT, ACTION_DISCOVER_THE_PLANS);
        $this->setMorale(4, false);
    }

    protected function setupBombTheBarracks(): void {
        $this->addSpaceAction(MISSION_B_SPACE_A, ACTION_RECON_THE_BARRACKS);
        $this->setActiveSoldiers(3);
    }

    protected function setupDestroyAAGuns(): void {
        $outerSpaces = [RUE_BARADAT, BLACK_MARKET, LEFT_FIELD, RIGHT_FIELD];
        foreach ($outerSpaces as $space) {
            $this->placeTokens($space, 'aa_gun', 1, false);
        }
        $allSpaces = [...$outerSpaces, MISSION_B_SPACE_A];
        $this->addSpaceAction($allSpaces, ACTION_DESTROY_AA_GUN_WITH_WEAPON);
        $this->addSpaceAction($allSpaces, ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES);
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