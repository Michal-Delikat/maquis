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
require_once("ComponentsTrait.php");
require_once("BoardTrait.php");
require_once("MissionsTrait.php");
require_once("GlobalsTrait.php");
require_once("RoomsTrait.php");
require_once("ResourcesTrait.php");
require_once("PatrolCardsTrait.php");
require_once("PlayerTrait.php");
require_once("PawnsTrait.php");
require_once("MarkersTrait.php");
require_once("BoardActionsTrait.php");

const BOARD = 'BOARD_STATE';

class Game extends \Table {
    use ComponentsTrait;
    use BoardTrait;
    use MissionsTrait;
    use GlobalsTrait;
    use ResourcesTrait;
    use PatrolCardsTrait;
    use RoomsTrait;
    use PlayerTrait;
    use PawnsTrait;
    use MarkersTrait;
    use BoardActionsTrait;

    private array $PATROL_CARD_ITEMS;
    private array $ACTIONS;
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
        
        $this->initGameStateLabels([
            "active_space" => 10,
            "selected_field" => 11,
            "shot_today" => 12,
            "explosives_at_bridge_planted" => 13,
            "my_first_game_variant" => 100,
            "my_second_game_variant" => 101,
        ]);

        require('material.inc.php');

        $this->PATROL_CARD_ITEMS = PATROL_CARD_ITEMS;
        $this->ACTIONS = ACTIONS;
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

        $this->reattributeColorsBasedOnPreferences($players, $gameinfos["player_colors"]);
        $this->reloadPlayersBasicInfos();

        // Add master data to DB
        static::DbQuery(DataService::setupBoard());
        static::DbQuery(DataService::setupBoardPaths());
        static::DbQuery(DataService::setupBoardActions());
        static::DbQuery(DataService::setupComponents());

        $this->patrol_cards->createCards($this->PATROL_CARD_ITEMS);

        // Initialize globals
        $this->setGameStateInitialValue("active_space", 0);
        $this->setGameStateInitialValue("selected_field", 0);
        $this->setGameStateInitialValue("shot_today", false);
        $this->setGameStateInitialValue("explosives_at_bridge_planted", false);

         // Initalize game statistics
        $this->initStat("table", "turns_number", 0);
        $this->initStat("player", "food_aquired", 0);
        $this->initStat("player", "medicine_aquired", 0);
        $this->initStat("player", "money_aquired", 0);
        $this->initStat("player", "weapon_aquired", 0);
        $this->initStat("player", "intel_aquired", 0);
        $this->initStat("player", "explosives_aquired", 0);
        $this->initStat("player", "workers_recruited", 0);

        // Configure missions
        $missionA = (int) $this->tableOptions->get(100);
        $missionB = (int) $this->tableOptions->get(101);

        $zeroStarMissions = array(MISSION_MILICE_PARADE_DAY, MISSION_OFFICERS_MANSION);
        $oneStarMissions = array(
                MISSION_SABOTAGE, 
                MISSION_UNDERGROUND_NEWSPAPER,
                MISSION_INFILTRATION, 
                MISSION_GERMAN_SHEPARDS,
                MISSION_DOUBLE_AGENT
        );
        $twoStarMissions = array(
            MISSION_AID_THE_SPY,
            MISSION_ASSASSINATION,
            MISSION_DESTROY_THE_TRAIN,
            MISSION_LIBERATE_THE_TOWN,
            MISSION_CODED_MESSAGES,
            MISSION_TAKE_OUT_THE_BRIDGES,
            MISSION_BOMB_FOR_THE_OFFICER
        );
        $threeStarMissions = array(
            MISSION_MILICE_HQ,
            MISSION_BOMB_THE_BARRACKS,
            MISSION_FREE_THE_RESISTANCE_LEADER,
            MISSION_DESTROY_AA_GUNS
        );

        while ($missionA === $missionB) {
            if ($missionA <= 1) {
                $missionB = array_rand($zeroStarMissions);
            } else if ($missionA <= 6) {
                $missionB = array_rand($oneStarMissions) + count($zeroStarMissions);
            } else if ($missionA <= 13) {
                $missionB = array_rand($twoStarMissions) + count($zeroStarMissions) + count($oneStarMissions);
            } else {
                $missionB = array_rand($threeStarMissions) + count($zeroStarMissions) + count($oneStarMissions) + count($twoStarMissions);
            }
        }

        $allMissions = array_merge($zeroStarMissions, $oneStarMissions, $twoStarMissions, $threeStarMissions);
        $this->configureMissions($allMissions[$missionA], $allMissions[$missionB]);

