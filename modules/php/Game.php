<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * Game.php
 *
 */

declare(strict_types=1);

namespace Bga\Games\Maquis;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

require_once("DataService.php");
require_once("constants.inc.php");

const BOARD = 'BOARD_STATE';

class Game extends \Table {
    private array $PATROL_CARD_ITEMS;
    private mixed $patrol_cards;

    /**
     * Your global variables labels:
     *
     * Here, you can assign labels to global variables you are using for this game. You can use any number of global
     * variables with IDs between 10 and 99. If your game has options (variants), you also have to associate here a
     * label to the corresponding ID in `gameoptions.inc.php`.
     *
     * NOTE: afterward, you can get/set the global variables with `getGameStateValue`, `setGameStateInitialValue` or
     * `setGameStateValue` functions.
     */
    public function __construct() {
        parent::__construct();
        require('material.inc.php');

        $this->PATROL_CARD_ITEMS = PATROL_CARD_ITEMS;
        
        $this->initGameStateLabels([
            "my_first_global_variable" => 10,
            "my_second_global_variable" => 11,
            "my_first_game_variant" => 100,
            "my_second_game_variant" => 101,
        ]);

        $this->patrol_cards = $this->getNew("module.common.deck");  
        $this->patrol_cards->init("patrol_card");
        $this->patrol_cards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'deckAutoReshuffle');
    }

    protected function setupNewGame($players, $options = []) {
        // Set the colors of the players with HTML color code. The default below is red/green/blue/orange/brown. The
        // number of colors defined here must correspond to the maximum number of players allowed for the gams.
        $gameinfos = $this->getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        foreach ($players as $player_id => $player) {
            // Now you can access both $player_id and $player array
            $query_values[] = vsprintf("('%s', '%s', '%s', '%s', '%s')", [
                $player_id,
                array_shift($default_colors),
                $player["player_canal"],
                addslashes($player["player_name"]),
                addslashes($player["player_avatar"]),
            ]);
        }

        // Create players based on generic information.
        //
        // NOTE: You can add extra field on player table in the database (see dbmodel.sql) and initialize
        // additional fields directly here.
        static::DbQuery(
            sprintf(
                "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES %s",
                implode(",", $query_values)
            )
        );

        // Add master data to DB
        static::DbQuery(DataService::setupRoundData());
        static::DbQuery(DataService::setupBoard());

        static::DbQuery(DataService::setupActions());
        static::DbQuery(DataService::setupBoardActions());
        static::DbQuery(DataService::setupBoardPaths());
        static::DbQuery(DataService::setupResources());
        static::DbQuery(DataService::setupMissions());
        static::DbQuery(DataService::setupRooms());

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.
        $this->patrol_cards->createCards($this->PATROL_CARD_ITEMS);

        // Missions

        $this->configureMissions(MISSION_OFFICERS_MANSION, MISSION_DOUBLE_AGENT);

        // Dummy content.
        $this->setGameStateInitialValue("my_first_global_variable", 0);
        
        // Init game statistics.
        //
        // NOTE: statistics used in this file must be defined in your `stats.inc.php` file.
        
        // Dummy content.
        // $this->initStat("table", "table_teststat1", 0);
        // $this->initStat("player", "player_teststat1", 0);
        
        
        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();

    }

    public function actPlaceWorker(int $spaceID): void {
        $roundData = $this->getRoundData();
        $spaceName = $this->getSpaceNameById($spaceID);

        $this->updateSpace($spaceID, hasWorker: true);
        $this->updatePlacedResistance($roundData["placed_resistance"] + 1);

        $this->notify->all("workerPlaced", clienttranslate("Worker placed at " . $spaceName), array(
            "spaceID" => $spaceID,
        ));

        if ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && in_array($spaceID, [1, 3, 5, 6, 9, 11]) && $this->countMarkers($spaceID) <= 0) {
            $this->placeMarker($spaceID);

            if  ($this->checkMarkersInSpaces([1, 3, 5, 6, 9, 11])) {
                $cardID = $this->drawPatrolCard();
                $card = $this->PATROL_CARD_ITEMS[$cardID - 1];
                $doubleAgentLocation = $card['space_a'];
                $this->addSpaceAction($doubleAgentLocation, ACTION_COMPLETE_DOUBLE_AGENT_MISSION);
                
                $this->notify->all("darkLadyFound", clienttranslate("Dark Lady found at " . $this->getSpaceNameById($card['space_a'])), array(
                    "cardId" => $cardID
                ));
            }
        }

        $this->gamestate->nextState("placePatrol");
    }

    public function stPlacePatrol(): void {
        // if ($this->patrol_cards->countCardInLocation('deck') <= 0) {
        //     $this->patrol_cards->moveAllCardsInLocation('discard', 'deck');            
        //     $this->patrol_cards->shuffle('deck');
        // }
        // $card = $this->patrol_cards->pickCardForLocation('deck', 'discard');
        // $cardID = $card['id'];

        // $card = $this->PATROL_CARD_ITEMS[$cardID - 1];

        $card = $this->PATROL_CARD_ITEMS[$this->drawPatrolCard() - 1];

        $spaceID = null;
        $emptySpaces = $this->getEmptySpaces();
        $roundData = $this->getRoundData();
        $arrestedOnsite = false;
        
        if (in_array($card['space_a'], $emptySpaces)) {
            $spaceID = $card['space_a'];
        } elseif (in_array($card['space_b'], $emptySpaces)) {
            $spaceID = $card['space_b'];
        } elseif (in_array($card['space_c'], $emptySpaces)) {
            $spaceID = $card['space_c'];
        } else {
            // All 3 space taken. Begining to arrest on site.
            $spacesWithResistanceWorkers = $this->getSpacesWithResistanceWorkers();

            if (in_array($card['space_a'], $spacesWithResistanceWorkers)) {
                $spaceID = $card['space_a'];
            } else if (in_array($card['space_b'], $spacesWithResistanceWorkers)) {
                $spaceID = $card['space_b'];
            } else if (in_array($card['space_c'], $spacesWithResistanceWorkers)) {
                $spaceID = $card['space_c'];
            } 

            if ($spaceID != null) {
                $arrestedOnsite = true;
            } 
        }

        if ($spaceID != null) {
            $spaceName = $this->getSpaceNameById($spaceID);

            $placeSoldier = $this->getPatrolsToPlace() - $roundData['active_soldiers'] < $roundData['placed_milice'] + 1;

            if ($placeSoldier) {
                $this->updateSpace($spaceID, hasWorker: $arrestedOnsite, hasSoldier: true);
                $this->updatePlacedSoldiers($roundData['placed_soldiers'] + 1);
            } else {
                $this->updateSpace($spaceID, hasWorker: $arrestedOnsite, hasMilice: true);
                $this->updatePlacedMilice($roundData['placed_milice'] + 1);
            }

            $this->notify->all("patrolPlaced", clienttranslate("Patrol placed at $spaceName"), array(
                "placeSoldier" => $placeSoldier,
                "spaceID" => $spaceID
            ));

            if ($arrestedOnsite) {
                $this->arrestWorker($spaceID, arrestedOnSite: true);
            }
        }

        if ($roundData['placed_resistance'] < $roundData['active_resistance']) {
            $this->gamestate->nextState("placeWorker");
        } else if ($roundData['placed_milice'] + $roundData['placed_soldiers'] + 1 < $this->getPatrolsToPlace()) {
            $this->gamestate->nextState("placePatrol");
        } else {
            $this->gamestate->nextState("activateWorker");
        }
    }

    public function actActivateWorker(int $spaceID): void {
        $spaceName = $this->getSpaceNameById($spaceID);

        $this->updateActiveSpace($spaceID);

        $this->notify->all("spaceActivated", clienttranslate("Worker at $spaceName activated"), array(
            "spaceID" => $spaceID
        ));

        $possibleActions = $this->getPossibleActions($spaceID);

        if (count($possibleActions) == 1) {
            // $this->gamestate->nextState("takeAction");
            $this->actTakeAction($possibleActions[0]['action_name']);
        } else {
            $this->gamestate->nextState("takeAction");
        }
    }

    public function actTakeAction(string $actionName): void {
        $this->notify->all("actionTaken", clienttranslate("Action selected: " . $actionName), array());
        $activeSpace = $this->getActiveSpace();

        if ($actionName == ACTION_GET_SPARE_ROOM) {
            $this->gamestate->nextstate("selectRoom");    
        } else if ($actionName === ACTION_INSERT_MOLE) {
            $this->saveAction(ACTION_INSERT_MOLE);
            $this->gamestate->nextState("nextWorker");
        } else if ($actionName === ACTION_COMPLETE_DOUBLE_AGENT_MISSION) {
            $this->completeMission(MISSION_DOUBLE_AGENT);
            foreach([1, 3, 5, 6, 9, 11] as $space) {
                $this->removeMarker($space);
            }
            $this->gamestate->nextState("removeWorker");
        } else if ($this->checkEscapeRoute()) {
            if ($actionName == ACTION_AIRDROP) {
                if (!empty($this->getEmptyFields())) {
                    $this->gamestate->nextstate("airdrop");
                } else {
                    $this->notify->all("noEmptyFieldsFound", clienttranslate("There are no empty fields"));
                    $this->returnWorker($activeSpace);
                    $this->gamestate->nextstate("nextWorker");
                }
            } else {
                $this->saveAction($actionName);
                $this->returnWorker($activeSpace);

                if ($this->getPlayerScore() >= 2) {
                    $this->gamestate->nextState("gameEnd");
                }

                $this->gamestate->nextState("nextWorker");
            }
        } else {
            if ($this->getIsSafe($actionName)) {
                $this->saveAction($actionName);
                
                if ($this->getPlayerScore() >= 2) {
                    $this->gamestate->nextState("gameEnd");
                }
            }
            $this->arrestWorker($activeSpace);

            $this->gamestate->nextState("nextWorker");
        }      
    }

    public function stNextWorker() {
        $roundData = $this->getRoundData();
        $this->resetActiveSpace();
        $this->resetActionTaken();

        if ($this->getIsMoleInserted() && $roundData['placed_resistance'] == 1) {
            $this->gamestate->nextState("roundEnd");
        } else if ($roundData['placed_resistance'] > 1) {
            $this->gamestate->nextState("activateWorker");
        } else if ($roundData['placed_resistance'] == 1) {
            $spacesWithResistanceWorkers = $this->getSpacesWithResistanceWorkers();
            $spaceID = $spacesWithResistanceWorkers[0];
            $this->updateActiveSpace($spaceID);
            $possibleActions = $this->getPossibleActions($spaceID);

            if (count($possibleActions) == 1) {
                $this->actTakeAction($possibleActions[0]['action_name']);
            } else {
                $this->gamestate->nextState("takeAction");
            }
        } else {
            $this->gamestate->nextState("roundEnd");
        }
    }

    public function actSelectField(int $spaceID): void {
        $this->setSelectedField($spaceID);
        $this->gamestate->nextstate("airdropSelectSupplies");
    }

    public function actSelectSupplies(string $supplyType): void {
        $spaceID = $this->getSelectedField();
        $quantity = $supplyType == RESOURCE_FOOD ? 3 : 1;

        if ($this->getAvailableResource($supplyType) > 0) {
            $this->setItems($spaceID, $supplyType, $quantity);
        }

        $this->returnWorker($this->getActiveSpace());

        $this->gamestate->nextstate("nextWorker");
    }

    public function stRoundEnd(): void {
        $board = $this->getBoard();
        $roundData = $this->getRoundData();
        $morale = $roundData['morale'];
        $round = (int) $roundData['round'] + 1;

        if ($this->isParadeDay($round)) {
            $morale--;
        }

        $this->updateRoundData($round, $morale);
        $this->updatePlacedMilice(0);

        if ($morale <= 0 || $roundData['active_resistance'] <= 0 || $round >= 15 || ($roundData['active_resistance'] == 1 && $this->getIsMissionSelected(MISSION_INFILTRATION))) {
            $this->gamestate->nextstate("gameEnd");
        } else {
            foreach($board as $space) {
                if ($space['has_milice'] || $space['has_soldier']) {
                    $this->updateSpace($space['space_id']);

                    $this->notify->all("patrolRemoved", '', array(
                        "spaceID" => $space['space_id']
                    ));
                }
            }
            if ($this->getIsMoleInserted()) {
                $cardId = $this->peekTopPatrolCardId();

                $this->notify->all("cardPeeked", '', array(
                    "cardId" => $cardId
                ));
            }
            $this->setShotToday(false);
            $this->gamestate->nextState("placeWorker");
        }
    }

    public function actDeclareShootingMilice(): void {
        $this->gamestate->nextState("shootMilice");
    }

    public function actShootMilice(int $spaceID): void {
        $roundData = $this->getRoundData();
        $morale = $roundData["morale"];
        $placedMilice = $roundData['placed_milice'];
        $miliceInGame = $roundData['milice_in_game'];
        $activeSoldiers = $roundData['active_soldiers'];

        $this->updateSpace($spaceID);

        $this->notify->all("patrolRemoved", clienttranslate("Milice patrol at " . $this->getSpaceNameById($spaceID) . " shot"), array(
            "spaceID" => $spaceID
        ));

        $this->updatePlacedMilice($placedMilice - 1);
        $this->updateMiliceInGame($miliceInGame - 1);
        $this->updateSoldiers($activeSoldiers + 1);
        $this->updateResourceQuantity(RESOURCE_WEAPON, -1);
        $this->setShotToday(true);
        $this->updateMorale($morale - 1);
        if ($morale - 1 <= 0) {
            $this->gamestate->nextState("endGame");
        } else {
            $this->gamestate->nextState("nextWorker");
        }
    }

    public function actSelectRoom(int $roomID): void {
        $activeSpace = $this->getActiveSpace();

        $this->setIsRoomAvailable($roomID, false); 
        $this->setRoomID($activeSpace, $roomID);
        $this->addSpareRoomActions($activeSpace, $roomID);
        $this->decrementResourceQuantity(RESOURCE_MONEY, 2);

        $this->notify->all("roomPlaced", clienttranslate("Room placed."), array(
            "roomID" => $roomID,
            "spaceID" => $activeSpace
        ));

        if ($this->checkEscapeRoute($activeSpace)) {
            $this->returnWorker($activeSpace);
        } else {
            $this->arrestWorker($activeSpace);
        }

        $this->gamestate->nextState("nextWorker");
    }

    public function actBack(): void {
        $this->gamestate->nextState("nextWorker");
    }

    public function actRemoveWorker(int $spaceID): void {
        $this->removeWorker($spaceID);

        $this->gamestate->nextState("nextWorker");
    }

    // ARGS

    public function argPlaceWorker(): array {
        return [
            "emptyFields" => $this->getEmptySpaces()
        ];
    }

    public function argActivateWorker(): array {
        return [
            "spaces" => $this->getSpacesWithResistanceWorkers(),
            "canShoot" => $this->getCanShoot()
        ];
    }

    public function argTakeAction(): array {
        return [
            "actions" => $this->getPossibleActions($this->getActiveSpace()),
            "activeSpace" => $this->getActiveSpace()
        ];
    }

    public function argSelectField(): array {
        return [
            "emptyFields" => $this->getEmptyFields()
        ];
    }

    public function argSelectSupplies(): array {
        $options = [
            [
                "resourceName" => RESOURCE_FOOD,
                "airdropOptionDescription" => "Airdrop 3 food"
            ], 
            [
                "resourceName" => RESOURCE_MONEY,
                "airdropOptionDescription" => "Airdrop 1 money"
            ], 
            [
                "resourceName" => RESOURCE_WEAPON,
                "airdropOptionDescription" => "Airdrop 1 weapon"
            ]
        ];

        $options = array_filter($options, function($option) {
            return $this->getAvailableResource($option["resourceName"]) > 0;
        });

        return [
            "options" => $options
        ];
    }

    public function argShootMilice(): array {
        // TODO Turn into assosiative array.
        return $this->getSpacesWithMilice();
    }

    public function argSelectRoom(): array {
        // TODO Turn into assosiative array.
        return $this->getAvailableRooms();
    }

    public function argRemoveWorker(): array {
        return $this->getSpacesWithResistanceWorkers();
    }

    // UTILITY 

    public function returnWorker(int $spaceID): void {
        if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $roundData = $this->getRoundData();

        $this->updateSpace($spaceID);
        $this->updatePlacedResistance($roundData['placed_resistance'] - 1);

        $this->notify->all("workerRemoved", clienttranslate("Worker safely returned from $spaceName"), array(
            "activeSpace" => $spaceID
        ));
    }

    public function arrestWorker(int $spaceID, bool $arrestedOnSite = false): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $roundData = $this->getRoundData();

        if ($arrestedOnSite) {
            $this->updateSpace($spaceID, $hasWorker = false, $hasMilice = true);
        } else {
            $this->updateSpace($spaceID);
        }
        
        $this->notify->all("workerRemoved", clienttranslate("Worker arrested at " . $spaceName), array(
            "activeSpace" => $spaceID
        ));

        $this->updatePlacedResistance($roundData['placed_resistance'] - 1);
        $this->updateActiveResistance($roundData['active_resistance'] - 1);
    }

    public function removeWorker(int $spaceID): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $roundData = $this->getRoundData();
        
        $this->notify->all("workerRemoved", clienttranslate("Worker removed from " . $spaceName), array(
            "activeSpace" => $spaceID
        ));

        $this->updateSpace($spaceID, $hasWorker = false);
        $this->updatePlacedResistance($roundData['placed_resistance'] - 1);
        $this->updateActiveResistance($roundData['active_resistance'] - 1);
    }

    public function returnOrArrest(int $spaceID): void {
        if ($this->checkEscapeRoute($spaceID)) {
            $this->returnWorker($spaceID);
        } else {
            $this->arrestWorker($spaceID);
        }
    }

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
            $this->addBoardSpace(18, $missionAName);
        }

        if (in_array($missionBName, $missionsWithSpaces)) {
            $this->addBoardSpace(21, $missionBName);
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

    protected function addBoardSpace(int $spaceID, string $missionName): void {
        $missionID = $this->getMissionIdByMissionName($missionName);

        if (in_array((int) $spaceID, [18, 19, 20])) {
            static::DbQuery("
                INSERT INTO board (`space_id`, `space_name`, `mission_id`) 
                VALUES ($spaceID, \"Mission A\", $missionID);
            ");

            static::DbQuery("
                INSERT INTO board_path (`space_id_start`, `space_id_end`)
                VALUES (2, $spaceID), ($spaceID, 2);
            ");
        } else if (in_array((int) $spaceID, [21, 22, 23])) {
            static::DbQuery("
                INSERT INTO board (`space_id`, `space_name`, `mission_id`) 
                VALUES ($spaceID, \"Mission B\", $missionID);
            ");

            static::DbQuery("
                INSERT INTO board_path (`space_id_start`, `space_id_end`)
                VALUES (3, $spaceID), ($spaceID, 3);
            ");
        }
    }

    protected function addSpaceAction(int $spaceID, string $actionName): void {
        self::DbQuery("
            INSERT INTO board_action (space_id, action_id)
            SELECT $spaceID, action_id
            FROM action
            WHERE action_name = \"$actionName\";
        ");
    }

    protected function removeBoardSpace(int $spaceID) {
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

    protected function completeMission(string $missionName): void {
        static::DbQuery("
            UPDATE mission
            SET completed = TRUE
            WHERE mission_name = '$missionName';
        ");

        $spaceIDs = $this->getSpaceIdsByMissionName($missionName);
        foreach ($spaceIDs as $spaceID) {
            $this->removeBoardSpace((int) $spaceID);
        }

        $this->incrementPlayerScore();

        $this->notify->all("missionCompleted", clienttranslate("Mission completed"), array("missionID" => $this->getMissionIdByMissionName($missionName), "playerScore" => $this->getPlayerScore(), "playerId" => $this->getActivePlayerId()));
    }

    protected function addSpareRoomActions(int $spaceID, int $roomID): void {
        $rooms = $this->getRooms();

        switch ($rooms[$roomID]["room_name"]) {
            case ROOM_INFORMANT:
                $this->addSpaceAction($spaceID, ACTION_GET_INTEL);
                break;
            case ROOM_COUNTERFEITER:
                $this->addSpaceAction($spaceID, ACTION_GET_MONEY);
                break;
            case ROOM_SAFE_HOUSE:
                $this->updateFieldsSafety($spaceID, isSafe: true);
                break;
            case ROOM_CHEMISTS_LAB:
                $this->addSpaceAction($spaceID, ACTION_GET_EXPLOSIVES);
                break;
            case ROOM_SMUGGLER:
                $this->addSpaceAction($spaceID, ACTION_GET_3_FOOD);
                $this->addSpaceAction($spaceID, ACTION_GET_3_MEDICINE);
                break;
            case ROOM_PROPAGANDIST:
                $this->addSpaceAction($spaceID, ACTION_INCREASE_MORALE);
                break;
            case ROOM_FIXER:

                break;
            case ROOM_PHARMACIST:
                $this->addSpaceAction($spaceID, ACTION_GET_POISON);
                break;
            case ROOM_FORGER:
                $this->addSpaceAction($spaceID, ACTION_GET_FAKE_ID);
                break;
        }
    } 

    // SAVE ACTION

    protected function saveAction(string $actionName): void {
        switch($actionName) {
            case ACTION_GET_WEAPON:
                $this->decrementResourceQuantity(RESOURCE_MONEY);
                $this->incrementResourceQuantity(RESOURCE_WEAPON);
                break;
            case ACTION_GET_FOOD:
                $this->incrementResourceQuantity(RESOURCE_FOOD);
                break;
            case ACTION_GET_MEDICINE:
                $this->incrementResourceQuantity(RESOURCE_MEDICINE);
                break;
            case ACTION_GET_INTEL:
                $this->incrementResourceQuantity(RESOURCE_INTEL);
                break;
            case ACTION_GET_MONEY_FOR_FOOD:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->decrementResourceQuantity(RESOURCE_FOOD);
                    $this->incrementResourceQuantity(RESOURCE_MONEY);
                    $this->decrementMorale();
                }
                break;
            case ACTION_GET_MONEY_FOR_MEDICINE:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->decrementResourceQuantity(RESOURCE_MEDICINE);
                    $this->incrementResourceQuantity(RESOURCE_MONEY);
                    $this->decrementMorale();
                }
                break;
            case ACTION_PAY_FOR_MORALE:
                $this->incrementMorale();
                $this->decrementResourceQuantity(RESOURCE_MEDICINE);
                $this->decrementResourceQuantity(RESOURCE_FOOD);
                break;
            case ACTION_GET_WORKER:
                $roundData = $this->getRoundData();
                $this->decrementResourceQuantity(RESOURCE_FOOD);
                $this->updateActiveResistance($roundData['active_resistance'] + 1);
                $this->updateResistanceToRecruit($roundData['resistance_to_recruit'] - 1);
                break;
            case ACTION_COLLECT_ITEMS:
                $activeSpace = $this->getActiveSpace();
                $spacesWithItems = $this->getSpacesWithItems();
                $itemType = $spacesWithItems[$activeSpace]['item'];
                $quantity = $spacesWithItems[$activeSpace]['quantity'];

                $this->updateResourceQuantityFromCollectingAirdrop($itemType, (int) $quantity);
                $this->setItems($activeSpace);
                break;
            case ACTION_WRITE_GRAFFITI:
                $this->placeMarker($this->getActiveSpace());
                break;
            case ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION:
                $this->completeMission(MISSION_MILICE_PARADE_DAY);
                $this->decrementResourceQuantity(RESOURCE_WEAPON);
                $this->incrementMorale($this->getMorale());
                $this->arrestWorker(1);
                break;
            case ACTION_COMPLETE_OFFICERS_MANSION_MISSION:
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_OFFICERS_MANSION);
                foreach([1, 3, 11] as $space) {
                    $this->removeMarker($space);
                }
                break;
            case ACTION_GET_MONEY:
                $this->incrementResourceQuantity(RESOURCE_MONEY);
                break;
            case ACTION_GET_EXPLOSIVES:
                $this->decrementResourceQuantity(RESOURCE_MEDICINE);
                $this->incrementResourceQuantity(RESOURCE_EXPLOSIVES);
                break;
            case ACTION_GET_3_FOOD:
                $this->incrementResourceQuantity(RESOURCE_FOOD, 3);
                break;
            case ACTION_GET_3_MEDICINE:
                $this->incrementResourceQuantity(RESOURCE_MEDICINE, 3);
                break;
            case ACTION_INCREASE_MORALE:
                $morale = $this->getMorale();
                $this->updateMorale($morale + 1);
                break;
            case ACTION_INFILTRATE_FACTORY:
                $activeSpace = $this->getActiveSpace();
                $this->placeMarker($activeSpace);
                $this->addBoardSpace($activeSpace + 1, MISSION_SABOTAGE);
                if ($activeSpace == 19 || $activeSpace == 22) {
                    $this->addSpaceAction($activeSpace + 1, ACTION_SABOTAGE_FACTORY);
                } else {
                    $this->addSpaceAction($activeSpace + 1, ACTION_INFILTRATE_FACTORY);
                }
                break;
            case ACTION_SABOTAGE_FACTORY:
                $this->updateResourceQuantity(RESOURCE_EXPLOSIVES, -2);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_SABOTAGE);
                break;
            case ACTION_DELIVER_INTEL:
                $activeSpace = $this->getActiveSpace();
                $this->updateResourceQuantity(RESOURCE_INTEL, -2);
                if ($activeSpace == 20 || $activeSpace == 23) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_UNDERGROUND_NEWSPAPER);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addBoardSpace($activeSpace + 1, MISSION_UNDERGROUND_NEWSPAPER);
                    $this->addSpaceAction($activeSpace + 1, ACTION_DELIVER_INTEL);
                }
                break;
            case ACTION_INSERT_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->setMoleInserted(true);
                $this->updateResourceQuantity(RESOURCE_INTEL, -2);
                $this->addBoardSpace($activeSpace + 1, MISSION_INFILTRATION);
                $this->addSpaceAction($activeSpace + 1, ACTION_RECOVER_MOLE);
                break;
            case ACTION_RECOVER_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->updateResourceQuantity(RESOURCE_WEAPON, -1);
                $this->updateResourceQuantity(RESOURCE_EXPLOSIVES, -1);
                $this->setMoleInserted(false);
                $this->returnWorker($activeSpace - 1);
                $this->returnOrArrest($activeSpace);
                $this->completeMission(MISSION_INFILTRATION);
                break;
            case ACTION_POISON_SHEPARDS:
                $activeSpace = $this->getActiveSpace();
                $this->updateResourceQuantity(RESOURCE_FOOD, -1);
                $this->updateResourceQuantity(RESOURCE_MEDICINE, -1);
                if ($activeSpace == 20 || $activeSpace == 23) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_GERMAN_SHEPARDS);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addBoardSpace($activeSpace + 1, MISSION_GERMAN_SHEPARDS);
                    $this->addSpaceAction($activeSpace + 1, ACTION_POISON_SHEPARDS);
                }
                break;
        }
    } 

    // GETTERS

    protected function getAllDatas() {
        $result = [];

        // WARNING: We must only return information visible by the current player.
        $result["currentPlayerID"] = (int) $this->getCurrentPlayerId();

        // Get information about players.
        // NOTE: you can retrieve some extra field you added for "player" table in `dbmodel.sql` if you need it.
        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $roundData = $this->getRoundData();
        $result["roundData"] = $roundData;
        $result["board"] = $this->getBoard();
        $result["spacesWithItems"] = $this->getSpacesWithItems();
        $result["discardedPatrolCards"] = $this->patrol_cards->getCardsInLocation('discard');
        $result["resources"] = $this->getAllResources();
        $result["selectedMissions"] = $this->getSelectedMissions();
        $result["completedMissions"] = $this->getCompletedMissions();
        $result["rooms"] = $this->getRooms();
        $result["spacesWithRooms"] = $this->getSpacesWithRooms();
        $result["activeMilice"] = max(0, $this->getPatrolsToPlace() - $roundData["active_soldiers"]);

        return $result;
    }

    // BOARD
    
    protected function getBoard() {
        return $this->getCollectionFromDb(
            "SELECT `space_id`, `has_worker`, `has_milice`, `has_soldier`, `is_safe`, `has_item`, `marker_number`, `mission_id` FROM `board`;"
        );
    }

    protected function getSpaceNameById(int $spaceID): string {
        return (string) $this->getUniqueValueFromDb("
            SELECT space_name
            FROM board
            WHERE space_id = $spaceID;
        ");
    }

    protected function getEmptySpaces(): array {
        $results = $this->getCollectionFromDB('
            SELECT space_id, has_worker, has_milice, has_soldier, is_safe, is_field
            FROM board
            WHERE has_worker = 0 AND has_milice = 0 AND has_soldier = 0 AND is_safe = 0 AND (is_field = 0 OR (is_field = 1 AND has_item = 1)) AND (mission_id = 0 || marker_number = 0);
        ');

        return array_keys($results);
    }

    protected function getEmptyFields(): array {
        $result = $this->getCollectionFromDb('
            SELECT space_id
            FROM board
            WHERE is_field = 1 AND item IS NULL;
        ');

        return $result;
    }

    protected function getSpacesWithResistanceWorkers(): array {
        $result = array_keys($this->getCollectionFromDb("SELECT `space_id` FROM `board` WHERE `has_worker` = TRUE"));
        if ($this->getIsMissionSelected(MISSION_INFILTRATION) && $this->getIsMoleInserted()) {
            $spaceIdWithMole = $this->getSpaceIdsByMissionName(MISSION_INFILTRATION)[0];

            $result = array_filter($result, function($spaceId) use ($spaceIdWithMole) {
                return $spaceId != $spaceIdWithMole;
            });
        }
        return $result;
    }
    
    protected function getSpacesWithMilice(): array {
        $result = $this->getCollectionFromDb("
            SELECT space_id
            FROM board
            WHERE has_milice = TRUE;"
        );
        return array_keys($result);
    }
    
    protected function getSpacesWithItems(): array {
        return $this->getCollectionFromDb("
            SELECT space_id, item, quantity
            FROM board
            WHERE has_item = TRUE;
        ");
    }

    protected function getSpacesWithRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id, room_id
            FROM board
            WHERE room_id IS NOT NULL;
        ");
    }

    protected function checkMarkersInSpaces($spaces): bool {
        $result = $this->getCollectionFromDb("
            SELECT space_id, marker_number
            FROM board
            WHERE space_id IN (" . implode(",", $spaces) . ");");

        $this->dump("result", $result);

        $allSpacesHaveMarkers = true;
        foreach ($result as $space) {
            if ((int) $space["marker_number"] <= 0) {
                $allSpacesHaveMarkers = false;
                break;
            }
        }
        return $allSpacesHaveMarkers;
    }

    protected function countMarkers(int $spaceID): int {
        return (int) $this->getUniqueValueFromDb("SELECT marker_number FROM board WHERE space_id = $spaceID;");
    }

    protected function countMarkersInSpaces(array $spaces): int {
        $result = $this->getCollectionFromDb("
            SELECT space_id, marker_number
            FROM board
            WHERE space_id IN (" . implode(",", $spaces) . ");
        ");

        return array_reduce($result, function($carry, $space) {
            $carry += (int) $space['marker_number'];
            return $carry;
        }, 0);
    }

    protected function getSpaceIdsByMissionId(int $missionID): array {
        return (array) $this->getCollectionFromDb("
            SELECT space_id
            FROM board
            WHERE mission_id = $missionID;
        ");
    }
    
    protected function getIsRoomPlaced(int $spaceID): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT room_id
            FROM board
            WHERE space_id = $spaceID;
        ");
    }

    // ROUND

    protected function getRoundData(): array {
        return (array) $this->getObjectFromDb(
            "SELECT `round`, `morale`, `active_soldiers`, `active_resistance`, `resistance_to_recruit`, `placed_resistance`, `placed_milice`, `placed_soldiers`, `milice_in_game` FROM `round_data`"
        );
    }

    protected function getActiveSpace(): int {
        return (int) $this->getUniqueValueFromDb("SELECT active_space FROM round_data");
    }

    protected function getActiveResistance(): int {
        return (int) $this->getUniqueValueFromDb("SELECT active_resistance FROM round_data");
    }

    protected function getResistanceToRecruit(): int {
        return (int) $this->getUniqueValueFromDb("SELECT resistance_to_recruit FROM round_data");
    }

    protected function getActionTaken(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT action_taken FROM round_data");
    }

    protected function getMorale(): int {
        return (int) $this->getUniqueValueFromDb("SELECT morale from round_data;");
    }
    
    protected function getSelectedField(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT selected_field
            FROM round_data;
        ");
    }
    
    protected function getShotToday(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT shot_today FROM round_data");
    }

    protected function getCanShoot(): bool {
        $weapon = $this->getResource('weapon');
        $placedMilice = $this->getRoundData()['placed_milice'];
        return ($weapon > 0 && !$this->getShotToday() && $placedMilice > 0) && !($this->getIsMissionSelected(MISSION_GERMAN_SHEPARDS) && !$this->getIsMissionCompleted(MISSION_GERMAN_SHEPARDS));
    }

    protected function getIsMoleInserted(): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT mole_inserted FROM round_data");
    }
    
    // GET POSSIBLE ACTIONS

    protected function getPossibleActions($spaceID): array {
        $willGetArrested = $this->checkEscapeRoute($spaceID);

        if ($willGetArrested) {
            $result = (array) $this->getCollectionFromDb("
                SELECT a.action_id, a.action_name, a.action_description
                FROM board_action ba
                JOIN action a ON ba.action_id = a.action_id
                WHERE ba.space_id = $spaceID;
            ");
        } else {
            $result = (array) $this->getCollectionFromDb("
                SELECT a.action_id, a.action_name, a.action_description
                FROM board_action ba
                JOIN action a ON ba.action_id = a.action_id
                WHERE ba.space_id = $spaceID AND a.is_safe = TRUE;
            ");
        }

        $result = array_filter($result, function($action) use ($spaceID) {
            switch ($action['action_name']) {
                case ACTION_GET_WEAPON:
                    return $this->getResource(RESOURCE_MONEY) > 0;
                    break;
                case ACTION_AIRDROP:
                    return count($this->getEmptyFields()) > 0;
                    break;
                case ACTION_PAY_FOR_MORALE:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getResource(RESOURCE_MEDICINE) > 0;
                    break;
                case ACTION_GET_MONEY_FOR_FOOD:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getAvailableResource(RESOURCE_MONEY) > 0;
                    break;
                case ACTION_GET_MONEY_FOR_MEDICINE:
                    return $this->getResource(RESOURCE_MEDICINE) > 0 && $this->getAvailableResource(RESOURCE_MONEY) > 0;
                case ACTION_WRITE_GRAFFITI:
                    return $this->countMarkers($spaceID) === 0 && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                    break;
                case ACTION_COMPLETE_OFFICERS_MANSION_MISSION:
                    return ((!$this->getIsMissionSelected(MISSION_DOUBLE_AGENT) || $this->getIsMissionCompleted(MISSION_DOUBLE_AGENT)) && $this->countMarkersInSpaces([1, 3, 11]) == 3) || ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && $this->countMarkersInSpaces([1, 3, 11]) == 6) && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                    break;
                case ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION:
                    $day = (int) $this->getRoundData()["round"];
                    return $this->getResource(RESOURCE_WEAPON) > 0 && $this->isParadeDay($day);
                    break;
                case ACTION_GET_WORKER:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getResistanceToRecruit() > 0;
                    break;
                case ACTION_GET_SPARE_ROOM:
                    return !$this->getIsRoomPlaced($spaceID) && $this->getResource(RESOURCE_MONEY) >= 2;
                    break;
                case ACTION_GET_EXPLOSIVES:
                    return $this->getResource(RESOURCE_MEDICINE) >= 1;
                    break;
                case ACTION_SABOTAGE_FACTORY:
                    return $this->getResource(RESOURCE_EXPLOSIVES) >= 1;
                    break;
                case ACTION_DELIVER_INTEL:
                    return $this->getResource(RESOURCE_INTEL) >= 2;
                    break;
                case ACTION_INSERT_MOLE:
                    return $this->getResource(RESOURCE_INTEL) >= 2;
                    break;
                case ACTION_COMPLETE_DOUBLE_AGENT_MISSION:
                    return !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT);
                default:
                    return true;
                    break;
            }
        });

        foreach($result as &$action) {
            switch($action['action_name']) {
                case ACTION_GET_FOOD:
                    if ($this->getAvailableResource(RESOURCE_FOOD) <= 0) {
                        $action['action_description'] .= " (No effect)";
                    }
                    break;
                case ACTION_GET_MEDICINE:
                    if ($this->getAvailableResource(RESOURCE_MEDICINE) <= 0) {
                        $action['action_description'] .= " (No effect)";
                    }
                    break;
                case ACTION_GET_INTEL:
                    if ($this->getAvailableResource(RESOURCE_INTEL) <= 0) {
                        $action['action_description'] .= " (No effect)";
                    }
                    break;
                case ACTION_GET_MONEY:
                    if ($this->getAvailableResource(RESOURCE_MONEY) <= 0) {
                        $action['action_description'] .= " (No effect)";
                    }
                    break;
                case ACTION_GET_MONEY_FOR_FOOD:
                case ACTION_GET_MONEY_FOR_MEDICINE:
                    if ($this->getMorale() === 1) {
                        $action['action_description'] .= " (This will result in loosing the game)";
                    } 
                    break;
                case ACTION_PAY_FOR_MORALE:
                    if ($this->getMorale() === 7) {
                        $action['action_description'] .= " (Resources will be lost. Morale won't be gained)";
                    }
                    break;
            }
        }

        $result[] = [
            "action_id" => 0,
            "action_name" => "return",
            "action_description" => clienttranslate("Return to Safe House"),
        ];

        return $result;
    }

    protected function getPatrolsToPlace(): int {
        $roundData = $this->getRoundData();
        $activeResistance = (int) $roundData['active_resistance'];

        $morale_to_patrols_map = array(
            0 => 5,
            1 => 5,
            2 => 4,
            3 => 4,
            4 => 4,
            5 => 3,
            6 => 3,
            7 => 2
        );

        return max($activeResistance, $morale_to_patrols_map[$roundData['morale']]);
    }

    // BOARD PATHS

    protected function getBoardPaths(): array {
        $result = (array) $this->getCollectionFromDb("
            SELECT path_id, space_id_start, space_id_end
            FROM board_path;
        ");
        
        $roundData = $this->getRoundData();

        return array_filter($result, function ($connection) use ($roundData) {
            return !(
                    (($connection['space_id_start'] == '1' && $connection['space_id_end'] == '2') || ($connection['space_id_start'] == '2' && $connection['space_id_end'] == '1')) && 
                    $this->isParadeDay((int) $roundData["round"]) &&
                    ($this->getIsMissionSelected(MISSION_MILICE_PARADE_DAY) && !$this->getIsMissionCompleted(MISSION_MILICE_PARADE_DAY))
                );
        });
        return $result;
    }

    // RESOURCES

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

    // ACTIONS

    protected function getIsSafe(string $actionName): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT is_safe FROM action WHERE action_name = \"$actionName\";");
    }

    // MISSION

    protected function getIsMissionCompleted(string $missionName): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT completed
            FROM mission
            WHERE mission_name = '$missionName';
        ");
    } 

    protected function getMissionIdByMissionName(string $missionName): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT mission_id
            FROM mission
            WHERE mission_name = '$missionName';
        ");
    }

    protected function getSpaceIdsByMissionName(string $missionName): array {
        $result = (array) $this->getCollectionFromDB("
            SELECT b.space_id
            FROM board AS b
            JOIN mission AS m ON b.mission_id = m.mission_id
            WHERE m.mission_name = '$missionName';
        ");

        return array_keys($result);
    }

    protected function setSelectedMissions(string $missionAName, string $missionBName): void {
        self::DbQuery("
            UPDATE mission
            SET selected = TRUE
            WHERE mission_name = '$missionAName' OR mission_name = '$missionBName';"
        );
    }

    protected function getSelectedMissions(): array {
        return (array) $this->getCollectionFromDb("
            SELECT mission_id
            FROM mission
            WHERE selected = TRUE;
        ");
    }

    protected function getIsMissionSelected(string $missionName): bool {
        return (bool) $this->getUniqueValueFromDb("
            SELECT selected
            FROM mission
            WHERE mission_name = '$missionName';
        ");
    }

    protected function getCompletedMissions(): array {
        return (array) $this->getCollectionFromDb("
            SELECT mission_id
            FROM mission
            WHERE completed = TRUE;
        ");
    }

    // PLAYER

    protected function getPlayerScore(): int {
        return (int) $this->getUniqueValueFromDb("
            SELECT player_score
            FROM player
            WHERE player_id = " . $this->getCurrentPlayerID() . ";"
        );
    }

    // ROOMS

    protected function getRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT room_id, room_name, available
            FROM room;
        ");
    }

    protected function getAvailableRooms(): array {
        return (array) $this->getCollectionFromDb("
            SELECT room_id, room_name
            FROM room
            WHERE available = TRUE;
        ");
    }

    // CARDS 

    protected function shuffleIfNeeded(): void {
        if ($this->patrol_cards->countCardInLocation('deck') <= 0) {
            $this->patrol_cards->moveAllCardsInLocation('discard', 'deck');            
            $this->patrol_cards->shuffle('deck');
            $this->notify->all("patrolCardsShuffled", clienttranslate("Patrol Cards Shuffled"));
        }
    }

    protected function peekTopPatrolCardId(): int {
        $this->shuffleIfNeeded();

        $cardId = (int) $this->patrol_cards->getCardOnTop('deck')['type_arg'];

        return $cardId;
    } 

    protected function drawPatrolCard() {
        $this->shuffleIfNeeded();

        $card = $this->patrol_cards->pickCardForLocation('deck', 'discard');
        $cardID = $card['type_arg'];

        $this->notify->all("patrolCardDiscarded", clienttranslate(""), array(
            "patrolCardID" => $cardID
        ));

        return $cardID;
    }

    // UPDATES

    protected function updateSpace($spaceID, $hasWorker = false, $hasMilice = false, $hasSoldier = false) {
        self::DbQuery('
            UPDATE board
            SET has_worker = ' . (int) $hasWorker . ', has_milice = ' . (int) $hasMilice . ', has_soldier = ' . (int) $hasSoldier . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    protected function setRoomId($spaceID, $roomID) {
        self::DbQuery("
            UPDATE board
            SET room_id = $roomID
            WHERE space_id = $spaceID;
        ");
    }

    protected function updateActiveResistance($newNumber) {
        self::DbQuery("
            UPDATE round_data
            SET active_resistance = $newNumber;
        ");

        $this->notify->all("activeResistanceUpdated", clienttranslate("You have $newNumber active resistance operatives"), array(
            "active_resistance" => $newNumber
        ));
    }

    protected function updatePlacedResistance($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_resistance = ' . $newNumber . ';'
        );

        $this->notify->all("placedResistanceUpdated", '', array(
            "placedResistance" => $newNumber,
        ));
    }

    protected function updateResistanceToRecruit($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET resistance_to_recruit = ' . $newNumber . ';'
        );

        $this->notify->all("resistanceToRecruitUpdated", '', array(
            "resistanceToRecruit" => $newNumber,
        ));
    }

    protected function updatePlacedMilice($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_milice = ' . $newNumber . ';'
        );

        $this->notify->all("placedMiliceUpdated", '', array(
            "placedMilice" => $newNumber,
        ));
    }

    protected function updateMiliceInGame($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET milice_in_game = ' . $newNumber . ';'
        );

        $this->notify->all("miliceInGameUpdated", '', array(
            "miliceInGame" => $newNumber,
        ));
    }

    protected function updatePlacedSoldiers($newNumber) {
        self::DbQuery('
            UPDATE round_data
            SET placed_soldiers = ' . $newNumber . ';'
        );
    }

    protected function updateActiveSpace($spaceID) {
        self::DbQuery('
            UPDATE round_data
            SET active_space = ' . $spaceID . ';'
        );
    }

    protected function resetActiveSpace() {
        self::DbQuery('
            UPDATE round_data
            SET active_space = 0;'
        );
    }

    protected function updateActionTaken() {
        self::DbQuery('
            UPDATE round_data
            SET action_taken = TRUE;
        ');
    }

    protected function resetActionTaken(): void {
        self::DbQuery('
            UPDATE round_data
            SET action_taken = FALSE;
        ');
    }

    protected function updateRoundData($round, $morale, $placedMilice = 0, $placedSoldiers = 0): void {
        self::DbQuery("
            UPDATE round_data
            SET round = $round, morale = $morale, placed_milice = $placedMilice, placed_soldiers = $placedSoldiers;  
        ");

        $this->notify->all("roundDataUpdated", clienttranslate("Round $round begins."), array(
            "round" => $round,
            "morale" => $morale
        ));
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

    protected function updateMorale(int $newMorale): void {
        self::DbQuery("
            UPDATE round_data
            SET morale = $newMorale;
        ");

        $this->notify->all("moraleUpdated", clienttranslate("Morale is $newMorale"), array(
            "player_id" => $this->getCurrentPlayerId(),
            "morale" => $newMorale
        ));
    }

    protected function updateSoldiers($newNumber): void {
        self::DbQuery("
            UPDATE round_data
            SET active_soldiers = $newNumber;
        ");

        $this->notify->all("soldiersUpdated", clienttranslate("There are $newNumber active soldiers now"), array(
            "newNumber" => $newNumber
        ));
    }

    protected function incrementPlayerScore(): void {
        static::DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id = " . $this->getCurrentPlayerId() . ";");
    }

    protected function setSelectedField(int $spaceID): void {
        self::DbQuery("
            UPDATE round_data
            SET selected_field = $spaceID;
        ");
    }

    protected function setItems(int $spaceID, string|null $itemType = NULL, int $quantity = 0): void {
        if ($itemType != NULL) {
            $quantity = min($quantity, $this->getAvailableResource($itemType));
        }

        if ($itemType != NULL && $quantity > 0) {
            self::DbQuery("
                UPDATE board
                SET has_item = TRUE, item = \"$itemType\", quantity = \"$quantity\"
                WHERE space_id = $spaceID;
            ");

            self::DbQuery("
                UPDATE resource
                SET available = available - $quantity
                WHERE resource_name = \"$itemType\";
            ");

            $this->notify->all("itemsPlaced", clienttranslate("$quantity $itemType airdropped onto field"), array(
                "spaceID" => $spaceID,
                "supplyType" => $itemType,
                "quantity" => $quantity
            ));
        } else {
            self::DbQuery("
                UPDATE board
                SET has_item = FALSE, item = NULL, quantity = 0
                WHERE space_id = $spaceID;
            ");

            $this->notify->all("itemsCollected", clienttranslate("Items collected from $spaceID"), array(
                "spaceID" => $spaceID 
            ));
        }
    }

    protected function setShotToday(bool $shotToday): void {
        self::DbQuery("
            UPDATE round_data
            SET shot_today = " . (int) $shotToday . ";"
        );
    }

    protected function setMoleInserted(bool $moleInserted = false): void {
        self::DbQuery("UPDATE round_data SET mole_inserted = " . (int) $moleInserted . ";");
    }

    protected function placeMarker(int $spaceID): void {
        static::DbQuery("UPDATE board SET marker_number = marker_number + 1 WHERE space_id = $spaceID;");

        $this->notify->all("markerPlaced", clienttranslate("Marker placed at " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID
        ));
    }

    protected function removeMarker(int $spaceID): void {
        static::DbQuery("UPDATE board SET marker_number = marker_number - 1 WHERE space_id = $spaceID;");

        $this->notify->all("markerRemoved", clienttranslate("Marker removed from " . $this->getSpaceNameById($spaceID)), array(
            "spaceID" => $spaceID
        ));
    }

    protected function setResistanceToRecruit(int $resistanceToRecruit): void {
        self::DbQuery("
            UPDATE round_data
            SET resistance_to_recruit = $resistanceToRecruit;
        ");
    }

    protected function setIsRoomAvailable(int $roomID, bool $isAvailable): void {
        self::DbQuery("
            UPDATE room
            SET available = " . (int) $isAvailable . " 
            WHERE room_id = $roomID;
        ");
    }

    protected function incrementResourceQuantity(string $resourceName, int $amount = 1): void {
        $availableResource = $this->getAvailableResource($resourceName);
        $amount = min($amount, $availableResource);
        
        $this->updateResourceQuantity($resourceName, $amount);
    }

    protected function decrementResourceQuantity(string $resourceName, int $amount = 1): void {
        $this->updateResourceQuantity($resourceName,  -$amount);
    }

    protected function incrementMorale(): void {
       $this->updateMorale($this->getMorale() + 1);
    }

    protected function decrementMorale(): void {
       $this->updateMorale($this->getMorale() - 1);
    }

    protected function updateFieldsSafety(int $spaceID, $isSafe = false): void {
        self::DbQuery('
            UPDATE board
            SET is_safe = ' . (int) $isSafe . '
            WHERE space_id = ' . $spaceID . ';'
        );
    }

    // CHECK ESCAPE ROUTE 

    protected function checkEscapeRoute(): bool {
        $activeSpace = $this->getActiveSpace();
        $board = $this->getBoard();
        $boardPaths = $this->getBoardPaths();

        $spacesToCheck = array();

        foreach ($boardPaths as $boardPath) {
            if ($boardPath['space_id_start'] == $activeSpace) {
                $spacesToCheck[] = $boardPath["space_id_end"];
            }
        }

        for ($i = 0; $i < count($spacesToCheck); $i++) {
            $spaceID = $spacesToCheck[$i];
            $isSafe = (bool) $board[$spaceID]['is_safe'];

            if ($isSafe) {
                // $this->notify->all("routeChecked", clienttranslate("Escape route found"), array());
                return true;
            } else if (!$board[$spaceID]['has_milice'] && !$board[$spaceID]['has_soldier']) { 
                $spacesToAdd = array();

                foreach ($boardPaths as $boardPath) {
                    if ($boardPath['space_id_start'] == $spaceID) {
                        $spacesToAdd[] = $boardPath["space_id_end"];
                    }
                }

                for($j = 0; $j < count($spacesToAdd); $j++) {
                    if (!in_array($spacesToAdd[$j], $spacesToCheck)) {
                        $spacesToCheck[] = $spacesToAdd[$j];
                    }
                }
            }
        }

        // $this->notify->all("routeChecked", clienttranslate("No escape route found"), array());
        return false;
    }

    // PREDICATES

    protected function isParadeDay(int $day): bool {
        return $day > 0 && ($day === 14 || $day % 3 === 0);
    }

    // BOILERPLATE
    
    public function getGameProgression() {
        // TODO: compute and return the game progression

        return 0;
    }

    /**
     * Migrate database.
     *
     * You don't have to care about this until your game has been published on BGA. Once your game is on BGA, this
     * method is called everytime the system detects a game running with your old database scheme. In this case, if you
     * change your database scheme, you just have to apply the needed changes in order to update the game database and
     * allow the game to continue to run with your new version.
     *
     * @param int $from_version
     * @return void
     */

    public function upgradeTableDb($from_version) {
        //       if ($from_version <= 1404301345)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
        //
        //       if ($from_version <= 1405061421)
        //       {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            $this->applyDbUpgradeToAllDB( $sql );
        //       }
    }

    /**
     * Returns the game name.
     *
     * IMPORTANT: Please do not modify.
     */
    protected function getGameName() {
        return "maquis";
    }
}
