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

use ComponentsTrait;

require_once(APP_GAMEMODULE_PATH . "module/table/table.game.php");

require_once("DataService.php");
require_once("constants.inc.php");
require_once("ComponentsTrait.php");
require_once("BoardTrait.php");
require_once("MissionsTrait.php");
require_once("RoundTrait.php");
require_once("RoomsTrait.php");
require_once("ResourcesTrait.php");
require_once("PatrolCardsTrait.php");
require_once("PlayerTrait.php");
require_once("PawnsTrait.php");
require_once("MarkersTrait.php");

const BOARD = 'BOARD_STATE';

class Game extends \Table {
    use ComponentsTrait;
    use BoardTrait;
    use MissionsTrait;
    use RoundTrait;
    use ResourcesTrait;
    use PatrolCardsTrait;
    use RoomsTrait;
    use PlayerTrait;
    use PawnsTrait;
    use MarkersTrait;

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
        static::DbQuery(DataService::setupBoardPaths());

        static::DbQuery(DataService::setupActions());
        static::DbQuery(DataService::setupBoardActions());

        static::DbQuery(DataService::setupComponents());

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Init global values with their initial values.
        $this->patrol_cards->createCards($this->PATROL_CARD_ITEMS);

        // Missions

        $missionsDifficulty = (int) $this->tableOptions->get(100);

        if ($missionsDifficulty == 0) {
            $this->configureMissions(MISSION_MILICE_PARADE_DAY, MISSION_OFFICERS_MANSION);
        } else {
            $missions = [
                MISSION_MILICE_PARADE_DAY,
                MISSION_OFFICERS_MANSION,
                MISSION_SABOTAGE,
                MISSION_INFILTRATION,
                MISSION_GERMAN_SHEPARDS,
                MISSION_DOUBLE_AGENT,
                MISSION_UNDERGROUND_NEWSPAPER
            ];

            $keys = array_rand($missions, 2);

            $this->configureMissions($missions[$keys[0]], $missions[$keys[1]]);
        }

        // Dummy content.
        $this->setGameStateInitialValue("my_first_global_variable", 0);
        
        // Init game statistics.
        $this->initStat("table", "turns_number", 0);
        $this->initStat("player", "food_aquired", 0);
        $this->initStat("player", "medicine_aquired", 0);
        $this->initStat("player", "money_aquired", 0);
        $this->initStat("player", "weapon_aquired", 0);
        $this->initStat("player", "intel_aquired", 0);
        $this->initStat("player", "explosives_aquired", 0);
        $this->initStat("player", "workers_recruited", 0);

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    public function actPlaceWorker(int $spaceID): void {
        $spaceName = $this->getSpaceNameById($spaceID);

        // $this->updateSpace($spaceID, hasWorker: true);
        $workerID = $this->getNextAvailableWorker();
        $this->updateComponent($workerID, (string) $spaceID, "placed");

        $this->notify->all("workerPlaced", clienttranslate("Worker placed at " . $spaceName), array(
            "workerID" => $workerID,
            "spaceID" => $spaceID,
        ));

        if ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && in_array($spaceID, [1, 3, 5, 6, 9, 11]) && $this->countMarkers($spaceID) <= 0) {
            $this->placeMarker($spaceID);

            if ($this->checkMarkersInSpaces([1, 3, 5, 6, 9, 11])) {
                $cardID = $this->drawPatrolCard();
                $card = $this->PATROL_CARD_ITEMS[$cardID - 1];
                $doubleAgentLocation = $card['space_a'];
                $this->addSpaceAction($doubleAgentLocation, ACTION_COMPLETE_DOUBLE_AGENT_MISSION);
                $this->updateDarkLadyLocation((string) $doubleAgentLocation, 'placed');
                
                $this->notify->all("darkLadyFound", clienttranslate("Dark Lady found at " . $this->getSpaceNameById($card['space_a'])), array(
                    "cardId" => $cardID,
                    "location" => $doubleAgentLocation
                ));
            }
        }