        $this->gainResources(RESOURCE_FAKE_ID, 4);

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    public function actPlaceWorker(int $spaceID): void {
        $this->setActiveSpace($spaceID);
        $workerID = $this->getLastAvailableWorker();
        $this->updateComponent($workerID, (string) $spaceID, "placed");

        $this->notify->all("workerMoved", clienttranslate('Worker placed at ${spaceName}'), array(
            "workerID" => $workerID,
            "spaceID" => $spaceID,
            "spaceName" => $this->getSpaceNameById($spaceID)
        ));

        $doubleAgentSpaces = [RUE_BARADAT, PONT_DU_NORD, DOCTOR, POOR_DISTRICT, RADIO_A, PONT_LEVEQUE];
        if ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && in_array($spaceID, $doubleAgentSpaces) && $this->countMarkers($spaceID) <= 0) {
            $this->placeMarker($spaceID);

            if ($this->checkMarkersInSpaces($doubleAgentSpaces)) {
                $cardID = $this->drawPatrolCard();
                $card = $this->PATROL_CARD_ITEMS[$cardID - 1];
                $doubleAgentLocation = $card['space_a'];
                $this->addSpaceAction($doubleAgentLocation, ACTION_COMPLETE_DOUBLE_AGENT_MISSION);
                $this->setDarkLadyLocation((string) $doubleAgentLocation, 'placed');

                $this->notify->all("darkLadyFound", clienttranslate('Dark Lady found at ${locationName}'), array(
                    "cardId" => $cardID,
                    "location" => $doubleAgentLocation,
                    "locationName" => $this->getSpaceNameById($doubleAgentLocation)
                ));
            }
        }

