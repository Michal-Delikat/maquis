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

require_once("constants.inc.php");
require_once("Material.php");

require_once("Components.php");
require_once("Board.php");
require_once("Missions.php");
require_once("Globals.php");
require_once("Rooms.php");
require_once("Resources.php");
require_once("PatrolCards.php");
require_once("Player.php");
require_once("Pawns.php");
require_once("Markers.php");
require_once("BoardActions.php");
require_once("BoardPaths.php");

class Game extends \Table {
    use Components;
    use Board;
    use Missions;
    use Globals;
    use Resources;
    use PatrolCards;
    use Rooms;
    use Player;
    use Pawns;
    use Markers;
    use BoardActions;
    use BoardPaths;

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
            "soldiers_distracted" => 14,
            "difficulty_mode" => 100,
            "mission_a" => 101,
            "mission_b" => 102
        ]);

        $this->ACTIONS = Material::ACTIONS;
        $this->PATROL_CARD_ITEMS = Material::PATROL_CARD_ITEMS;
        $this->patrol_cards = $this->getNew("module.common.deck");  
        $this->patrol_cards->init("patrol_card");
        $this->patrol_cards->autoreshuffle_trigger = array('obj' => $this, 'method' => 'deckAutoReshuffle');
        $this->patrol_cards->shuffle('deck');
    }

    protected function setupNewGame($players, $options = []) {
        $this->setupPlayer($players);

        $this->patrol_cards->createCards($this->PATROL_CARD_ITEMS);

        // Initialize globals
        $this->setGameStateInitialValue("difficulty_mode", $this->tableOptions->get(100));
        $this->setGameStateInitialValue("active_space", 0);
        $this->setGameStateInitialValue("selected_field", 0);
        $this->setGameStateInitialValue("shot_today", false);
        $this->setGameStateInitialValue("explosives_at_bridge_planted", false);
        $this->setGameStateInitialValue("soldiers_distracted", false);

         // Initalize game statistics
        $this->initStat("table", "turns_number", 0);
        $this->initStat("player", "food_aquired", 0);
        $this->initStat("player", "medicine_aquired", 0);
        $this->initStat("player", "money_aquired", 0);
        $this->initStat("player", "weapon_aquired", 0);
        $this->initStat("player", "intel_aquired", 0);
        $this->initStat("player", "explosives_aquired", 0);
        $this->initStat("player", "workers_recruited", 0);

        $this->setupBoard();
        $this->setupBoardPaths();
        $this->setupBoardActions();
        $this->setupComponents($this->getDifficultyMode());

        // Configure missions
        $missionA = (int) $this->tableOptions->get(101);
        $missionB = (int) $this->tableOptions->get(102);

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

        // Activate first player once everything has been initialized and ready.
        $this->activeNextPlayer();
    }

    public function stRoundStart(): void {
        $this->setShotToday(false);
        $this->setSoldiersDistracted(false);

        if ($this->getIsMoleInserted()) {
            $cardId = $this->peekTopPatrolCardId();

            $this->notify->all("cardPeeked", '', array(
                "cardId" => $cardId
            ));
        }

        $this->gamestate->nextState("placeWorker");
    }

    public function stRoundEnd(): void {
        if ($this->getIsCryptographerPlaced() && $this->getRoundNumber() === 10) {
            $this->returnOrArrest((int) $this->getSpaceIdWithCryptographer());
            $this->completeMission(MISSION_CODED_MESSAGES);
        }

        if ($this->getIsGameWon()) {
            $this->gamestate->nextState("gameEnd");
        }

        foreach (array_merge(array_reverse($this->getMilice()), array_reverse($this->getSoldiers())) as $patrol) {
            if ($patrol['state'] === 'placed') {
                $this->updateComponent($patrol['name'], 'barracks', 'active');

                $this->notify->all("patrolReturned", '', array(
                    "patrolID" => $patrol['name']
                ));
            }
        }

        $this->setRoundNumber($this->getRoundNumber() + 1);

        $difficultyMode = $this->getDifficultyMode();
        
        if ((in_array($difficultyMode, [VERY_EASY, EASY, NORMAL, TRICKY]) && $this->isParadeDay()) || (in_array($difficultyMode, [HARD, VERY_HARD]) && $this->isDayWithTriangle())) {
            $this->setMorale($this->getMorale() - 1);
        }

        if (($this->getMorale() <= 0) || ($this->getActiveResistance() <= 0)) {
            $this->gamestate->nextstate("gameEnd");
        } else if ($this->getRoundNumber() >= 12 && in_array($this->getDifficultyMode(), [TRICKY, HARD, VERY_HARD])) {
            $this->gamestate->nextState("gameEnd");
        } else if ($this->getRoundNumber() >= 15) {
            if ($this->getDifficultyMode() === VERY_EASY) {
                $this->setRoundNumber(1);
                $this->gamestate->nextState("roundStart");
            } else {
                $this->gamestate->nextState("gameEnd");
            }
        } else {
            $this->gamestate->nextState("roundStart");
        }        
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

        $this->gamestate->nextState("takeAction");
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

            $this->notify->all("darkLadyLocationReminderRemoved", '');

            if ($this->getIsGameWon()) {
                $this->gamestate->nextState("gameEnd");
            } else {
                $this->gamestate->nextState("removeWorker");
            }
        } else if ($escapeStatus["escapeFound"]) {
            if ($actionName === ACTION_USE_FIXER) {
                $this->gamestate->nextState("useFixer");
            } else {
                $this->saveAction($actionName);
                $this->returnWorker($activeSpace);

                if ($this->getIsGameWon()) {
                    $this->gamestate->nextState("gameEnd");
                }

                $this->gamestate->nextState("nextWorker");
            }
        } else {
            if ($actionName !== ACTION_RETURN && $this->getIsSafe($actionName)) {
                $this->saveAction($actionName);
                
                if ($this->getIsGameWon()) {
                    $this->gamestate->nextState("gameEnd");
                }
            }
            $this->arrestWorker($activeSpace);

            $this->gamestate->nextState("nextWorker");
        }      
    }

    public function stNextWorker() {
        $this->resetActiveSpace();

        if ($this->getPlacedResistance() > 0) {
            $this->gamestate->nextState("activateWorker");
        } else if ($this->getExplosivesAtBridgePlanted()) {
            $this->gamestate->nextState("removeBridge");
        } else {
            $this->gamestate->nextState("roundEnd");
        }
    }

    public function stPseudoGameEnd(): void {
        if ($this->getIsMissionSelected(MISSION_LIBERATE_THE_TOWN) && ($this->getMorale() >= 4) && ($this->getResource(RESOURCE_WEAPON) >= 3)) {
            $this->completeMission(MISSION_LIBERATE_THE_TOWN);
        }
        if ($this->getIsMissionSelected(MISSION_DESTROY_AA_GUNS) && (($this->countAAGunsPlaced() - (int) $this->checkMarkersInSpaces([MISSION_B_SPACE_A])) <= 2)) {
            $this->completeMission(MISSION_DESTROY_AA_GUNS);
        } 

        $this->setPlayerScore((int) $this->getIsGameWon());

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
        if ($morale - 1 <= 0) {
            $this->gamestate->nextState("gameEnd");
        } else if ($this->getIsMissionSelected(MISSION_ASSASSINATION) && (($this->getPlacedMilice()) <= 0) && ($this->getPlayerScore() === 1)) {
            $this->completeMission(MISSION_ASSASSINATION);
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

    public function argShootMilice(): array {
        return [
            "spacesWithMilice" => $this->getSpacesWithMilice()
        ];
    }

    public function argSelectRoom(): array {
        return [
            "availableRooms" => $this->getAvailableRooms(),
            "roomsDescriptions" => Material::ROOM_DESCRIPTIONS
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

    protected function getAllDatas() {
        $result = [];

        // TODO: REMOVE AFTER IMPLEMENTING DIFFICULTY MODES
        $result["difficultyMode"] = $this->getDifficultyMode();
        $result["isNormal"] = $this->getDifficultyMode() === NORMAL;

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
        
        $result["threeStarMissionSelected"] = $this->getIsThreeStarMissionSelected();

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
            case ACTION_AIRDROP_FOOD:
                $emptyField = $this->getEmptyFields()[0];
                $veryEasyOrEasyDifficultyMode = $this->getDifficultyMode() === VERY_EASY || $this->getDifficultyMode() === EASY;
                $this->placeTokens($emptyField, RESOURCE_FOOD, $veryEasyOrEasyDifficultyMode ? 4 : 3);
                break;
            case ACTION_AIRDROP_MONEY:
                $emptyField = $this->getEmptyFields()[0];
                $veryEasyOrEasyDifficultyMode = $this->getDifficultyMode() === VERY_EASY || $this->getDifficultyMode() === EASY;
                $this->placeTokens($emptyField, RESOURCE_MONEY, $veryEasyOrEasyDifficultyMode ? 2 : 1);
                break;
            case ACTION_AIRDROP_WEAPON:
                $emptyField = $this->getEmptyFields()[0];
                $quantity = 0;
                $veryEasyDifficultyMode = $this->getDifficultyMode() === VERY_EASY;
                $this->placeTokens($emptyField, RESOURCE_WEAPON, $veryEasyDifficultyMode ? 2 : 1);
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
            case ACTION_DELIVER_2_INTEL:
                $activeSpace = $this->getActiveSpace();
                $this->spendResources(RESOURCE_INTEL, 2);
                if ($activeSpace === MISSION_A_SPACE_C || $activeSpace === MISSION_B_SPACE_C) {
                    $this->returnOrArrest($activeSpace);
                    $this->completeMission(MISSION_UNDERGROUND_NEWSPAPER);
                } else {
                    $this->placeMarker($activeSpace);
                    $this->addMissionSpace($activeSpace + 1);
                    $this->addSpaceAction($activeSpace + 1, ACTION_DELIVER_2_INTEL);
                }
                break;
            case ACTION_INSERT_MOLE:
                $this->spendResources(RESOURCE_INTEL, 2);
                
                $activeSpace = (string) $this->getActiveSpace();
                $moleId = $this->getWorkerIdByLocation($activeSpace);

                $this->insertMole($moleId, $activeSpace);

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
            case ACTION_PLANT_2_EXPLOSIVES:
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
                $this->decrementMorale();
                if ($this->getActiveSoldiers() <= 2) {
                    $this->completeMission(MISSION_MILICE_HQ, true);
                } else {
                    $this->completeMission(MISSION_MILICE_HQ);
                }
                $this->setActiveSoldiers($this->getActiveSoldiers() + 3);
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
                    $this->addSpaceAction(LEFT_FIELD, ACTION_DISTRACT_THE_SOLDIERS);
                    $this->addSpaceAction(RIGHT_FIELD, ACTION_DISTRACT_THE_SOLDIERS);
                }
                break;
            case ACTION_BOMB_THE_BARRACKS:
                $this->spendResources(RESOURCE_EXPLOSIVES, 2);
                $this->spendResources(RESOURCE_FAKE_ID);
                $this->returnOrArrest($this->getActiveSpace());
                $this->completeMission(MISSION_BOMB_THE_BARRACKS, $this->getSoldiersDistracted());
                break;
            case ACTION_DISTRACT_THE_SOLDIERS:
                $this->spendResources(RESOURCE_WEAPON);
                $this->returnWorker($this->getActiveSpace());
                $this->setSoldiersDistracted(true);
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
                $this->setMorale($this->getMorale() -  2);
                $this->completeMission(MISSION_FREE_THE_RESISTANCE_LEADER, true);
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
                    $this->completeMission(MISSION_DESTROY_AA_GUNS, true);
                }
                break;
        }
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
                case ACTION_AIRDROP_FOOD:
                    return !empty($this->getEmptyFields()) && $this->getAvailableResource(RESOURCE_FOOD);
                case ACTION_AIRDROP_MONEY:
                    return !empty($this->getEmptyFields()) && $this->getAvailableResource(RESOURCE_MONEY);
                case ACTION_AIRDROP_WEAPON:
                    return !empty($this->getEmptyFields()) && $this->getAvailableResource(RESOURCE_WEAPON);
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
                    return $this->getTokenQuantityInSpace($spaceID) && ($this->getTokenTypeInSpace($spaceID) !== TOKEN_AA_GUN);
                case ACTION_GET_SPARE_ROOM:
                    return !$this->getIsRoomPlaced($spaceID) && $this->getResource(RESOURCE_MONEY) >= 2;
                case ACTION_BUY_EXPLOSIVES:
                    return $this->getResource(RESOURCE_MEDICINE) >= 1;
                case ACTION_SABOTAGE_FACTORY:
                    return $this->getResource(RESOURCE_EXPLOSIVES) >= 1;
                case ACTION_DELIVER_2_INTEL:
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
                case ACTION_PLANT_2_EXPLOSIVES:
                    return ($this->getResource(RESOURCE_EXPLOSIVES) >= 2) && !$this->getIsMissionCompleted(MISSION_TAKE_OUT_THE_BRIDGES);
                case ACTION_DELIVER_EXPLOSIVES_AND_WEAPON:
                    return $this->getMorale() >= 5 && $this->getResource(RESOURCE_EXPLOSIVES) >= 1 && $this->getResource(RESOURCE_WEAPON) >= 1;
                case ACTION_DELIVER_2_POISON:
                    return $this->getResource(RESOURCE_POISON) >= 2;
                case ACTION_BUY_POISON:
                    return $this->getResource(RESOURCE_MEDICINE) >= 2;
                case ACTION_FORGE_FAKE_ID:
                    return $this->getResource(RESOURCE_MONEY) >= 2 && $this->getResource(RESOURCE_INTEL);
                case ACTION_DISCOVER_THE_PLANS:
                    return !$this->getIsMissionCompleted(MISSION_MILICE_HQ) && !$this->checkMarkersInSpaces([MISSION_B_SPACE_A]);
                case ACTION_BOMB_THE_BARRACKS:
                    return $this->getResource(RESOURCE_EXPLOSIVES) >= 2 && $this->getResource(RESOURCE_FAKE_ID);
                case ACTION_DISTRACT_THE_SOLDIERS:
                    return $this->getResource(RESOURCE_WEAPON) && !$this->getSoldiersDistracted() && !$this->getIsMissionCompleted(MISSION_BOMB_THE_BARRACKS);
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
            ACTION_GET_FOOD => clienttranslate("Get food"),
            ACTION_GET_MEDICINE => clienttranslate("Get medicine"),
            ACTION_GET_MONEY_FOR_FOOD => clienttranslate("Get money for food"),
            ACTION_GET_MONEY_FOR_MEDICINE => clienttranslate("Get money for medicine"),
            ACTION_PAY_FOR_MORALE => clienttranslate("Increase morale"),
            ACTION_GET_INTEL => clienttranslate("Get intel"),
            ACTION_BUY_WEAPON => clienttranslate("Get weapon"),
            ACTION_GET_WORKER => clienttranslate("Recruit worker"),
            ACTION_COLLECT_ITEMS => clienttranslate("Collect items"),
            ACTION_GET_SPARE_ROOM => clienttranslate("Get spare room"),
            ACTION_AIRDROP_FOOD => clienttranslate("Airdrop Food"),
            ACTION_AIRDROP_MONEY => clienttranslate("Airdrop Money"),
            ACTION_AIRDROP_WEAPON => clienttranslate("Airdrop Weapon"),
            
            ACTION_GET_MONEY => clienttranslate("Get money"),
            ACTION_BUY_EXPLOSIVES => clienttranslate("Get explosives"),
            ACTION_GET_3_FOOD => clienttranslate("Get 3 food"),
            ACTION_GET_3_MEDICINE => clienttranslate("Get 3 medicine"),
            ACTION_INCREASE_MORALE => clienttranslate("Increase morale"),
            ACTION_BUY_POISON => clienttranslate("Buy poison"),
            ACTION_FORGE_FAKE_ID => clienttranslate("Forge fake ID"),
            ACTION_USE_FIXER => clienttranslate("Use fixer"),

            ACTION_COMPLETE_MILICE_PARADE_DAY_MISSION => clienttranslate("Complete Milice Parade Day mission"),
            ACTION_WRITE_GRAFFITI => clienttranslate("Write graffiti"),
            ACTION_COMPLETE_OFFICERS_MANSION_MISSION => clienttranslate("Complete Officer's Mansion mission"),

            ACTION_INFILTRATE_FACTORY => clienttranslate("Infiltrate factory"),
            ACTION_SABOTAGE_FACTORY => clienttranslate("Sabotage factory and complete the mission"),
            ACTION_DELIVER_2_INTEL => clienttranslate("Deliver 2 intel"),
            ACTION_INSERT_MOLE => clienttranslate("Insert mole"),
            ACTION_RECOVER_MOLE => clienttranslate("Recover mole and complete the mission"),
            ACTION_POISON_SHEPARDS => clienttranslate("Poison German Shepards"),
            ACTION_COMPLETE_DOUBLE_AGENT_MISSION => clienttranslate("Meet Double Agent and complete the mission"),

            ACTION_DELIVER_2_WEAPONS => clienttranslate("Deliver 2 Weapons"),
            ACTION_DELIVER_MONEY_AND_2_FOOD => clienttranslate("Deliver Money and 2 Food; Complete mission"),
            ACTION_DELIVER_3_EXPLOSIVES => clienttranslate("Deliver 3 Explosives and complete mission"),
            ACTION_TRAIN_A_CRYPTOGRAPHER => clienttranslate("Train a Cryptographer"),
            ACTION_PLANT_2_EXPLOSIVES => clienttranslate("Plant 2 Explosives at a bridge"),
            ACTION_DELIVER_EXPLOSIVES_AND_WEAPON => clienttranslate("Deliver Explosives and Weapon; Complete the mission"),

            ACTION_DISCOVER_THE_PLANS => clienttranslate("Discover the Plans"),
            ACTION_DELIVER_2_POISON => clienttranslate("Deliver 2 Poison and complete the mission"),
            ACTION_RECON_THE_BARRACKS => clienttranslate("Recon the Barracks"),
            ACTION_DISTRACT_THE_SOLDIERS => clienttranslate("Distract the soldiers"),
            ACTION_BOMB_THE_BARRACKS => clienttranslate("Bomb the Barracks and complete the mission"),
            ACTION_BRIBE_THE_CLERK => clienttranslate("Bribe the clerk"),
            ACTION_KILL_THE_RESISTANCE_LEADER => clienttranslate("Kill the resistance leader and complete the mission"),
            ACTION_FREE_THE_RESISTANCE_LEADER => clienttranslate("Free the resistance leader and complete the mission"),
            ACTION_DESTROY_AA_GUN_WITH_EXPLOSIVES => clienttranslate("Destroy AA gun with explosives"),
            ACTION_DESTROY_AA_GUN_WITH_WEAPON => clienttranslate("Destroy AA gun with weapon"),
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
        return in_array($this->getRoundNumber(), [3, 6, 9, 12, 14]);
    }

    protected function isDayWithTriangle(): bool {
        return in_array($this->getRoundNumber(), [2, 5, 8, 10, 12]);
    }

    protected function getCanShoot(): bool {
        $weapon = $this->getResource('weapon');
        $placedMilice = $this->getPlacedMilice();
        return ($weapon > 0 && !$this->getShotToday() && $placedMilice > 0) && !($this->getIsMissionSelected(MISSION_GERMAN_SHEPARDS) && !$this->getIsMissionCompleted(MISSION_GERMAN_SHEPARDS));
    }

    protected function getIsGameWon(): bool {
        $isThreeStarMissionSelected = $this->getIsThreeStarMissionSelected();
        $playerScore = $this->getPlayerScore();
        return ($isThreeStarMissionSelected && ($playerScore >= 3)) || (!$isThreeStarMissionSelected && ($playerScore >= 2));
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
