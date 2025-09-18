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
 * states.inc.php
 *
 * Maquis game states description
 *
 */

//    !! It is not a good idea to modify this file when a game is running !!
require_once("modules/php/constants.inc.php");

$machinestates = [

    // The initial state. Please do not modify.

    ST_BGA_GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => ST_PLAYER_PLACE_WORKER]
    ),

    ST_PLAYER_PLACE_WORKER => [
        "name" => "placeWorker",
        "descriptionmyturn" => clienttranslate('${you} must place a worker'),
        "type" => "activeplayer",
        "args" => "argPlaceWorker",
        "possibleactions" => ["actPlaceWorker"],
        "transitions" => ["placePatrol" => ST_GAME_PLACE_PATROL]
    ],

    ST_GAME_PLACE_PATROL => [
        "name" => "placePatrol",
        "description" => '',
        "type" => "game",
        "action" => "stPlacePatrol",
        "transitions" => ["placePatrol" => ST_GAME_PLACE_PATROL, "placeWorker" => ST_PLAYER_PLACE_WORKER, "activateWorker" => ST_PLAYER_ACTIVATE_WORKER]
    ],
    
    ST_PLAYER_ACTIVATE_WORKER => [
        "name" => "activateWorker",
        "descriptionmyturn" => clienttranslate('${you} must activate a worker'),
        "type" => "activeplayer",
        "args" => "argActivateWorker",
        "possibleactions" => [
            "actActivateWorker",
            "actDeclareShootingMilice"
        ],
        "transitions" => ["takeAction" => ST_PLAYER_TAKE_ACTION, "shootMilice" => ST_PLAYER_SHOOT_MILICE, "nextWorker" => ST_GAME_NEXT_WORKER]
    ],

    ST_PLAYER_SHOOT_MILICE => [
        "name" => "shootMilice",
        "descriptionmyturn" => clienttranslate('${you} must shoot a milice'),
        "type" => "activeplayer",
        "args" => "argShootMilice",
        "possibleactions" => [
            "actShootMilice",
            "actBack"
        ],
        "transitions" => ["nextWorker" => ST_GAME_NEXT_WORKER, "endGame" => ST_BGA_GAME_END]
    ],

    ST_PLAYER_TAKE_ACTION => [
        "name" => "takeAction",
        "descriptionmyturn" => clienttranslate('${you} must take an action'),
        "type" => "activeplayer",
        "args" => "argTakeAction",
        "possibleactions" => [
            "actTakeAction",
            "actBack"
        ],
        "transitions" => [
            "nextWorker" => ST_GAME_NEXT_WORKER, 
            "airdrop" => ST_PLAYER_AIRDROP_SELECT_FIELD, 
            "selectRoom" => ST_PLAYER_SELECT_ROOM,
            "removeWorker" => ST_PLAYER_REMOVE_WORKER, 
            "gameEnd" => ST_BGA_GAME_END]
    ],

    ST_PLAYER_AIRDROP_SELECT_FIELD => [
        "name" => "airdropSelectField",
        "descriptionmyturn" => clienttranslate('${you} must select empty field'),
        "type" => "activeplayer",
        "args" => "argSelectField",
        "possibleactions" => [
            "actSelectField"
        ],
        "transitions" => ["airdropSelectSupplies" => ST_PLAYER_AIRDROP_SELECT_SUPPLIES],
    ],

    ST_PLAYER_AIRDROP_SELECT_SUPPLIES => [
        "name" => "airdropSelectSupplies",
        "descriptionmyturn" => clienttranslate('${you} must select wanted resources'),
        "type" => "activeplayer",
        "args" => "argSelectSupplies",
        "possibleactions" => [
            "actSelectSupplies"
        ],
        "transitions" => ["nextWorker" => ST_GAME_NEXT_WORKER]
    ],

    ST_PLAYER_SELECT_ROOM => [
        "name" => "selectSpareRoom",
        "descriptionmyturn" => clienttranslate('${you} must select the room tile'),
        "type" => "activeplayer",
        "args" => "argSelectRoom",
        "possibleactions" => [
            "actSelectRoom"
        ],
        "transitions" => ["nextWorker" => ST_GAME_NEXT_WORKER],
    ],

    ST_PLAYER_REMOVE_WORKER => [
        "name" => "removeWorker",
        "descriptionmyturn" => clienttranslate('${you} must remove a worker'),
        "type" => "activeplayer",
        "args" => "argRemoveWorker",
        "possibleactions" => [
            "actRemoveWorker"
        ],
        "transitions" => ["nextWorker" => ST_GAME_NEXT_WORKER],
    ],

    ST_GAME_NEXT_WORKER => [
        "name" => "nextWorker",
        "description" => "",
        "type" => "game",
        "action" => "stNextWorker",
        "transitions" => ["activateWorker" => ST_PLAYER_ACTIVATE_WORKER, "takeAction" => ST_PLAYER_TAKE_ACTION, "nextWorker" => ST_GAME_NEXT_WORKER, "roundEnd" => ST_GAME_ROUND_END],
    ],  

    ST_GAME_ROUND_END => [
        "name" => "roundEnd",
        "description" => "",
        "type" => "game",
        "action" => "stRoundEnd",
        "transitions" => ["placeWorker" => ST_PLAYER_PLACE_WORKER, "gameEnd" => ST_BGA_GAME_END],
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    ST_BGA_GAME_END => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