        if ($this->getResource(RESOURCE_FAKE_ID) && !in_array($spaceID, [LEFT_FIELD, RIGHT_FIELD, CAFE])) {
            $this->gamestate->nextState("placeFakeId");
        } else {
            $this->gamestate->nextState("placePatrol");
        }
    }

    public function actPlaceFakeId(): void {
        $activeSpace = $this->getActiveSpace();

        $this->spendResources(RESOURCE_FAKE_ID);
        $this->placeTokens($activeSpace, RESOURCE_FAKE_ID);

        $this->gamestate->nextState("placePatrol");
    }

    public function actDontPlaceFakeId(): void {
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
            
            if ($placeSoldier) {
                $soldierID = $this->getNextActiveSoldier();
                $this->updateComponent($soldierID, (string) $spaceID, 'placed');
            } else {
                $miliceID = $this->getNextActiveMilice();
                $this->updateComponent($miliceID, (string) $spaceID, 'placed');
            }

            $this->notify->all("patrolPlaced", clienttranslate('Patrol placed at ${spaceName}'), array(
                "placeSoldier" => $placeSoldier,
                "patrolID" => $placeSoldier ? $soldierID : $miliceID,
                "spaceID" => $spaceID,
                "spaceName" => $spaceName
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
        $this->setActiveSpace($spaceID);
        
        $spaceName = $this->getSpaceNameById($spaceID);

        $this->notify->all("spaceActivated", clienttranslate('Worker at ${spaceName} activated'), array(
            "spaceID" => $spaceID,
            "spaceName" => $spaceName
        ));

        $possibleActions = $this->getPossibleActions($spaceID);

        if (count($possibleActions) === 0) {
            $this->actTakeAction(ACTION_RETURN);
        } else if ($this->getIsFixerInLocation($spaceID)) {
            if ($this->getResource(RESOURCE_MONEY) === 0) {
                $this->actTakeAction(ACTION_RETURN);
            } else {
                $this->gamestate->nextState("useFixer");
            }
        } else {
            $this->gamestate->nextState("takeAction");
        }
    }

    public function actTakeAction(string $actionName): void {
        // $this->notify->all("actionTaken", clienttranslate("Action selected: " . $actionName), array());
        $activeSpace = $this->getActiveSpace();

        $escapeStatus = $this->checkEscapeRoute();

        if ($escapeStatus["fakeIdUsed"]) {
            $this->removeFakeId($activeSpace);
        }

        if ($actionName === ACTION_GET_SPARE_ROOM) {
            $this->gamestate->nextstate("selectRoom");    
        } else if ($actionName === ACTION_INSERT_MOLE) {
            $this->saveAction(ACTION_INSERT_MOLE);

            $this->gamestate->nextState("nextWorker");
        } else if ($actionName === ACTION_TRAIN_A_CRYPTOGRAPHER) {
            $this->saveAction(ACTION_TRAIN_A_CRYPTOGRAPHER);

            $this->gamestate->nextState("nextWorker");
        } else if ($actionName === ACTION_COMPLETE_DOUBLE_AGENT_MISSION) {
            $this->setDarkLadyLocation('off_board', 'NaN');
            $this->completeMission(MISSION_DOUBLE_AGENT);
            foreach([RUE_BARADAT, PONT_DU_NORD, DOCTOR, POOR_DISTRICT, RADIO_A, PONT_LEVEQUE] as $space) {
                $this->removeMarker($space);
            }

            if ($this->getPlayerScore() >= 2) {
                $this->gamestate->nextState("gameEnd");
            } else {
                $this->gamestate->nextState("removeWorker");
            }
        } else if ($escapeStatus["escapeFound"]) {
            if ($actionName === ACTION_AIRDROP) {
                $this->gamestate->nextstate("airdrop");
            } else if ($actionName === ACTION_USE_FIXER) {
                $this->gamestate->nextState("useFixer");
            } else {
                $this->saveAction($actionName);
                $this->returnWorker($activeSpace);

                if ($this->getPlayerScore() >= 2) {
                    $this->gamestate->nextState("gameEnd");
                }

                $this->gamestate->nextState("nextWorker");
            }
        } else {
            if ($actionName !== ACTION_RETURN && $this->getIsSafe($actionName)) {
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

        if ($this->getIsMoleInserted() && ($this->getPlacedResistance() === 1)) {
            $this->gamestate->nextState("roundEnd");
        } else if ($this->getPlacedResistance() > 0) {
            $this->gamestate->nextState("activateWorker");
        } else if ($this->getExplosivesAtBridgePlanted()) {
            $this->gamestate->nextState("removeBridge");
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
            $this->placeTokens($spaceID, $supplyType, $quantity);
        }

        $this->returnWorker($this->getActiveSpace());

        $this->gamestate->nextstate("nextWorker");
    }

    public function stRoundEnd(): void {
        $this->incStat(1, "turns_number");
        $round = $this->getRoundNumber() + 1;
        $this->setRoundNumber($round);
        
        if ($this->isParadeDay()) {
            $this->setMorale($this->getMorale() - 1);
        }

        if ($this->getMorale() <= 0 || $this->getActiveResistance() <= 0 || $round >= 15 || ($this->getActiveResistance() == 1 && $this->getIsMoleInserted())) {
            $this->gamestate->nextstate("gameEnd");
        } else {
            foreach (array_merge(array_reverse($this->getMilice()), array_reverse($this->getSoldiers())) as $patrol) {
                if ($patrol['state'] === 'placed') {
                    $this->updateComponent($patrol['name'], 'barracks', 'active');

                    $this->notify->all("patrolReturned", '', array(
                        "patrolID" => $patrol['name']
                    ));
                }
            }

            if ($this->getIsCryptographerPlaced() && $round === 11) {
                $this->returnWorker((int) $this->getSpaceIdWithCryptographer());
                $this->completeMission(MISSION_CODED_MESSAGES);
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

    public function stPseudoGameEnd(): void {
        if ($this->getIsMissionSelected(MISSION_LIBERATE_THE_TOWN) && ($this->getMorale() >= 4) && ($this->getResource(RESOURCE_WEAPON) >= 3)) {
            $this->completeMission(MISSION_LIBERATE_THE_TOWN);
        }
        if ($this->getIsMissionSelected(MISSION_DESTROY_AA_GUNS) && (($this->countAAGunsPlaced() - (int) $this->checkMarkersInSpaces([MISSION_B_SPACE_A])) <= 2)) {
            $this->completeMission(MISSION_DESTROY_AA_GUNS);
        } 

        $playerScore = $this->getPlayerScore();

        $this->setPlayerScore((int) ($playerScore >= 2));

        $this->gamestate->nextState("gameEnd");
    }

    public function actDeclareShootingMilice(): void {
        $this->gamestate->nextState("shootMilice");
    }

    public function actShootMilice(int $spaceID): void {
        $morale = $this->getMorale();
        $miliceID = $this->getMiliceIdByLocation((string) $spaceID);

        $this->updateComponent($miliceID, 'off_board', 'NaN');

        $this->notify->all("patrolRemoved", clienttranslate('Milice patrol at ${spaceName} shot. Active milice: ${activeMilice}'), array(
            "patrolID" => $miliceID,
            "spaceName" => $this->getSpaceNameById($spaceID),
            "activeMilice" => $this->getActiveMilice()
        ));

        $this->spendResources(RESOURCE_WEAPON, 1);
        $this->setShotToday(true);
        $this->setActiveSoldiers($this->getActiveSoldiers() + 1);
        $this->updateComponent($this->getNextInactiveSoldier(), 'barracks', 'active');
        $this->setMorale($morale - 1);
        if ($this->getIsMissionSelected(MISSION_ASSASSINATION) && (($this->getActiveMilice() + $this->getPlacedMilice()) <= 0) && ($this->getPlayerScore() === 1)) {
            $this->completeMission(MISSION_ASSASSINATION);
            $this->gamestate->nextState("gameEnd");
        } else if ($morale - 1 <= 0) {
            $this->gamestate->nextState("gameEnd");
        } else {
            $this->gamestate->nextState("nextWorker");
        }
    }

    public function actSelectRoom(string $roomID): void {
        $activeSpace = $this->getActiveSpace();

        $this->placeRoom($roomID, $activeSpace); 
        $this->addSpareRoomActions($activeSpace, $roomID);
        $this->spendResources(RESOURCE_MONEY, 2);

        $this->notify->all("roomPlaced", clienttranslate('Room placed'), array(
            "roomID" => $roomID,
            "spaceID" => $activeSpace
        ));

        $this->returnOrArrest($activeSpace);

        $this->gamestate->nextState("nextWorker");
    }

    public function actBack(): void {
        $this->gamestate->nextState("nextWorker");
    }

    public function actRemoveWorker(int $spaceID): void {
        $this->removeWorker($spaceID);

        $this->gamestate->nextState("nextWorker");
    }

    public function actRemoveBridge(int $spaceID): void {
        $this->placeMarker($spaceID);

        if ($spaceID === TOP_BRIDGE) {
            $this->removePath(PONT_DU_NORD, BLACK_MARKET);
        } else {
            $this->removePath(POOR_DISTRICT, BLACK_MARKET);
        }

        $this->notify->all("bridgeRemoved", clienttranslate('Bridge removed'), array(
            "spaceID" => $spaceID
        ));

        $this->setExplosivesAtBridgePlanted(false);

        if ($this->countMarkersInSpaces([TOP_BRIDGE, BOTTOM_BRIDGE]) === 2) {
            $this->completeMission(MISSION_TAKE_OUT_THE_BRIDGES);
        }

        $this->gamestate->nextState("roundEnd");
    }

    public function actUseFixer(string $actionName) {
        $this->spendResources(RESOURCE_MONEY);
        $this->actTakeAction($actionName);
    }

    // ARGS

    public function argPlaceWorker(): array {
        return [
            "emptySpaces" => $this->getEmptySpaces()
        ];
    }

    public function argActivateWorker(): array {
        $resistanceWorkersLocations = $this->getSpacesWithResistanceWorkers();
        if ($this->getIsMoleInserted() || $this->getIsCryptographerPlaced()) {
            $spaceIdWithMole = $this->getSpaceIdWithMole();
            $spaceIdWithCryptographer = $this->getSpaceIdWithCryptographer();

            $resistanceWorkersLocations = array_filter($resistanceWorkersLocations, function($spaceId) use ($spaceIdWithMole, $spaceIdWithCryptographer) {
                return $spaceId !== $spaceIdWithMole && $spaceId !== $spaceIdWithCryptographer;
            });
        }

        return [
            "spaces" => $resistanceWorkersLocations,
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
                "airdropOptionDescription" => clienttranslate("Airdrop 3 food")
            ], 
            [
                "resourceName" => RESOURCE_MONEY,
                "airdropOptionDescription" => clienttranslate("Airdrop 1 money")
            ], 
            [
                "resourceName" => RESOURCE_WEAPON,
                "airdropOptionDescription" => clienttranslate("Airdrop 1 weapon")
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

    public function argUseFixer(): array {
        return [
            "actions" => $this->getPossibleActions(FIXER)
        ];
    }

    public function argRemoveWorker(): array {
        return $this->getSpacesWithResistanceWorkers();
    }

    public function argRemoveBridge(): array {
        return $this->getBridgesWithMarkers();
    }

    protected function saveAction(string $actionName): void {
        switch($actionName) {
            // TODO: remove stats
            case ACTION_BUY_WEAPON:
                $this->spendResources(RESOURCE_MONEY);
                $this->gainResources(RESOURCE_WEAPON);
                $this->incStat(1, "weapon_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_FOOD:
                $this->gainResources(RESOURCE_FOOD);
                $this->incStat(1, "food_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_MEDICINE:
                $this->gainResources(RESOURCE_MEDICINE);
                $this->incStat(1, "medicine_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_INTEL:
                $this->gainResources(RESOURCE_INTEL);
                $this->incStat(1, "intel_aquired", $this->getActivePlayerId());
                break;
            // TODO: remove conditions
            case ACTION_GET_MONEY_FOR_FOOD:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->spendResources(RESOURCE_FOOD);
                    $this->gainResources(RESOURCE_MONEY);
                    $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                    $this->decrementMorale();
                }
                break;
            case ACTION_GET_MONEY_FOR_MEDICINE:
                if ($this->getAvailableResource(RESOURCE_MONEY) > 0) {
                    $this->spendResources(RESOURCE_MEDICINE);
                    $this->gainResources(RESOURCE_MONEY);
                    $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                    $this->decrementMorale();
                }
                break;
            case ACTION_PAY_FOR_MORALE:
                $this->incrementMorale();
                $this->spendResources(RESOURCE_MEDICINE);
                $this->spendResources(RESOURCE_FOOD);
                break;
            case ACTION_GET_WORKER:
                $this->spendResources(RESOURCE_FOOD);
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
                $this->spendResources(RESOURCE_WEAPON);
                $this->incrementMorale($this->getMorale());
                $this->arrestWorker(RUE_BARADAT);
                break;
            case ACTION_COMPLETE_OFFICERS_MANSION_MISSION:
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_OFFICERS_MANSION);
                foreach([RUE_BARADAT, PONT_DU_NORD, PONT_LEVEQUE] as $space) {
                    $this->removeMarker($space);
                }
                break;
            case ACTION_GET_MONEY:
                $this->gainResources(RESOURCE_MONEY);
                $this->incStat(1, "money_aquired", $this->getActivePlayerId());
                break;
            case ACTION_BUY_EXPLOSIVES:
                $this->spendResources(RESOURCE_MEDICINE);
                $this->gainResources(RESOURCE_EXPLOSIVES);
                $this->incStat(1, "explosives_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_3_FOOD:
                $this->gainResources(RESOURCE_FOOD, 3);
                $this->incStat(3, "food_aquired", $this->getActivePlayerId());
                break;
            case ACTION_GET_3_MEDICINE:
                $this->gainResources(RESOURCE_MEDICINE, 3);
                $this->incStat(3, "medicine_aquired", $this->getActivePlayerId());
                break;
            case ACTION_INCREASE_MORALE:
                $morale = $this->getMorale();
                $this->setMorale($morale + 1);
                break;
            case ACTION_INFILTRATE_FACTORY:
                $activeSpace = $this->getActiveSpace();
                $this->placeMarker($activeSpace);
                $this->addMissionSpace($activeSpace + 1);
                if ($activeSpace === MISSION_A_SPACE_B || $activeSpace === MISSION_B_SPACE_B) {
                    $this->addSpaceAction($activeSpace + 1, ACTION_SABOTAGE_FACTORY);
                } else {
                    $this->addSpaceAction($activeSpace + 1, ACTION_INFILTRATE_FACTORY);
                }
                break;
            case ACTION_SABOTAGE_FACTORY:
                $this->spendResources(RESOURCE_EXPLOSIVES, 2);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_SABOTAGE);
                break;
            case ACTION_DELIVER_INTEL:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_INTEL, 2);
                if ($activeSpace === MISSION_A_SPACE_C || $activeSpace === MISSION_B_SPACE_C) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_UNDERGROUND_NEWSPAPER);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addMissionSpace($activeSpace + 1);
                    $this->addSpaceAction($activeSpace + 1, ACTION_DELIVER_INTEL);
                }
                break;
            case ACTION_INSERT_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_INTEL, 2);

                $moleID = $this->getWorkerIdByLocation((string) $activeSpace);
                $this->updateComponent($moleID, (string) $activeSpace, 'mole');

                $this->addMissionSpace($activeSpace + 1);
                $this->addSpaceAction($activeSpace + 1, ACTION_RECOVER_MOLE);
                break;
            case ACTION_RECOVER_MOLE:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_WEAPON, 1);
                $this->spendResources(RESOURCE_EXPLOSIVES, 1);
                $this->returnOrArrest($activeSpace - 1);
                $this->returnOrArrest($activeSpace);
                $this->completeMission(MISSION_INFILTRATION);
                break;
            case ACTION_POISON_SHEPARDS:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_FOOD, 1);
                $this->spendResources(RESOURCE_MEDICINE, 1);
                if ($activeSpace === MISSION_A_SPACE_C || $activeSpace === MISSION_B_SPACE_C) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_GERMAN_SHEPARDS);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addMissionSpace($activeSpace + 1);
                    $this->addSpaceAction($activeSpace + 1, ACTION_POISON_SHEPARDS);
                }
                break;
            case ACTION_DELIVER_2_WEAPONS:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_WEAPON, 2);
                $this->returnOrArrest($activeSpace);
                $this->placeMarker($activeSpace);
                $this->addMissionSpace($activeSpace + 1);
                $this->addSpaceAction($activeSpace + 1, ACTION_DELIVER_MONEY_AND_2_FOOD);
                break;
            case ACTION_DELIVER_MONEY_AND_2_FOOD:
                $this->spendResources(RESOURCE_MONEY);
                $this->spendResources(RESOURCE_FOOD, 2);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_AID_THE_SPY);
                break;
            case ACTION_DELIVER_3_EXPLOSIVES:
                $this->spendResources(RESOURCE_EXPLOSIVES, 3);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_DESTROY_THE_TRAIN);
            case ACTION_TRAIN_A_CRYPTOGRAPHER:
                $this->spendResources(RESOURCE_FOOD);
                $this->spendResources(RESOURCE_MONEY);
                $this->spendResources(RESOURCE_WEAPON);

                $activeSpace = (string) $this->getActiveSpace();
                $cryptographerID = $this->getWorkerIdByLocation($activeSpace);
                $this->updateComponent($cryptographerID, $activeSpace, 'cryptographer');
                break;
            case ACTION_DELIVER_2_EXPLOSIVES:
                $this->spendResources(RESOURCE_EXPLOSIVES, 2);
                $this->setExplosivesAtBridgePlanted(true);
                break;
            case ACTION_DELIVER_EXPLOSIVES_AND_WEAPON:
                $this->spendResources(RESOURCE_EXPLOSIVES);
                $this->spendResources(RESOURCE_WEAPON);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_BOMB_FOR_THE_OFFICER);
                break;
            case ACTION_DISCOVER_THE_PLANS:
                if ($this->getLocationByMissionName(MISSION_MILICE_HQ) === 'mission_card_a') {
                    $this->placeMarker(MISSION_A_SPACE_A);
                    $this->addMissionSpace(MISSION_A_SPACE_B);
                    $this->addSpaceAction(MISSION_A_SPACE_B, ACTION_DELIVER_2_POISON);
                } else {
                    $this->placeMarker(MISSION_B_SPACE_A);
                    $this->addMissionSpace(MISSION_B_SPACE_B);
                    $this->addSpaceAction(MISSION_B_SPACE_B, ACTION_DELIVER_2_POISON);
                }
                break;
            case ACTION_DELIVER_2_POISON:
                $this->spendResources(RESOURCE_POISON, 2);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_MILICE_HQ);
                break;
            case ACTION_BUY_POISON:
                $this->spendResources(RESOURCE_MEDICINE, 2);
                $this->gainResources(RESOURCE_POISON);
                break;
            case ACTION_FORGE_FAKE_ID:
                $this->spendResources(RESOURCE_MONEY, 2);
                $this->spendResources(RESOURCE_INTEL);
                $this->gainResources(RESOURCE_FAKE_ID);
                break;
            case ACTION_RECON_THE_BARRACKS:
                $activeSpace = $this->getActiveSpace();
                $this->placeMarker($activeSpace);
                $this->addMissionSpace($activeSpace + 1);
                if ($activeSpace === MISSION_B_SPACE_A) {
                    $this->addSpaceAction($activeSpace + 1, ACTION_RECON_THE_BARRACKS);
                } else {
                    $this->addSpaceAction($activeSpace + 1, ACTION_BOMB_THE_BARRACKS);
                }
                break;
            case ACTION_BOMB_THE_BARRACKS:
                $this->spendResources(RESOURCE_EXPLOSIVES, 2);
                $this->spendResources(RESOURCE_FAKE_ID);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_BOMB_THE_BARRACKS);
                break;
            case ACTION_BRIBE_THE_CLERK:
                $this->spendResources(RESOURCE_INTEL);
                $this->spendResources(RESOURCE_MONEY);
                $this->placeMarker(MISSION_B_SPACE_A);
                $this->addMissionSpace(MISSION_B_SPACE_B);
                $this->addSpaceAction(MISSION_B_SPACE_B, ACTION_KILL_THE_RESISTANCE_LEADER);
                $this->addSpaceAction(MISSION_B_SPACE_B, ACTION_FREE_THE_RESISTANCE_LEADER);
                break;
            case ACTION_KILL_THE_RESISTANCE_LEADER:
                $this->spendResources(RESOURCE_POISON);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_FREE_THE_RESISTANCE_LEADER);
                break;
            case ACTION_FREE_THE_RESISTANCE_LEADER:
                $this->spendResources(RESOURCE_FAKE_ID);
                $this->spendResources(RESOURCE_WEAPON, 2);
                $this->spendResources(RESOURCE_MEDICINE);
                $this->returnOrArrest(MISSION_B_SPACE_B);
                $this->setActiveSoldiers($this->getActiveSoldiers() + 2);
                $this->completeMission(MISSION_FREE_THE_RESISTANCE_LEADER);
                break;
            case ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES:
            case ACTION_DESTROY_AA_GUN_WITH_WEAPON:
                $resource = $actionName === ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES 
                    ? RESOURCE_EXPLOSIVES 
                    : RESOURCE_WEAPON;
                $this->spendResources($resource);
                $activeSpace = $this->getActiveSpace();
                if ($activeSpace === MISSION_B_SPACE_A) {
                    $this->placeMarker(MISSION_B_SPACE_A);
                    $this->returnOrArrest(MISSION_B_SPACE_A);
                } else {
                    $this->removeAAGun($activeSpace);
                }
                if ($this->countAAGunsPlaced() <= 0 && $this->checkMarkersInSpaces([MISSION_B_SPACE_A])) {
                    $this->completeMission(MISSION_DESTROY_AA_GUNS);
                }

                break;
        }
    } 

    protected function getAllDatas() {
        $result = [];

        $result["currentPlayerID"] = (int) $this->getCurrentPlayerId();

        $result["players"] = $this->getCollectionFromDb(
            "SELECT `player_id` `id`, `player_score` `score` FROM `player`"
        );

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

    protected function getPossibleActions(int $spaceID): array {
        $willNotGetArrested = $this->checkEscapeRoute()["escapeFound"];

        $result = (array) ($this->getCollectionFromDb("
            SELECT action_name
            FROM board_action 
            WHERE space_id = $spaceID;
        "));

        if (!$willNotGetArrested) {
            $result = array_filter($result, function($action) {
                return $this->getIsSafe($action["action_name"]);
            });
        } 

        $result = array_filter($result, function($action) use ($spaceID) {
            switch ($action['action_name']) {
                case ACTION_BUY_WEAPON:
                    return $this->getResource(RESOURCE_MONEY) > 0;
                case ACTION_AIRDROP:
                    return count($this->getEmptyFields()) > 0;
                case ACTION_PAY_FOR_MORALE:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getResource(RESOURCE_MEDICINE) > 0;
                case ACTION_GET_MONEY_FOR_FOOD:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getAvailableResource(RESOURCE_MONEY) > 0;
                case ACTION_GET_MONEY_FOR_MEDICINE:
                    return $this->getResource(RESOURCE_MEDICINE) > 0 && $this->getAvailableResource(RESOURCE_MONEY) > 0;
                case ACTION_WRITE_GRAFFITI:
                    return (($this->countMarkers($spaceID) === 0) || (($this->countMarkers($spaceID) === 1) && $this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT))) && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                case ACTION_COMPLETE_OFFICERS_MANSION_MISSION:
                    return ((!$this->getIsMissionSelected(MISSION_DOUBLE_AGENT) || $this->getIsMissionCompleted(MISSION_DOUBLE_AGENT)) && $this->countMarkersInSpaces([RUE_BARADAT, PONT_DU_NORD, PONT_LEVEQUE]) == 3) || ($this->getIsMissionSelected(MISSION_DOUBLE_AGENT) && !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT) && $this->countMarkersInSpaces([RUE_BARADAT, PONT_DU_NORD, PONT_LEVEQUE]) == 6) && !$this->getIsMissionCompleted(MISSION_OFFICERS_MANSION);
                case ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION:
                    return $this->getResource(RESOURCE_WEAPON) > 0 && $this->isParadeDay();
                case ACTION_GET_WORKER:
                    return $this->getResource(RESOURCE_FOOD) > 0 && $this->getResistanceToRecruit() > 0;
                case ACTION_COLLECT_ITEMS:
                    return $this->getTokenTypeInSpace($this->getActiveSpace()) !== TOKEN_AA_GUN;
                case ACTION_GET_SPARE_ROOM:
                    return !$this->getIsRoomPlaced($spaceID) && $this->getResource(RESOURCE_MONEY) >= 2;
                case ACTION_BUY_EXPLOSIVES:
                    return $this->getResource(RESOURCE_MEDICINE) >= 1;
                case ACTION_SABOTAGE_FACTORY:
                    return $this->getResource(RESOURCE_EXPLOSIVES) >= 1;
                case ACTION_DELIVER_INTEL:
                    return $this->getResource(RESOURCE_INTEL) >= 2;
                case ACTION_INSERT_MOLE:
                    return $this->getResource(RESOURCE_INTEL) >= 2;
                case ACTION_COMPLETE_DOUBLE_AGENT_MISSION:
                    return !$this->getIsMissionCompleted(MISSION_DOUBLE_AGENT);
                case ACTION_RECOVER_MOLE:
                    return ($this->getResource(RESOURCE_WEAPON) >= 1) && ($this->getResource(RESOURCE_EXPLOSIVES) >= 1);
                case ACTION_DELIVER_2_WEAPONS:
                    return ($this->getResource(RESOURCE_WEAPON) >= 2);
                case ACTION_DELIVER_MONEY_AND_2_FOOD:
                    return ($this->getResource(RESOURCE_FOOD) >= 2) && ($this->getResource(RESOURCE_MONEY) >= 1);
                case ACTION_DELIVER_3_EXPLOSIVES:
                    return ($this->getResource(RESOURCE_EXPLOSIVES) >= 3) && (in_array($this->getRoundNumber(), [6, 7, 8, 9]));
                case ACTION_TRAIN_A_CRYPTOGRAPHER:
                    return ($this->getResource(RESOURCE_FOOD) >= 1) && ($this->getResource(RESOURCE_MONEY) >= 1) && ($this->getResource(RESOURCE_WEAPON) >= 1) && ($this->getRoundNumber() <= 6);
                case ACTION_DELIVER_2_EXPLOSIVES:
                    return ($this->getResource(RESOURCE_EXPLOSIVES) >= 2) && !$this->getIsMissionCompleted(MISSION_TAKE_OUT_THE_BRIDGES);
                case ACTION_DELIVER_EXPLOSIVES_AND_WEAPON:
                    return $this->getMorale() >= 5 && $this->getResource(RESOURCE_EXPLOSIVES) >= 1 && $this->getResource(RESOURCE_WEAPON) >= 1;
                case ACTION_DELIVER_2_POISON:
                    return $this->getResource(RESOURCE_POISON) >= 2;
                case ACTION_BUY_POISON:
                    return $this->getResource(RESOURCE_MEDICINE) >= 2;
                case ACTION_FORGE_FAKE_ID:
                    return $this->getResource(RESOURCE_MONEY) >= 2 && $this->getResource(RESOURCE_INTEL);
                case ACTION_BOMB_THE_BARRACKS:
                    return $this->getResource(RESOURCE_EXPLOSIVES) >= 2 && $this->getResource(RESOURCE_FAKE_ID);
                case ACTION_BRIBE_THE_CLERK:
                    return $this->getRoundNumber() <= 5 && $this->getResource(RESOURCE_MONEY) && $this->getResource(RESOURCE_INTEL);
                case ACTION_KILL_THE_RESISTANCE_LEADER:
                    return $this->getRoundNumber() <= 9 && $this->getResource(RESOURCE_POISON);
                case ACTION_FREE_THE_RESISTANCE_LEADER:
                    return $this->getRoundNumber() === 10 && $this->getResource(RESOURCE_FAKE_ID) && $this->getResource(RESOURCE_WEAPON) >= 2 && $this->getResource(RESOURCE_MEDICINE);
                case ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES:
                    return $this->getResource(RESOURCE_EXPLOSIVES) && ($this->getTokenTypeInSpace($this->getActiveSpace()) === TOKEN_AA_GUN || $this->getActiveSpace() === MISSION_B_SPACE_A);
                case ACTION_DESTROY_AA_GUN_WITH_WEAPON:
                    return $this->getResource(RESOURCE_WEAPON) && ($this->getTokenTypeInSpace($this->getActiveSpace()) === TOKEN_AA_GUN || $this->getActiveSpace() === MISSION_B_SPACE_A);
                case ACTION_USE_FIXER:
                    return $this->getResource(RESOURCE_MONEY);
                default:
                    return true;
            }
        });

        $actionDescriptions = [
            ACTION_INSERT_MOLE => clienttranslate("Insert mole"),
            ACTION_RECOVER_MOLE => clienttranslate("Recover mole and complete the mission"),
            ACTION_POISON_SHEPARDS => clienttranslate("Poison German Shepards"),
            ACTION_GET_SPARE_ROOM => clienttranslate("Get spare room"),
            ACTION_BUY_WEAPON => clienttranslate("Get weapon"),
            ACTION_GET_FOOD => clienttranslate("Get food"),
            ACTION_GET_MEDICINE => clienttranslate("Get medicine"),
            ACTION_GET_INTEL => clienttranslate("Get intel"),
            ACTION_GET_MONEY_FOR_FOOD => clienttranslate("Get money for food"),
            ACTION_GET_MONEY_FOR_MEDICINE => clienttranslate("Get money for medicine"),
            ACTION_PAY_FOR_MORALE => clienttranslate("Pay for morale"),
            ACTION_GET_WORKER => clienttranslate("Recruit worker"),
            ACTION_COLLECT_ITEMS => clienttranslate("Collect items"),
            ACTION_WRITE_GRAFFITI => clienttranslate("Write graffiti"),
            ACTION_GET_MONEY => clienttranslate("Get money"),
            ACTION_BUY_EXPLOSIVES => clienttranslate("Get explosives"),
            ACTION_GET_3_FOOD => clienttranslate("Get 3 food"),
            ACTION_GET_3_MEDICINE => clienttranslate("Get 3 medicine"),
            ACTION_INCREASE_MORALE => clienttranslate("Increase morale"),
            ACTION_INFILTRATE_FACTORY => clienttranslate("Infiltrate factory"),
            ACTION_SABOTAGE_FACTORY => clienttranslate("Sabotage factory"),
            ACTION_DELIVER_INTEL => clienttranslate("Deliver intel"),
            ACTION_AIRDROP => clienttranslate("Airdrop supplies onto an empty field"),
            ACTION_COMPLETE_OFFICERS_MANSION_MISSION => clienttranslate("Complete Officers Mansion mission"),
            ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION => clienttranslate("Complete Milice Parade Day mission"),
            ACTION_COMPLETE_DOUBLE_AGENT_MISSION => clienttranslate("Complete the mission"),
            ACTION_DELIVER_2_WEAPONS => clienttranslate("Deliver 2 Weapons"),
            ACTION_DELIVER_MONEY_AND_2_FOOD => clienttranslate("Deliver Money and 2 Food"),
            ACTION_DELIVER_3_EXPLOSIVES => clienttranslate("Deliver 3 Explosives"),
            ACTION_TRAIN_A_CRYPTOGRAPHER => clienttranslate("Train a Cryptographer"),
            ACTION_DELIVER_2_EXPLOSIVES => clienttranslate("Deliver 2 Explosives"),
            ACTION_DELIVER_EXPLOSIVES_AND_WEAPON => clienttranslate("Deliver Explosives and Weapon"),
            ACTION_DISCOVER_THE_PLANS => clienttranslate("Discover the Plans"),
            ACTION_DELIVER_2_POISON => clienttranslate("Deliver 2 Poison"),
            ACTION_BUY_POISON => clienttranslate("Buy poison"),
            ACTION_FORGE_FAKE_ID => clienttranslate("Forge fake ID"),
            ACTION_RECON_THE_BARRACKS => clienttranslate("Recon the Barracks"),
            ACTION_BOMB_THE_BARRACKS => clienttranslate("Bomb the Barracks"),
            ACTION_BRIBE_THE_CLERK => clienttranslate("Bribe the clerk"),
            ACTION_FREE_THE_RESISTANCE_LEADER => clienttranslate("Free the resistance leader"),
            ACTION_KILL_THE_RESISTANCE_LEADER => clienttranslate("Kill the resistance leader"),
            ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES => clienttranslate("Destroy AA gun with explosives"),
            ACTION_DESTROY_AA_GUN_WITH_WEAPON => clienttranslate("Destroy AA gun with weapon"),
            ACTION_USE_FIXER => clienttranslate("Use fixer"),
        ];

        foreach($result as &$action) {
            $action['action_description'] = $actionDescriptions[$action['action_name']] ?? "";

            switch($action['action_name']) {
                case ACTION_GET_FOOD:
                    if ($this->getAvailableResource(RESOURCE_FOOD) <= 0) {
                        $action['action_description'] .= " (" . clienttranslate("No effect") . ")";
                    }
                    break;
                case ACTION_GET_MEDICINE:
                    if ($this->getAvailableResource(RESOURCE_MEDICINE) <= 0) {
                        $action['action_description'] .= " (" . clienttranslate("No effect") . ")";
                    }
                    break;
                case ACTION_GET_INTEL:
                    if ($this->getAvailableResource(RESOURCE_INTEL) <= 0) {
                        $action['action_description'] .= " (" . clienttranslate("No effect") . ")";
                    }
                    break;
                case ACTION_GET_MONEY:
                    if ($this->getAvailableResource(RESOURCE_MONEY) <= 0) {
                        $action['action_description'] .= " (" . clienttranslate("No effect") . ")";
                    }
                    break;
                case ACTION_GET_MONEY_FOR_FOOD:
                case ACTION_GET_MONEY_FOR_MEDICINE:
                    if ($this->getMorale() === 1) {
                        $action['action_description'] .= " (" . clienttranslate("This will result in loosing the game") . ")";
                    } 
                    break;
                case ACTION_PAY_FOR_MORALE:
                    if ($this->getMorale() === 7) {
                        $action['action_description'] .= " (" . clienttranslate("Resources will be lost. Morale won't be gained") . ")";
                    }
                    break;
            }
        }

        return $result;
    }

    protected function getIsSafe(string $actionName): bool {
        return (bool) $this->ACTIONS[$actionName]['is_safe'];
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

    protected function isParadeDay(): bool {
        $day = $this->getRoundNumber();
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
