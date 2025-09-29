/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * maquis.js
 *
 * MaquisSolo user interface script
 * 
 */

define([
    "dojo","dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    getLibUrl('bga-animations', '1.x'),
],
function (dojo, declare) {
    return declare("bgagame.maquis", ebg.core.gamegui, {
        constructor: function() {
            // console.log('maquis constructor');
              
            // Here, you can init the global variables of your user interface
            // Example:
            // this.myGlobalValue = 0;
        },
        
        setup: function(gamedatas) {
            // console.log("Starting game setup");

            let currentRound = parseInt(gamedatas.round);
            let currentMorale = parseInt(gamedatas.morale);
            let activeSoldiers = parseInt(gamedatas.activeSoldiers);

            let placedResistance = parseInt(gamedatas.placedResistance);
            let activeResistance = parseInt(gamedatas.activeResistance);
            let resistanceToRecruit = parseInt(gamedatas.resistanceToRecruit);

            let board = gamedatas.board;
            let placedTokens = Object.values(gamedatas.placedTokens);
            let spacesWithMarkers = Object.values(gamedatas.spacesWithMarkers);
            let placedRooms = Object.values(gamedatas.placedRooms);

            let discardedPatrolCards = gamedatas.discardedPatrolCards;

            let resources = gamedatas.resources;

            let selectedMissions = Object.values(gamedatas.selectedMissions);
            let completedMissions = Object.values(gamedatas.completedMissions);

            let rooms = Object.values(gamedatas.rooms);

            let resistanceWorkers = Object.values(gamedatas.resistanceWorkers);
            let milice = Object.values(gamedatas.milice);
            let soldiers = Object.values(gamedatas.soldiers);

            let player_id = gamedatas.currentPlayerID;

            let player_board_div = $('player_board_' + player_id);

            // PLAYER INFO

            dojo.place(`
                <div id="custom-player-board">
                    <div id="workers">
                        <div id="resistance">
                            <div id="resistance-worker-icon"></div>
                            <div id="resistance-worker-numbers">
                                <span id="placed-resistance">${placedResistance}</span>
                                <span>|</span>
                                <span id="active-resistance">${activeResistance}</span>
                                <span>|</span>
                                <span id="resistance-to-recruit">${resistanceToRecruit}</span>
                            </div>
                        </div>
                    </div>
                    <hr/>
                    <div id="resources"></div>
                </div>
            `, player_board_div);

            // RESOURCES
            Object.values(resources).forEach(([resource_name, quantity, available]) => dojo.place(`
                <div class="resource-box">
                    <div id="${resource_name}-icon" class="resource-icon">
                        <div class="resource-icon-circle"></div>
                    </div>
                    <span id=${resource_name}-quantity>${quantity}</span>/<span id=${resource_name}-available>${available}</span>
                <div>    
            `, 'resources'));

            dojo.place(`
                <div id="board-and-missions">
                    <div id="mission-cards">
                        <div id="mission-slot-1" class="mission-slot">
                            <div id="${selectedMissions[0].name}" class="card mission-card">
                                <div class="mission-card-back mission-card-face"></div>
                                <div class="mission-card-front mission-card-face"></div>
                                <div id="space-18" class="space mission-space mission-space-1">
                                    <div id="space-18-worker-space" class="worker-space"></div>
                                    <div id="space-18-marker-space" class="marker-space"></div>
                                    <div id="space-18-background-space" class="background-space"></div>
                                </div>
                                <div id="space-19" class="space mission-space mission-space-2">
                                    <div id="space-19-worker-space" class="worker-space"></div>
                                    <div id="space-19-marker-space" class="marker-space"></div>
                                    <div id="space-19-background-space" class="background-space"></div>
                                </div>
                                <div id="space-20" class="space mission-space mission-space-3">
                                    <div id="space-20-worker-space" class="worker-space"></div>
                                    <div id="space-20-marker-space" class="marker-space"></div>
                                    <div id="space-20-background-space" class="background-space"></div>
                                </div>
                            </div>
                        </div>
                        <div id="mission-slot-2" class="mission-slot">
                            <div id="${selectedMissions[1].name}" class="card mission-card">
                                <div class="mission-card-back mission-card-face"></div>
                                <div class="mission-card-front mission-card-face"></div>
                                <div id="space-21" class="space mission-space mission-space-1">
                                    <div id="space-21-worker-space" class="worker-space"></div>
                                    <div id="space-21-marker-space" class="marker-space"></div>
                                    <div id="space-21-background-space" class="background-space"></div>
                                </div>
                                <div id="space-22" class="space mission-space mission-space-2">
                                    <div id="space-22-worker-space" class="worker-space"></div>
                                    <div id="space-22-marker-space" class="marker-space"></div>
                                    <div id="space-22-background-space" class="background-space"></div>
                                </div>
                                <div id="space-23" class="space mission-space mission-space-3">
                                    <div id="space-23-worker-space" class="worker-space"></div>
                                    <div id="space-23-marker-space" class="marker-space"></div>
                                    <div id="space-23-background-space" class="background-space"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="board">
                        <div id="spaces"></div>
                        <div id="round-number-spaces"></div>
                    </div>
                </div>
                <div id="right-panel">
                    <div id="cards">
                        <div id="morale-and-soldiers-track" class="card">
                            <div id="morale-track"></div>
                            <div id="soldiers-track"></div>
                        </div>
                        <div id="patrol-deck" class="card"></div>
                        <div id="patrol-discard" class="card"></div>
                    </div>
                    <div id="room-tiles"></div>
                </div>
            `, 'game_play_area');

            // FLIP MISSIONS 

            completedMissions.forEach(mission => this.flipMission(mission['name']));

            // ADD TOOLTIPS TO MISSIONS

            selectedMissions.forEach(mission => {
                let description = null;
                switch(parseInt(mission.mission_id)) {
                    case 1:
                        description = `<p>
                            The Milice are holding parades around town - a
                            brave volunteer must show our defiance. They
                            probably won't make it back...<br><br>
                            
                            Deliver 1 Weapon to Rue Baradat on a Parade Day
                            (Day 3, 6, 9, 12, and 14). The Worker is Arrested.
                            Increase Morale by 1. Before this mission is
                            completed, the road between Rue Baradat and
                            Fence is blocked on Parade Days.
                        </p>`;
                        break;
                    case 2:
                        description = `<p>
                            The local commander has commandeered a fancy
                            house north of town. We need to make sure he
                            knows that he will never have us cowed.<br><br>
                            
                            Place a Worker on Rue Baradat, Pont Leveque and
                            Pont du Nord to write anti-fascist graffiti (place
                            markers during Action Phase to track graffiti).
                            Once all three locations are tagged, place a Worker
                            here to complete.
                        </p>`;
                        break;
                    case 3:
                        description = `<p>
                            The occupation runs a munitions factory on
                            the outskirts of town. Infiltrate and sabotage
                            the operation by any means possible.<br><br>
                            
                            A Worker must infiltrate the factory twice, 
                            then return a third day to deliver 
                            Explosives.
                        </p>`;
                        break;
                    case 4:
                        description = `<p>
                            Get the word out and counter the 
                            propaganda of the occupation.<br><br>
                            
                            Deliver 2 Intel to this location on three
                            separate days.
                        </p>`;
                        break;
                    case 5:
                        description = `<p>
                                The best place to collect reconnaissance is often
                                from the inside. Insert a mole into the Milice.<br><br>

                                Deliver 2 Intel to this location. The Worker
                                must remain here until another Worker delivers
                                1 Weapon and 1 Explosive. While the first
                                Worker is here, you may look at the top card of
                                the Patrol deck before the placement phase.
                            </p>`;
                        break;
                    case 6:
                        description = `<p>
                            The occupiers have dogs to help with patrols. 
                            Use poison to eliminate them<br><br>
                            
                            Deliver 1 Medicine and 1 Food to this 
                            location on three separate days. Before this 
                            mission is completed, Milice units may not 
                            be eliminated.
                        </p>`;
                    case 7:
                        description = `<p>
                            We must enearth the double agent known only as the "Dark Lady"...<br><br>

                            Visit all locations on the west side of the
                            river except the Fence and Spare Room. Once 
                            completed, turn over the top patrol card; 
                            Location #1 is the location of the "Dark Lady".
                            Visit that location again to complete the mission.
                            Remove one Worker permanently from the game.
                        </p>`;
                        break;
                }
                    
                this.addTooltipHtml(`mission-${mission.mission_id}`, `
                <div class="mission-tooltip-wrapper">
                    <div class="mission-description">
                        ${description}
                    </div>
                </div>`);
            });

            // MORALE TRACK

            for (let i = 0; i <= 7; i++) {
                dojo.place(`<div id="morale-track-space-${i}" class="morale-track-space"></div>`, "morale-track");
            }

            dojo.place(`<div id="marker-morale" class="marker"></div>`, `morale-track-space-${currentMorale}`);

            // SOLDIER TRACK

            for (let i = 0; i <= 5; i++) {
                dojo.place(`<div id="soldiers-track-space-${i}" class="soldiers-track-space"></div>`, "soldiers-track");
            }

            dojo.place('<div id="marker-soldiers" class="marker"></div>', `soldiers-track-space-${activeSoldiers}`);

            // ROUND NUMBER
            
            for (let i = 0; i < 16; i++) {
                dojo.place(`<div id="round-number-space-${i}" class="round-number-space"></div>`, 'round-number-spaces')
            }

            dojo.place(`<div id="marker-round" class="marker"></div>`, `round-number-space-${currentRound}`);
            
            // BOARD SPACES

            for (let i = 0; i < 17; i++) {
                dojo.place(`
                    <div id="space-${i + 1}" class="space board-space">
                        <div id="space-${i + 1}-room-tile-space" class="room-tile-space"></div>
                        <div id="space-${i + 1}-token-spaces" class="token-spaces"></div>
                        <div id="space-${i + 1}-marker-spaces"></div>
                        <div id="space-${i + 1}-worker-space" class="worker-space"></div>
                        <div id="space-${i + 1}-background-space" class="background-space"></div>
                    </div>
                `, 'spaces');

                for (let j = 0; j < 5; j++) {
                    dojo.place(`
                        <div 
                            id="space-${i + 1}-token-space-${j + 1}" 
                            class="token-space"
                            style="top: ${20 * j}%"
                        ></div>
                    `, `space-${i + 1}-token-spaces`);
                }

                for (let j = 0; j < 2; j++) {
                    dojo.place(`
                        <div 
                            id="space-${i + 1}-marker-space-${j + 1}" 
                            class="marker-space"
                            style="left: ${50 * j}%"
                        ></div>
                    `, `space-${i + 1}-marker-spaces`);
                }
            }

            // PAWNS

            resistanceWorkers.forEach(worker => { 
                if (worker.state === 'placed') this.placeWorker(worker.name, worker.location, false)
            });
            milice.forEach(milice => {
                if (milice.state === 'placed') this.placeMilice(milice.name, milice.location, false)
            });
            soldiers.forEach(soldier => {
                if (soldier.state === 'placed') this.placeSoldier(soldier.name, soldier.location, false)
            });

            for (let i = 1; i <= 23; i++) {
                if (board[i]) {
                    if (parseInt(board[i].dark_lady_location)) {
                        this.placeDarkLadyLocationReminder(parseInt(board[i].space_id));
                    }
                }
            }

            // MARKERS
            spacesWithMarkers.forEach(space => this.placeMissionMarker(space['location'], space['marker_number'], false));

            // TOKENS

            this.placeTokens(placedTokens, false);
            
            // ROOM TILES
            
            rooms.forEach((room) => dojo.place(`
                    <div id="${room.name}-tile-container" class="room-tile-container">
                        <div id="room-tile-${room.name}" class="room-tile">
                            <div class="circle-shape"></div>
                            <div class="rectangle-shape"></div>
                        </div>
                    <div>
                `, `room-tiles`));
            
            placedRooms.forEach(room => {
                this.placeRoomTile(room.location, room.name, false);
            });

            // PATROL DISCARD

            Object.values(discardedPatrolCards).forEach((card) => this.discardPatrolCard(card.type_arg, true));

            // Event Listeners

            dojo.query('.background-space').connect('click', this, "onSpaceClicked");
 
            // Setup game notifications to handle (see "setupNotifications" method below)
            this.setupNotifications();

            // console.log("Ending game setup");
        },

        ///////////////////////////////////////////////////
        //// Game & client states
        
        onEnteringState: function(stateName, args) {
            // console.log('Entering state: ' + stateName, args);
            
            switch(stateName) {
                case 'placeWorker':
                    const emptySpaces = Object.values(args.args.emptySpaces);
                                        
                    emptySpaces.forEach(spaceID => {
                        let space = dojo.byId(`space-${spaceID}-background-space`);
                        if (space) dojo.addClass(space, 'available-space');
                    });

                    break;

                case 'activateWorker':
                    const spacesWithWorkers = Object.values(args.args.spaces);

                    spacesWithWorkers.forEach(spaceID => {
                        let space = dojo.byId(`space-${spaceID}-background-space`);
                        if (space) dojo.addClass(space, 'space-with-available-worker');
                    });                        

                    break;

                case 'takeAction':
                    const activeSpaceID = args.args.activeSpace;

                    let activeSpace = dojo.byId(`space-${activeSpaceID}-background-space`);
                    if (activeSpace) dojo.addClass(activeSpace, 'active-space');

                    break;

                case 'airdropSelectField':
                    const emptyFields = Object.values(args.args.emptyFields);
                    
                    emptyFields.forEach(field => {
                        let space = dojo.byId(`space-${field}-background-space`);
                        if (space) dojo.addClass(space, 'empty-field');
                    });

                    break;

                case 'shootMilice':
                    const spacesWithMilice = Object.values(args.args);

                    spacesWithMilice.forEach(spaceID => {
                        let space = dojo.byId(`space-${spaceID}-background-space`);
                        if (space) dojo.addClass(space, 'space-with-milice');
                    });
                    break;

                case 'removeWorker':
                    const spacesWithResistanceWorkers = Object.values(args.args);

                    spacesWithResistanceWorkers.forEach(spaceID => {
                        let space = dojo.byId(`space-${spaceID}-background-space`);
                        if (space) dojo.addClass(space, 'space-with-worker-to-remove');
                    });
                    break;
            }   
        },

        
        onLeavingState: function(stateName) {
            // console.log('Leaving state: ' + stateName);
            
            switch(stateName)
            {
                case 'placeWorker':
                    dojo.query('.available-space').removeClass('available-space');
                    break;

                case 'activateWorker':
                    dojo.query('.space-with-available-worker').removeClass('space-with-available-worker');
                    break;

                case 'takeAction':
                    dojo.query('.active-space').removeClass('active-space');
                    break;

                case 'airdropSelectSupplies':
                    dojo.query('.empty-field').removeClass('empty-field');
                    break;

                case 'shootMilice':
                    dojo.query('.space-with-milice').removeClass('space-with-milice');
                    break;

                case 'removeWorker':
                    dojo.query('.space-with-worker-to-remove').removeClass('space-with-worker-to-remove');
                    break;
            }          
        }, 
        
        onUpdateActionButtons: function(stateName, args) {
            // console.log('onUpdateActionButtons: ' + stateName, args);
                      
            if(this.isCurrentPlayerActive())
            {            
                switch(stateName) {
                    case 'activateWorker':
                        if (args.canShoot) {
                            this.addActionButton('actDeclareShootingMilice-btn', _('Shoot milice'), () => this.bgaPerformAction("actDeclareShootingMilice"), null, null, 'gray');
                        }
                        break;

                    case 'takeAction':
                        Object.values(args.actions).forEach(action => this.addActionButton('actTakeAction_' + `${action.action_name}`, (`${action.action_description}`), () => this.bgaPerformAction("actTakeAction", { actionName: action.action_name }), null, null, action.action_name == 'return' ? 'gray' : 'blue'));
                        this.addActionButton('actBack', _('Back'), () => this.bgaPerformAction("actBack"), null, null, 'red');
                        break;

                    case 'airdropSelectSupplies':
                        Object.values(args.options).forEach(option => this.addActionButton('actAirdropSelectSupplies_' + `${option["resourceName"]}`, (`${option["airdropOptionDescription"]}`), () => this.bgaPerformAction("actSelectSupplies", { supplyType: option["resourceName"]}), null, null, 'blue'));
                        break;

                    case 'selectSpareRoom':
                        Object.values(args.availableRooms).forEach(room => this.addActionButton('actSelectRoom_' + `${room.name}`, (`${args.roomsDescriptions[room.name.replace("room_", "")]}`), () => this.bgaPerformAction("actSelectRoom", { roomID: room.name}), null, null, 'blue'));
                        break;

                    case 'shootMilice':
                        this.addActionButton('actReturn', _('Back'), () => this.bgaPerformAction("actBack"), null, null, 'red');
                        break;
                }
            }
        },        

        ///////////////////////////////////////////////////
        //// Player's action's handlers

        onSpaceClicked: function(evt) {
            evt.preventDefault();
            evt.stopPropagation();

            var space = evt.currentTarget.id.split('-');
            var spaceID = space[1];
            
            if (evt.currentTarget.classList.contains('available-space')) {
                this.bgaPerformAction("actPlaceWorker", {
                    spaceID: spaceID
                });
            }
            else if (evt.currentTarget.classList.contains('space-with-available-worker')) {
                this.bgaPerformAction("actActivateWorker", {
                    spaceID: spaceID
                });
            }
            else if (evt.currentTarget.classList.contains('empty-field')) {
                this.bgaPerformAction("actSelectField", {
                    spaceID: spaceID
                });
            }
            else if (evt.currentTarget.classList.contains('space-with-milice')) {
                this.bgaPerformAction("actShootMilice", {
                    spaceID: spaceID
                });
            }
            else if (evt.currentTarget.classList.contains('space-with-worker-to-remove')) {
                this.bgaPerformAction("actRemoveWorker", {
                    spaceID: spaceID
                })
            }
        },
        
        ///////////////////////////////////////////////////
        //// Reaction to cometD notifications

        setupNotifications: function() {
            // console.log( 'notifications subscriptions setup' );
            
            // TODO: here, associate your game notifications with local methods
            
            // Example 1: standard notification handling
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            
            // Example 2: standard notification handling + tell the user interface to wait
            //            during 3 seconds after calling the method in order to let the players
            //            see what is happening in the game.
            // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
            // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
            //

            this.bgaSetupPromiseNotifications();
        },
        
        notif_workerPlaced: function({workerID, spaceID}) {
            this.placeWorker(workerID, spaceID);
        },

        notif_patrolPlaced: function({placeSoldier, patrolID, spaceID}) {
            if (placeSoldier) {
                this.placeSoldier(patrolID, spaceID);
            } else {
                this.placeMilice(patrolID, spaceID);
            }
        },
        
        notif_patrolCardDiscarded: function({patrolCardID}) {
            this.discardPatrolCard(patrolCardID);
        },

        notif_workerRemoved: function(notif) {
            this.removeWorker(notif.activeSpace);
        },

        notif_patrolRemoved: function({patrolID}) {
            this.removePatrol(patrolID);
        },

        notif_resistanceToRecruitUpdated: function({resistanceToRecruit}) {
            dojo.byId(`resistance-to-recruit`).innerHTML = resistanceToRecruit;
        },

        notif_placedMiliceUpdated: function({placedMilice}) {
            dojo.byId(`placed-milice`).innerHTML = placedMilice;
        },

        notif_roundNumberUpdated: function({round}) {
            this.moveRoundMarker(round);
        },

        notif_moraleUpdated: function({morale}) {
            this.moveMoraleMarker(morale);
        },

        notif_resourcesChanged: function({resource_name, quantity, available}) {
            dojo.byId(`${resource_name}-quantity`).innerHTML = quantity;
            dojo.byId(`${resource_name}-available`).innerHTML = available;
        },

        notif_activeResistanceUpdated: function(notif) {
            dojo.byId("active-resistance").innerHTML = notif.active_resistance;
        },

        notif_tokensPlaced: function({tokens}) {
            this.placeTokens(tokens);
        },

        notif_tokensCollected: function({tokenType, location}) {
            this.removeTokens(tokenType, location);
        },

        notif_activeSoldiersUpdated: function({soldierNumber}) {
            this.moveSoldiersMarker(soldierNumber);
        },

        notif_markerPlaced: function({spaceID, markerNumber}) {
            this.placeMissionMarker(spaceID, markerNumber);
        },

        notif_markerRemoved: function({spaceID, markerNumber}) {
            this.removeMarker(spaceID, markerNumber);
        },

        notif_missionCompleted: function({missionName, playerScore, playerId}) {
            dojo.byId("player_score_" + playerId).innerHTML = playerScore;

            this.flipMission(`mission_card_${missionName}`);
        },

        notif_roomPlaced: function({roomID, spaceID}) {
            this.placeRoomTile(spaceID, roomID);
        },

        notif_cardPeeked: function({cardId}) {
            this.displayModalWithCard(cardId, "Next Patrol card");
        },
        
        notif_patrolCardsShuffled: function() {
            dojo.query('.patrol-card').forEach(node => {
                dojo.destroy(node.id);
            });
        },

        notif_darkLadyFound: function({cardId}) {
            this.displayModalWithCard(cardId, "Dark Lady found at place #1");
        },

        // UTILITY

        smoothRemove: function(node) {
            const container = node.parentNode;
            const children = Array.from(container.children);

            // --- F: record first positions ---
            const firstRects = new Map();
            children.forEach(child => {
                firstRects.set(child, child.getBoundingClientRect());
            });

            // --- L: remove the node ---
            dojo.destroy(node);

            // --- L: record last positions ---
            children.forEach(child => {
                const lastRect = child.getBoundingClientRect();
                const firstRect = firstRects.get(child);

                if (!firstRect) return;

                // --- I: calculate deltas ---
                const dx = firstRect.left - lastRect.left;
                const dy = firstRect.top - lastRect.top;

                // Apply transform to invert position
                child.style.transform = `translate(${dx}px, ${dy}px)`;
                child.style.transition = "none"; // prevent immediate jump
            });

            // --- P: force reflow, then animate back ---
            requestAnimationFrame(() => {
                children.forEach(child => {
                    child.style.transition = "transform 300ms ease";
                    child.style.transform = "none";
                });
            });
        },
        
        placeWorker: async function(resistanceID, spaceID, animate = true) {
            dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `space-${spaceID}-worker-space`);            
            if (animate) {
                this.placeOnObject(`${resistanceID}`, 'resistance-worker-icon');
                const animation = this.slideToObject(`${resistanceID}`, `space-${spaceID}-worker-space`);
                await this.bgaPlayDojoAnimation(animation);
            }
        },
        
        placeMilice: async function(miliceID, spaceID, animate = true) {
            dojo.place(`<div id="${miliceID}" class="worker milice"></div>`, `space-${spaceID}-worker-space`);
            if (animate) {
                this.placeOnObject(`${miliceID}`, 'player_boards');
                const animation = this.slideToObject(`${miliceID}`, `space-${spaceID}-worker-space`);
                await this.bgaPlayDojoAnimation(animation);
            }
        },

        placeSoldier: async function(soldierID, spaceID, animate = true) {

            dojo.place(`<div id="${soldierID}" class="worker soldier"></div>`, `space-${spaceID}-worker-space`);
            if (animate) {
                this.placeOnObject(`${soldierID}`, 'player_boards');
                const animation = this.slideToObject(`${soldierID}`, `space-${spaceID}-worker-space`);
                await this.bgaPlayDojoAnimation(animation);
            }
        },

        discardPatrolCard: async function(patrolCardID, animate = true) {
            dojo.place(`
                <div id="patrol-${patrolCardID}" class="card patrol-card">
                    <div class="card patrol-card-back"></div>
                    <div class="card patrol-card-front"></div>
                </div>`, 'patrol-discard');
            if (animate) {
                this.placeOnObject(`patrol-${patrolCardID}`, 'patrol-deck');
                dojo.toggleClass(dojo.byId(`patrol-${patrolCardID}`), 'flipped');
                const slideAnimation = this.slideToObjectPos(`patrol-${patrolCardID}`, `patrol-discard`, 0, 0, 2000);
                await this.bgaPlayDojoAnimation(slideAnimation);
            }
        },

        removeWorker: async function(spaceID) {
            let space = dojo.byId(`space-${spaceID}-worker-space`);
            let resistanceID = space.firstElementChild.id;

            const animation = this.slideToObject(`${resistanceID}`, 'resistance-worker-icon');
            await this.bgaPlayDojoAnimation(animation);
            dojo.destroy(`${resistanceID}`);
        },

        removePatrol: async function(patrolID) {
            const animation = this.slideToObject(`${patrolID}`, "player_boards");
            await this.bgaPlayDojoAnimation(animation);
            dojo.destroy(`${patrolID}`);
        },

        placeTokens: async function(tokens, animate = true) {
            const _tokens = Object.values(tokens);
            for (var i = 0; i < _tokens.length; i++) {
                let tokenID = _tokens[i].name.split('_').join('-');
                let tokenClass = tokenID.split('-').slice(0, -1).join('-');
                let targetID = `space-${_tokens[i].location.split('_')[0]}-token-space-${_tokens[i].location.split('_')[1]}`;

                dojo.place(`
                    <div id=${tokenID} class="token ${tokenClass}">
                        <div class="token-circle"></div>
                    </div>`, targetID);

                if (animate) {
                    this.placeOnObject(tokenID, `custom-player-board`);
                    const animation = this.slideToObject(tokenID, targetID);
                    await this.bgaPlayDojoAnimation(animation);
                }
            };
        },

        removeTokens: async function(tokenType, spaceID) {
            for (let i = 5; i > 0; i--) {
                let space = dojo.byId(`space-${spaceID}-token-space-${i}`);
                if (space.firstElementChild) {
                    let tokenID = space.firstElementChild.id;
                    const animation = this.slideToObject(`${tokenID}`, `${tokenType}-icon`);
                    await this.bgaPlayDojoAnimation(animation);
                    dojo.destroy(`${tokenID}`);
                }
            }
        },

        moveRoundMarker: async function(round) {
            const animation = this.slideToObject("marker-round", `round-number-space-${round}`);
            await this.bgaPlayDojoAnimation(animation);
        },

        moveMoraleMarker: async function(morale) {
            const animation = this.slideToObject("marker-morale", `morale-track-space-${morale}`);
            await this.bgaPlayDojoAnimation(animation);
        },

        moveSoldiersMarker: async function(soldiersNumber) {
            const animation = this.slideToObject("marker-soldiers", `soldiers-track-space-${soldiersNumber}`);
            await this.bgaPlayDojoAnimation(animation);
        },

        placeMissionMarker: async function(spaceID, marker_number, animate = true) {
            const markerIDs = dojo.query(".marker-mission").map(node => node.id);
            const markerID = markerIDs.length

            dojo.place(`<div id="mission-marker-${markerID}" class="marker marker-mission"></div>`, `space-${spaceID}-marker-space-${marker_number}`);
            if (animate) {
                this.placeOnObject(`mission-marker-${markerID}`, 'player_boards');
                const animation = this.slideToObject(`mission-marker-${markerID}`, `space-${spaceID}-marker-space-${marker_number}`);
                await this.bgaPlayDojoAnimation(animation);
            }
        },

        removeMarker: async function(spaceID, markerNumber) {
            let space = dojo.byId(`space-${spaceID}-marker-space-${markerNumber + 1}`);
            let markerID = space.firstElementChild.id;

            const animation = this.slideToObject(`${markerID}`, "player_boards");
            await this.bgaPlayDojoAnimation(animation);
            dojo.destroy(`${markerID}`);
        },

        flipMission: function(missionName) {
            dojo.toggleClass(dojo.byId(`${missionName}`), 'flipped');
        },

        placeRoomTile: async function(spaceID, roomID, animate = true) {
            dojo.destroy(`room-tile-${roomID}`);
            dojo.place(`
                <div id="room-tile-${roomID}" class="room-tile">
                    <div class="circle-shape"></div>
                    <div class="rectangle-shape"></div>
                </div>`, `space-${spaceID}-room-tile-space`);
            if (animate) {
                this.placeOnObject(`room-tile-${roomID}`, `${roomID}-tile-container`);
                const slideAnimation = this.slideToObjectPos(`room-tile-${roomID}`, `space-${spaceID}-room-tile-space`, 0, 0, 1000);
                await this.bgaPlayDojoAnimation(slideAnimation);
                const node = dojo.query(`#${roomID}-tile-container`)[0];
                this.smoothRemove(node);
            } else {
                dojo.destroy(`${roomID}-tile-container`);
            }
            
        },

        displayModalWithCard: function(cardId, title) {
            let dialog = new ebg.popindialog();
            dialog.create('cardDialog');
            dialog.setTitle(title);
            dialog.setContent(`
                <div id="patrol-${cardId}" class="card patrol-card">
                    <div class="card patrol-card-front" style="transform:none"></div>
                </div>`);
            // dialog.resize(200, 300);
            dialog.show();
        },

        placeDarkLadyLocationReminder: function(spaceID) {
            dojo.place(`
                <div id="dark-lady-location"></div>
            `, `space-${spaceID}`);

            this.addTooltipHtml(`space-${spaceID}`, `Dark Lady's Location`);
        },
    });
});
