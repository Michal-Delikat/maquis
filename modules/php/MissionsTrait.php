<?php
namespace Bga\Games\Maquis;
trait MissionsTrait {
    protected function configureMissions(string $missionAName, string $missionBName): void {
        $this->setSelectedMissions($missionAName, $missionBName);

        $missionsWithSpaces = [
            MISSION_OFFICERS_MANSION, 
            MISSION_SABOTAGE, 
            MISSION_INFILTRATION, 
            MISSION_GERMAN_SHEPARDS, 
            MISSION_UNDERGROUND_NEWSPAPER
        ];

        if (in_array($missionAName, $missionsWithSpaces)) {
            $this->addMissionSpace(18, $missionAName);
        }

        if (in_array($missionBName, $missionsWithSpaces)) {
            $this->addMissionSpace(21, $missionBName);
        }

        $missionNames = [$missionAName, $missionBName];

        if (in_array(MISSION_MILICE_PARADE_DAY, $missionNames)) {
            $this->addSpaceAction(1, ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION);
        }
        
        if (in_array(MISSION_OFFICERS_MANSION, $missionNames)) {
            $missionSpace = $missionAName === MISSION_OFFICERS_MANSION ? 18 : 21;
            foreach([1, 3, 11] as $space) { 
                $this->addSpaceAction($space, ACTION_WRITE_GRAFFITI);
            }
            $this->addSpaceAction($missionSpace, ACTION_COMPLETE_OFFICERS_MANSION_MISSION);
        }

        if (in_array(MISSION_SABOTAGE, $missionNames)) {
            $missionSpace = $missionAName === MISSION_SABOTAGE ? 18 : 21;
            $this->addSpaceAction($missionSpace, ACTION_INFILTRATE_FACTORY);
        }

        if (in_array(MISSION_UNDERGROUND_NEWSPAPER, $missionNames)) {
            $missionSpace = $missionAName === MISSION_UNDERGROUND_NEWSPAPER ? 18 : 21;
            $this->addSpaceAction($missionSpace, ACTION_DELIVER_INTEL);
        }

        if (in_array(MISSION_INFILTRATION, $missionNames)) {
            $missionSpace = $missionAName === MISSION_INFILTRATION ? 18 : 21;
            $this->addSpaceAction($missionSpace, ACTION_INSERT_MOLE);
        }

        if (in_array(MISSION_GERMAN_SHEPARDS, $missionNames)) {
            $missionSpace = $missionAName === MISSION_GERMAN_SHEPARDS ? 18 : 21;
            $this->addSpaceAction($missionSpace, ACTION_POISON_SHEPARDS);
        }
    }

    protected function addMissionSpace(int $spaceID, string $missionName): void {
        // $missionID = $this->getMissionIdByMissionName($missionName);

        if (in_array((int) $spaceID, [18, 19, 20])) {
            static::DbQuery("
                INSERT INTO board (`space_id`, `space_name`) 
                VALUES ($spaceID, \"Mission A\");
            ");

            static::DbQuery("
                INSERT INTO board_path (`space_id_start`, `space_id_end`)
                VALUES (2, $spaceID), ($spaceID, 2);
            ");
        } else if (in_array((int) $spaceID, [21, 22, 23])) {
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

    protected function completeMission(string $missionName): void {
        static::DbQuery("
            UPDATE components
            SET state = 'completed'
            WHERE name = 'mission_card_$missionName';
        ");

        $spaceIDs = $this->getSpaceIdsByMissionName($missionName);
        foreach ($spaceIDs as $spaceID) {
            $this->removeMissionSpace((int) $spaceID);
        }

        $this->incrementPlayerScore();

        $this->notify->all("missionCompleted", clienttranslate("Mission completed"), array(
            "missionName" => $missionName, 
            "playerScore" => $this->getPlayerScore(), 
            "playerId" => $this->getActivePlayerId()
        ));
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

    protected function getSelectedMissions(): array {
        return (array) $this->getCollectionFromDb("
            SELECT name, location
            FROM components
            WHERE name LIKE 'mission_card%' AND (state = 'selected' OR state = 'completed');
        ");
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
}