        $this->gamestate->nextState("placePatrol");
    }

    public function stPlacePatrol(): void {
        $card = $this->PATROL_CARD_ITEMS[$this->drawPatrolCard() - 1];

        $spaceID = null;
        $emptySpaces = $this->getEmptySpaces();
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

            $placeSoldier = $this->getPatrolsToPlace() - $this->getActiveSoldiers() < $this->getPlacedMilice() + 1;

            $soldierID = $this->getNextAvailableSoldier();
            $miliceID = $this->getNextAvailableMilice();

            if ($placeSoldier) {
                // $this->updateSpace($spaceID, hasWorker: $arrestedOnsite, hasSoldier: true);
                $this->updateComponent($soldierID, (string) $spaceID, 'placed');
            } else {
                // $this->updateSpace($spaceID, hasWorker: $arrestedOnsite, hasMilice: true);
                $this->updateComponent($miliceID, (string) $spaceID, 'placed');
            }

            $this->notify->all("patrolPlaced", clienttranslate("Patrol placed at $spaceName"), array(
                "placeSoldier" => $placeSoldier,
                "patrolID" => $placeSoldier ? $soldierID : $miliceID,
                "spaceID" => $spaceID
            ));

            if ($arrestedOnsite) {
                $this->arrestWorker($spaceID);
            }
        }

        if ($this->getPlacedResistance() < $this->getActiveResistance()) {
            $this->gamestate->nextState("placeWorker");
        } else if ($this->getPlacedMilice() + $this->getPlacedSoldiers() < $this->getPatrolsToPlace()) {
            $this->gamestate->nextState("placePatrol");
        } else {
            $this->gamestate->nextState("activateWorker");
        }
    }

    public function actActivateWorker(int $spaceID): void {
        $this->updateActiveSpace($spaceID);
        
        $spaceName = $this->getSpaceNameById($spaceID);

        $this->notify->all("spaceActivated", clienttranslate("Worker at $spaceName activated"), array(
            "spaceID" => $spaceID
        ));

        $possibleActions = $this->getPossibleActions($spaceID);

        if (count($possibleActions) == 1) {
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
            $this->updateDarkLadyLocation('off_board', 'NaN');
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
        $this->resetActiveSpace();

        if ($this->getIsMoleInserted() && $this->getPlacedResistance() == 1) {
            $this->gamestate->nextState("roundEnd");
        } else if ($this->getPlacedResistance() > 0) {
            $this->gamestate->nextState("activateWorker");
        } 
        // else if ($this->getPlacedResistance() == 1) {
        //     $spacesWithResistanceWorkers = $this->getSpacesWithResistanceWorkers();
        //     $spaceID = $spacesWithResistanceWorkers[0];
        //     $this->updateActiveSpace($spaceID);
        //     $possibleActions = $this->getPossibleActions($spaceID);

        //     if (count($possibleActions) == 1) {
        //         $this->actTakeAction($possibleActions[0]['action_name']);
        //     } else {
        //         $this->gamestate->nextState("takeAction");
        //     }
        // } 
        else {
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
            $this->placeTokens($spaceID, $supplyType, $quantity);
        }

        $this->returnWorker($this->getActiveSpace());

        $this->gamestate->nextstate("nextWorker");
    }

    public function stRoundEnd(): void {
        $this->incStat(1, "turns_number");
        $round = $this->getRoundNumber() + 1;
        $this->updateRoundNumber($round);
        
        if ($this->isParadeDay($round)) {
            $this->updateMorale($this->getMorale() - 1);
        }

        if ($this->getMorale() <= 0 || $this->getActiveResistance() <= 0 || $round >= 15 || ($this->getActiveResistance() == 1 && $this->getIsMoleInserted())) {
            $this->gamestate->nextstate("gameEnd");
        } else {
            foreach (array_merge($this->getMilice(), $this->getSoldiers()) as $patrol) {
                if ($patrol['state'] === 'placed') {
                    $this->updateComponent($patrol['name'], 'off_board', 'active');

                    $this->notify->all("patrolRemoved", '', array(
                        "patrolID" => $patrol['name']
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
        $morale = $this->getMorale();
        $miliceID = $this->getMiliceIdByLocation((string) $spaceID);

        $this->updateComponent($miliceID, 'off_board', 'NaN');

        $this->notify->all("patrolRemoved", clienttranslate("Milice patrol at " . $this->getSpaceNameById($spaceID) . " shot"), array(
            "patrolID" => $miliceID
        ));

        $this->spendTokens(RESOURCE_WEAPON, 1);
        $this->setShotToday(true);
        $this->updateActiveSoldiers($this->getActiveSoldiers() + 1);
        $this->updateMorale($morale - 1);
        if ($morale - 1 <= 0) {
            $this->gamestate->nextState("endGame");
        } else {
            $this->gamestate->nextState("nextWorker");
        }
    }

    public function actSelectRoom(string $roomID): void {
        $activeSpace = $this->getActiveSpace();

        $this->placeRoom($roomID, $activeSpace); 
        $this->addSpareRoomActions($activeSpace, $roomID);
        $this->spendTokens(RESOURCE_MONEY, 2);

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
            "emptySpaces" => $this->getEmptySpaces()
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
        return [
            "spacesWithMilice" => $this->getSpacesWithMilice()
        ];
    }

    public function argSelectRoom(): array {
        return [
            "availableRooms" => $this->getAvailableRooms(),
            "roomsDescriptions" => ROOM_DESCRIPTIONS
        ];
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

        $workerID = $this->getWorkerIdByLocation((string) $spaceID);
        $this->updateComponent($workerID, 'safe_house', 'active');

        $this->notify->all("workerRemoved", clienttranslate("Worker safely returned from $spaceName"), array(
            "activeSpace" => $spaceID
        ));
    }

    public function arrestWorker(int $spaceID): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $workerID = $this->getWorkerIdByLocation((string) $spaceID);

        $this->updateComponent($workerID, 'arrest', 'arrested');
        
        $this->notify->all("workerRemoved", clienttranslate("Worker arrested at " . $spaceName), array(
            "activeSpace" => $spaceID
        ));
    }

    public function removeWorker(int $spaceID): void {
         if (!in_array($spaceID, $this->getSpacesWithResistanceWorkers())) {
            return;
        }

        $spaceName = $this->getSpaceNameById($spaceID);
        $this->updateComponent($this->getWorkerIdByLocation((string) $spaceID), 'off_board', 'removed');
        
        $this->notify->all("workerRemoved", clienttranslate("Worker removed from " . $spaceName), array(
            "activeSpace" => $spaceID
        ));
    }

    public function returnOrArrest(int $spaceID): void {
        if ($this->checkEscapeRoute($spaceID)) {
            $this->returnWorker($spaceID);
        } else {
            $this->arrestWorker($spaceID);
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

    protected function addSpareRoomActions(int $spaceID, string $roomID): void {
        switch (str_replace("room_", "", $roomID)) {
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
        }
    } 

    // SAVE ACTION

    protected function saveAction(string $actionName): void {
        switch($actionName) {
            case ACTION_GET_WEAPON:
                $this->spendTokens(RESOURCE_MONEY);
                $this->getTokens(RESOURCE_WEAPON);
                $this->incStat(1, "weapon_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_FOOD:
                $this->getTokens(RESOURCE_FOOD);
                $this->incStat(1, "food_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_MEDICINE:
                $this->getTokens(RESOURCE_MEDICINE);
                $this->incStat(1, "medicine_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_INTEL:
                $this->getTokens(RESOURCE_INTEL);
                $this->incStat(1, "intel_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_MONEY_FOR_FOOD:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->spendTokens(RESOURCE_FOOD);
                    $this->getTokens(RESOURCE_MONEY);
                    $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                    $this->decrementMorale();
                }
                break;
            case ACTION_GET_MONEY_FOR_MEDICINE:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->spendTokens(RESOURCE_MEDICINE);
                    $this->getTokens(RESOURCE_MONEY);
                    $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                    $this->decrementMorale();
                }
                break;
            case ACTION_PAY_FOR_MORALE:
                $this->incrementMorale();
                $this->spendTokens(RESOURCE_MEDICINE);
                $this->spendTokens(RESOURCE_FOOD);
                break;
            case ACTION_GET_WORKER:
                $this->spendTokens(RESOURCE_FOOD);
                $this->recruitWorker();
                $this->incStat(1, "workers_recruited", $this->getActivePlayerId());
                break;
            case ACTION_COLLECT_ITEMS:
                $activeSpace = $this->getActiveSpace();
                $itemType = $this->getTokenTypeInSpace($activeSpace);
                $quantity = $this->getTokenQuantityInSpace($activeSpace);

                $this->incStat($quantity, $itemType . "_aquired", $this->getActivePlayerId());
                $this->collectTokens($itemType, $activeSpace);
                break;
            case ACTION_WRITE_GRAFFITI:
                $this->placeMarker($this->getActiveSpace());
                break;
            case ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION:
                $this->completeMission(MISSION_MILICE_PARADE_DAY);
                $this->spendTokens(RESOURCE_WEAPON);
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
                $this->getTokens(RESOURCE_MONEY);
                $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_EXPLOSIVES:
                $this->spendTokens(RESOURCE_MEDICINE);
                $this->getTokens(RESOURCE_EXPLOSIVES);
                $this->incStat(1, "explosives_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_3_FOOD:
                $this->getTokens(RESOURCE_FOOD, 3);
                $this->incStat(3, "food_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_3_MEDICINE:
                $this->getTokens(RESOURCE_MEDICINE, 3);
                $this->incStat(3, "medicine_aquired", $this->getActivePlayerId());
                break;
            case ACTION_INCREASE_MORALE:
                $morale = $this->getMorale();
                $this->updateMorale($morale + 1);
                break;
            case ACTION_INFILTRATE_FACTORY:
                $activeSpace = $this->getActiveSpace();
                $this->placeMarker($activeSpace);
                $this->addMissionSpace($activeSpace + 1, MISSION_SABOTAGE);
                if ($activeSpace == 19 || $activeSpace == 22) {
                    $this->addSpaceAction($activeSpace + 1, ACTION_SABOTAGE_FACTORY);
                } else {
                    $this->addSpaceAction($activeSpace + 1, ACTION_INFILTRATE_FACTORY);
                }
                break;
            case ACTION_SABOTAGE_FACTORY:
                $this->spendTokens(RESOURCE_EXPLOSIVES, 2);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_SABOTAGE);
                break;
            case ACTION_DELIVER_INTEL:
                $activeSpace = $this->getActiveSpace();
                $this->spendTokens(RESOURCE_INTEL, 2);
                if ($activeSpace == 20 || $activeSpace == 23) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_UNDERGROUND_NEWSPAPER);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addMissionSpace($activeSpace + 1, MISSION_UNDERGROUND_NEWSPAPER);
                    $this->addSpaceAction($activeSpace + 1, ACTION_DELIVER_INTEL);
                }
                break;
            case ACTION_INSERT_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->setMoleInserted(true);
                $this->spendTokens(RESOURCE_INTEL, 2);
                $this->addMissionSpace($activeSpace + 1, MISSION_INFILTRATION);
                $this->addSpaceAction($activeSpace + 1, ACTION_RECOVER_MOLE);
                break;
            case ACTION_RECOVER_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->spendTokens(RESOURCE_WEAPON, 1);
                $this->spendTokens(RESOURCE_EXPLOSIVES, 1);
                $this->setMoleInserted(false);
                $this->returnOrArrest($activeSpace - 1);
                $this->returnOrArrest($activeSpace);
                $this->completeMission(MISSION_INFILTRATION);
                break;
            case ACTION_POISON_SHEPARDS:
                $activeSpace = $this->getActiveSpace();
                $this->spendTokens(RESOURCE_FOOD, 1);
                $this->spendTokens(RESOURCE_MEDICINE, 1);
                if ($activeSpace == 20 || $activeSpace == 23) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_GERMAN_SHEPARDS);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addMissionSpace($activeSpace + 1, MISSION_GERMAN_SHEPARDS);
                    $this->addSpaceAction($activeSpace + 1, ACTION_POISON_SHEPARDS);
                }
                break;
        }
    } 

    protected function getAllDatas() {
        $result = [];

        $result["currentPlayerID"] = (int) $this->getCurrentPlayerId();

        $result["round"] = $this->getRoundNumber();
        $result["morale"] = $this->getMorale();
        $result["activeSoldiers"] = $this->getActiveSoldiers();

        $result["board"] = $this->getBoard();
        $result["placedTokens"] = $this->getPlacedTokens();
        $result["placedRooms"] = $this->getPlacedRooms();
        $result["spacesWithMarkers"] = $this->getSpacesWithMarkers();

        $result["discardedPatrolCards"] = $this->patrol_cards->getCardsInLocation('discard');

        $result["resources"] = $this->getAllResources();
        
        $selectedMissions = $this->getSelectedMissions();    
        $result["selectedMissions"] = [
            $selectedMissions[0]['location'] => $selectedMissions[0]['name'],
            $selectedMissions[1]['location'] => $selectedMissions[1]['name']
        ];
        $result["completedMissions"] = $this->getCompletedMissions();

        $result["rooms"] = $this->getRooms();
        
        $result["placedResistance"] = $this->getPlacedResistance();
        $result["activeResistance"] = $this->getActiveResistance();
        $result["resistanceToRecruit"] = $this->getResistanceToRecruit();

        $result["resistanceWorkers"] = $this->getResistanceWorkers();
        $result["milice"] = $this->getMilice();
        $result["soldiers"] = $this->getSoldiers();

        $result["darkLadyLocation"] = $this->getDarkLadyLocation();

        return $result;
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
                    return (($this->countMarkers($spaceID) === 0) || (($this->countMarkers($spaceID) === 1) && $this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT))) && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                    break;
                case ACTION_COMPLETE_OFFICERS_MANSION_MISSION:
                    return ((!$this->getIsMissionSelected(MISSION_DOUBLE_AGENT) || $this->getIsMissionCompleted(MISSION_DOUBLE_AGENT)) && $this->countMarkersInSpaces([1, 3, 11]) == 3) || ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && $this->countMarkersInSpaces([1, 3, 11]) == 6) && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                    break;
                case ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION:
                    return $this->getResource(RESOURCE_WEAPON) > 0 && $this->isParadeDay($this->getRoundNumber());
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

        return max($this->getActiveResistance(), $morale_to_patrols_map[$this->getMorale()]);
    }

    // BOARD PATHS

    protected function getBoardPaths(): array {
        $result = (array) $this->getCollectionFromDb("
            SELECT path_id, space_id_start, space_id_end
            FROM board_path;
        ");

        $roundNumber = $this->getRoundNumber();
        $paradeCanHappen = $this->getIsMissionSelected(MISSION_MILICE_PARADE_DAY) && !$this->getIsMissionCompleted(MISSION_MILICE_PARADE_DAY);
        
        return array_filter($result, function ($connection) use ($roundNumber, $paradeCanHappen) {
            return !(
                    (($connection['space_id_start'] == '1' && $connection['space_id_end'] == '2') || ($connection['space_id_start'] == '2' && $connection['space_id_end'] == '1')) && 
                    $this->isParadeDay($roundNumber) &&
                    $paradeCanHappen
                );
        });
        return $result;
    }

    // ACTIONS

    protected function getIsSafe(string $actionName): bool {
        return (bool) $this->getUniqueValueFromDb("SELECT is_safe FROM action WHERE action_name = \"$actionName\";");
    }

    // UPDATES

    

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

        $spacesWithMilice = $this->getSpacesWithMilice();
        $spacesWithSoldiers = $this->getSpacesWithSoldiers();

        for ($i = 0; $i < count($spacesToCheck); $i++) {
            $spaceID = $spacesToCheck[$i];
            $isSafe = (bool) $board[$spaceID]['is_safe'];

            if ($isSafe) {
                return true;
            } else if (!in_array($spaceID, $spacesWithMilice) && !in_array($spaceID, $spacesWithSoldiers)) { 
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

        return false;
    }

    // PREDICATES

    protected function isParadeDay(int $day): bool {
        return $day > 0 && ($day === 14 || $day % 3 === 0);
    }
    
    public function getGameProgression() {        
        $round = $this->getRoundNumber();
        return min(100, intval($round * 100 / 15));
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
