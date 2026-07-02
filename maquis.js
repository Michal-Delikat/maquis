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
 * Maquis user interface script
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

            let placedTokens = Object.values(gamedatas.placedTokens);
            let spacesWithMarkers = Object.values(gamedatas.spacesWithMarkers);
            
            let discardedPatrolCards = gamedatas.discardedPatrolCards;
            
            let resources = Object.values(gamedatas.resources);
            
            let selectedMissions = gamedatas.selectedMissions;
            let completedMissions = Object.values(gamedatas.completedMissions);
            
            let rooms = Object.values(gamedatas.rooms);
            let placedRooms = Object.values(gamedatas.placedRooms);

            let resistanceWorkers = Object.values(gamedatas.resistanceWorkers);
            let milice = Object.values(gamedatas.milice);
            let soldiers = Object.values(gamedatas.soldiers);

            let player_id = gamedatas.currentPlayerID;
            let playerScore = Object.values(gamedatas.players)[0].score;

            let darkLadyLocation = gamedatas.darkLadyLocation;

            let player_board_div = $('player_board_' + player_id);

            // PLAYER INFO
            dojo.place(`
                <div id="custom-player-board">
                    <div id="resources"></div>
                </div>
            `, player_board_div);
            
            let maxScoreValue = gamedatas.threeStarMissionSelected ? '3' : '2';  

            dojo.place(`<span id="player_score_max_${player_id}">/${maxScoreValue}</span>`, `player_score_${player_id}`, "after");

            // RESOURCES
            resources.forEach(({resource_type, amount}) => dojo.place(`
                <div class="resource-box">
                    <span id="${resource_type}-quantity" class="resource-amount">${amount}</span>
                    <div id="${resource_type}-icon" class="resource-icon">
                        <div class="resource-icon-circle"></div>
                    </div>
                <div>    
            `, 'resources'));

            dojo.place(`
                <div class="board-and-missions">
                    <div class="top-area">
                        <div class="arrest whiteblock">
                            <h3 class="whiteblock-label">${_('Arrested Workerks')}</h3>
                            <div class="arrest-spaces-wrapper">
                                <div id="arrest-space-1" class="arrest-space"></div>
                                <div id="arrest-space-2" class="arrest-space"></div>
                                <div id="arrest-space-3" class="arrest-space"></div>
                                <div id="arrest-space-4" class="arrest-space"></div>
                                <div id="arrest-space-5" class="arrest-space"></div>
                            </div>
                        </div>
                        <div id="mission-slot-1" class="mission-slot">
                            <div id="${selectedMissions.mission_card_a}" class="card mission-card">
                                <div class="mission-card-back mission-card-face"></div>
                                <div class="mission-card-front mission-card-face"></div>
                                <div id="space-18" class="space mission-space mission-space-1">
                                    <div id="space-18-worker-space" class="worker-space"></div>
                                    <div id="space-18-marker-space" class="marker-space">
                                        <div id="space-18-marker-space-1" class="marker-space"></div>
                                    </div>
                                    <div id="space-18-background-space" class="background-space"></div>
                                </div>
                                <div id="space-19" class="space mission-space mission-space-2">
                                    <div id="space-19-worker-space" class="worker-space"></div>
                                    <div id="space-19-marker-space" class="marker-space">
                                        <div id="space-19-marker-space-1" class="marker-space"></div>
                                    </div>
                                    <div id="space-19-background-space" class="background-space"></div>
                                </div>
                                <div id="space-20" class="space mission-space mission-space-3">
                                    <div id="space-20-worker-space" class="worker-space"></div>
                                    <div id="space-20-marker-space" class="marker-space">
                                        <div id="space-20-marker-space-1" class="marker-space"></div>    
                                    </div>
                                    <div id="space-20-background-space" class="background-space"></div>
                                </div>
                                <div id="mission-a-token-space" class="mission-token-space"></div>
                            </div>
                        </div>
                        <div id="mission-slot-2" class="mission-slot">
                            <div id="${selectedMissions.mission_card_b}" class="card mission-card">
                                <div class="mission-card-back mission-card-face"></div>
                                <div class="mission-card-front mission-card-face"></div>
                                <div id="space-21" class="space mission-space mission-space-1">
                                    <div id="space-21-worker-space" class="worker-space"></div>
                                    <div id="space-21-marker-space" class="marker-space">
                                        <div id="space-21-marker-space-1" class="marker-space"></div>
                                    </div>
                                    <div id="space-21-background-space" class="background-space"></div>
                                </div>
                                <div id="space-22" class="space mission-space mission-space-2">
                                    <div id="space-22-worker-space" class="worker-space"></div>
                                    <div id="space-22-marker-space" class="marker-space">
                                        <div id="space-22-marker-space-1" class="marker-space"></div>
                                    </div>
                                    <div id="space-22-background-space" class="background-space"></div>
                                </div>
                                <div id="space-23" class="space mission-space mission-space-3">
                                    <div id="space-23-worker-space" class="worker-space"></div>
                                    <div id="space-23-marker-space" class="marker-space">
                                        <div id="space-23-marker-space-1" class="marker-space"></div>
                                    </div>
                                    <div id="space-23-background-space" class="background-space"></div>
                                </div>
                                <div id="mission-b-token-space" class="mission-token-space"></div>
                            </div>
                        </div>
                        <div class="barracks-boxes">
                            <div class="milice-barracks barracks whiteblock">
                                <h3 class="whiteblock-label">${_('Milice')}</h3>
                                <div id="milice-row" class="patrol-units-row">
                                    <div id="barracks-milice-space-1" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-milice-space-2" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-milice-space-3" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-milice-space-4" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-milice-space-5" class="patrol-unit-barracks-space"></div>
                                </div>
                            </div>
                            <div class="soldiers-barracks barracks whiteblock">
                                <h3 class="whiteblock-label">${_('Soldiers')}</h3>
                                <div id="soldiers-row" class="patrol-units-row">
                                    <div id="barracks-soldier-space-1" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-soldier-space-2" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-soldier-space-3" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-soldier-space-4" class="patrol-unit-barracks-space"></div>
                                    <div id="barracks-soldier-space-5" class="patrol-unit-barracks-space"></div>
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
                    <div id="right-panel-top">
                        <div id="morale-and-soldiers-track" class="card">
                            <div id="morale-track"></div>
                            <div id="soldiers-track"></div>
                        </div>
                        <div id="patrol-deck" class="card"></div>
                        <div id="patrol-discard" class="card whiteblock"></div>
                    </div>
                    <div id="room-tiles-container-wrapper">
                        <div id="room-tiles"></div>
                    </div>
                </div>
            `, 'game_play_area');

            // FLIP MISSIONS 
            completedMissions.forEach(mission => this.flipMission(mission['name']));

            // ADD TOOLTIPS TO MISSIONS
            Object.values(selectedMissions).forEach(mission => {
                let description = null;
                switch(mission.split('_').slice(2).join("_")) {
                    case 'milice_parade_day':
                        description = `
                        <p class="mission-flavour-text">${_('The Milice are holding parades around town - a brave volunteer must show our defiance. They probably won\'t make it back...')}</p><br>
                        <p class="mission-description-text">${_('Deliver 1')} <span class="mission-text-item-name">${_('Weapon')}</span> ${_('to')} <span class="mission-text-location-name">Rue Baradat</span> ${_('on a Parade Day (Day 3, 6, 9, 12, and 14). The Worker is Arrested. Increase')} <span class="mission-text-morale">${_('Morale')}</span> ${_('by 1. Before this mission is completed, the road between')} <span class="mission-text-location-name">Rue Baradat</span> ${_('and')} <span class="mission-text-location-name">Fence</span> ${_('is blocked on Parade Days.')}</p>`;
                        break;
                    case 'officers_mansion':
                        description = `
                            <p class="mission-flavour-text">${_('The local commander has commandeered a fancy house north of town. We need to make sure he knows that he will never have us cowed.')}</p><br>
                            <p class="mission-description-text">${_('Place a Worker on')} <span class="mission-text-location-name">Rue Baradat</span>, <span class="mission-text-location-name">Pont Leveque</span> ${_('and')} <span class="mission-text-location-name">Pont du Nord</span> ${_('to write anti-fascist graffiti (place markers during Action Phase to track graffiti). Once all three locations are tagged, place a Worker here to complete.')}</p>`;
                        break;
                    case 'sabotage':
                        description = `
                            <p class="mission-flavour-text">${_('The occupation runs a munitions factory on the outskirts of town. Infiltrate and sabotage the operation by any means possible.')}</p><br>
                            <p class="mission-description-text">${_('A Worker must infiltrate the factory twice, then return a third day to deliver')} <span class="mission-text-item-name">${_('Explosives')}</span>.</p>`;
                        break;
                    case 'underground_newspaper':
                        description = `
                            <p class="mission-flavour-text">${_('Get the word out and counter the propaganda of the occupation.')}</p><br>
                            <p class="mission-description-text">${_('Deliver 2')} <span class="mission-text-item-name">${_('Intel')}</span> ${_('to this location on three separate days.')}</p>`;
                        break;
                    case 'infiltration':
                        description = `
                            <p class="mission-flavour-text">${_('The best place to collect reconnaissance is often from the inside. Insert a mole into the Milice.')}</p><br>
                            <p class="mission-description-text">${_('Deliver 2')} <span class="mission-text-item-name">${_('Intel')}</span> ${_('to this location. The Worker must remain here until another Worker delivers 1')} <span class="mission-text-item-name">${_('Weapon')}</span> ${_('and 1')} <span class="mission-text-item-name">${_('Explosive')}</span>. ${_('While the first Worker is here, you may look at the top card of the Patrol deck before the placement phase.')}</p>`;
                        break;
                    case 'german_shepards':
                        description = `
                            <p class="mission-flavour-text">${_('The occupiers have dogs to help with patrols. Use poison to eliminate them')}</p><br>
                            <p class="mission-description-text">${_('Deliver 1')} <span class="mission-text-item-name">${_('Medicine')}</span> ${_('and 1')} <span class="mission-text-item-name">${_('Food')}</span> ${_('to this location on three separate days. Before this mission is completed, Milice units may not be eliminated.')}</p>`;
                        break;
                    case 'double_agent':
                        description = `
                            <p class="mission-flavour-text">${_('We must enearth the double agent known only as the "Dark Lady"...')}</p><br>
                            <p class="mission-description-text">${_('Visit all locations on the west side of the river except the')} <span class="mission-text-location-name">Fence</span> ${_('and')} <span class="mission-text-location-name">Spare Room</span>. ${_('Once completed, turn over the top patrol card; Location #1 is the location of the "Dark Lady". Visit that location again to complete the mission. Remove one Worker permanently from the game.')}</p>`;
                        break;
                    case 'aid_the_spy':
                        description = `
                            <p class="mission-flavour-text">${_('A British spy parachuted in a few days ago and needs our help. Provide him with equipment and supplies to help him carry out his mission.')}</p><br>
                            <p class="mission-description-text">${_('Deliver 2')} <span class="mission-text-item-name">${_('Weapons')}</span> ${_('to the spy on one day, followed by 1')} <span class="mission-text-item-name">${_('Money')}</span> ${_('and 2')} <span class="mission-text-item-name">${_('Food')}</span> ${_('on a second day.')}</p>`;
                        break;
                    case 'assassination':
                        description = `
                            <p class="mission-flavour-text">${_('The Milice is a paramilitary force of local thugs colluding with the occupiers - we need to send a message to teach these collaborators a lesson!')}</p><br>
                            <p class="mission-description-text">${_('Eliminate all Milice Units. This mission MUST be completed last.')}</p>`;
                        break;
                    case 'destroy_the_train':
                        description = `
                            <p class="mission-flavour-text">${_('We\'ve recieved intelligence that the occupation are going to be transporting Panzers along the railway near your town. Plant bombs to destroy the train as it passes.')}</p><br>
                            <p class="mission-description-text">${_('Deliver 3')} <span class="mission-text-item-name">${_('Explosives')}</span> ${_('to this location. This mission can only be completed on Days 6, 7, 8 or 9.')}</p>`;
                        break;
                    case 'liberate_the_town':
                        description = `
                            <p class="mission-flavour-text">${_('The Allies are pushing forward. If we rise up the right time, our town could emerge unscathed. For that, we will need weapons and courage!')}</p><br>
                            <p class="mission-description-text">${_('Posses at least 3')} <span class="mission-text-item-name">${_('Weapons')}</span> ${_('and 4')} <span class="mission-text-morale">${_('Morale')}</span> ${_('at the point the Day Track marker moves to \'END\' space.')}</p>`;
                        break;
                    case 'coded_messages':
                        description = `
                            <p class="mission-flavour-text">${_('Knowledge is power. Work with other Resistance Fighters from other cities to monitor the Occupation. Train a cryptographer, then have them communicate with other Resistance operatives.')}</p><br>
                            <p class="mission-description-text">${_('A Worker must be placed here by end of Day 6 and must remain until the end of Day 10.')}</p>`;
                        break;
                    case 'take_out_the_bridges':
                        description = `
                            <p class="mission-flavour-text">${_('The Occupation has enjoyed unfettered access to the city for too long. Slow them down!')}</p><br>
                            <p class="mission-description-text">${_('To destroy a bridge, deliver 2')} <span class="mission-text-item-name">${_('Explosives')}</span> ${_('to the')} <span class="mission-text-location-name">Black Market</span>. ${_('At the end of the day, place a marker on a bridge of you choice connected to The')} <span class="mission-text-location-name">Black Market</span>. ${_('Workers may not pass destroyed bridges for the rest of the game.')}</p>`;
                        break;
                    case 'bomb_for_the_officer':
                        description = `
                            <p class="mission-flavour-text">${_('A German plane landed in the field southeast of town. The pilot is in a hotel nearby. A perfect time to strike!')}</p><br>
                            <p class="mission-description-text">${_('You must have at least 5')} <span class="mission-text-morale">${_('Morale')}</span> ${_('to carry out this mission. Deliver 1')} <span class="mission-text-item-name">${_('Weapon')}</span> ${_('and 1')} <span class="mission-text-item-name">${_('Explosive')}</span> ${_('to this location. Before this mission is completed, the East')} <span class="mission-text-location-name">Field</span> ${_('and the Southeast')} <span class="mission-text-location-name">Spare Room</span> ${_('are unusable.')}</p>`;
                        break;
                    case 'milice_hq':
                        description = `
                            <p class="mission-flavour-text">${_('The Milice have established their regional HQ on the edge of town, and nobody knows who to trust. We need to clear out the rats to let the town feel safe again.')}<p><br>
                            <p class="mission-description-text"><span class="mission-text-morale">${_('Morale')}</span> ${_('starts on 4')}<br><br>
                            ${_('Objective 1:')}<br>
                            ${_('Discover the plans of the building at the public records office on')} <span class="mission-text-location-name">Rue Baradat</span>.<br><br>
                            ${_('Objective 2:')}<br>
                            ${_('[SAFE] Spend 2')} <span class="mission-text-item-name">${_('Poison')}</span> ${_('on this location to spike the Milice\'s water supply, gain 2 on the')} <span class="mission-text-morale">Morale track</span>  ${_('and 3 on the')} <span class="mission-text-soldier">Soldier track</span><br><br>
                            ${_('If the')} <span class="mission-text-soldier">soldier track</span> ${_('was on 3+ already, success; otherwise, major success.')}</p>`;
                        break;
                    case 'bomb_the_barracks':
                        description = `
                            <p class="mission-flavour-text">${_('German soldiers are stationed right outside town. Draw them out with distraction and bomb the barracks to slow the Nazi war machine.')}</p><br>
                            <p class="mission-description-text"><span class="mission-text-soldier">Soldier track</span> ${_('starts on 3')}<br><br>
                            ${_('Objective 1:')}<br>
                            ${_('Visit this location to recon the barracks')}<br><br>
                            ${_('Objective 2:')}<br>
                            ${_('Visit this location to recon the barracks')}<br><br>
                            ${_('Objective 3:')}<br>
                            ${_('[SAFE] Spend a ')}<span class="mission-text-item-name">${_('Fake ID')}</span> and Two <span class="mission-text-item-name">${_('Explosives')}</span> ${_('on this location AND, on the same day, send a second worker to an empty')} <span class="mission-text-location-name">field</span> ${_('and spend a')} <span class="mission-text-item-name">${_('Weapon')}</span> ${_('to distract the soldiers to achieve a major success.')}</p>`;
                        break;
                    case 'free_the_resistance_leader':
                        description = `
                            <p class="mission-flavour-text">${_('A resistance leader has been captured and will be transported away from town soon. Free him from the occupiers... or make sure he at least can\'t tell them his secrets.')}</p><br>
                            <p class="mission-description-text">${_('Objective 1:')}<br>
                            ${_('Spend')} <span class="mission-text-item-name">${_('Info')}</span> ${_('and')} <span class="mission-text-item-name">${_('Money')}</span> ${_('on this location before end of day 5 to bribe a clerk to discover the location of the prisoner.')}<br><br>
                            ${_('Objective 2:')}<br>
                            ${_('[SAFE] Spend a')} <span class="mission-text-item-name">${_('Poison')}</span> ${_('on this location before end of day 9 to succeed the mission OR spend a')} <span class="mission-text-item-name">${_('Fake ID')}</span>, ${_('two')} <span class="mission-text-item-name">${_('Weapons')}</span> ${_('and a')} <span class="mission-text-item-name">${_('Medicine')}</span> ${_('to increase the')} <span class="mission-text-soldier">soldier track</span> ${_('by 2 on day 10 to achieve a major success.')}</p>`;
                        break;
                    case 'destroy_aa_guns':
                        description = `
                            <p class="mission-flavour-text">${_('The Liberation of France approaches adn the Allies must surely be near. Take out the AA guns that the occupiers have positioned in and around town to ensure air support.')}</p><br>
                            <p class="mission-description-text">${_('Place AA gun tokens on both')} <span class="mission-text-location-name">fields</span>, <span class="mission-text-location-name">Rue Baradat</span>, ${_('and the')} <span class="mission-text-location-name">Black Market</span>. ${('A fifth gun emplacement is on this location.')}<br><br>
                            ${_('Objective:')}<br>
                            ${_('[SAFE] Spend an')} <span class="mission-text-item-name">${_('explosive')}</span> ${_('or a')} <span class="mission-text-item-name">${_('weapon')}</span> ${_('in a AA gun emplacement\'s position to destroy it.')}<br><br>
                            ${_('No airdrops can be made at a')} <span class="mission-text-location-name">field</span> ${('with a AA gun in place.')}<br><br>
                            ${_('If 3 AA guns are disabled the mission is a success; if all 5 are disabled the mission is a major success.')}</p>`;
                        break;
                }
                    
                this.addTooltipHtml(mission, 
                    `<div class="mission-tooltip-wrapper">
                        <div class="mission-description">
                            ${description}
                        </div>
                    </div>`
                );
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
                        <div id="space-${i + 1}-token-spaces" class="token-spaces">
                            <div 
                                id="space-${i + 1}-token-space-1" 
                                class="token-space"
                            ></div>
                        </div>
                        <div id="space-${i + 1}-marker-spaces"></div>
                        <div id="space-${i + 1}-worker-space" class="worker-space"></div>
                        <div id="space-${i + 1}-background-space" class="background-space"></div>
                    </div>
                `, 'spaces');

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

            [1, 7].forEach((id) => dojo.place(`<div id="space-${id}-token-space-2" class="token-space"></div>`, `space-${id}-token-spaces`));

            [14, 17].forEach((id) => {
                for (let i = 2; i < 5; i++) {
                    dojo.place(`
                        <div 
                            id="space-${id}-token-space-${i}" 
                            class="token-space"
                        ></div>
                    `, `space-${id}-token-spaces`)
                }
            });

            // ADDITIONAL SPACES FOR RESISTANCE
            // SAFE HOUSE
            dojo.place(`
                <div id="safe-house-space-1" class="safe-house-space"></div>
                <div id="safe-house-space-2" class="safe-house-space"></div>
                <div id="safe-house-space-3" class="safe-house-space"></div>
                <div id="safe-house-space-4" class="safe-house-space"></div>
                <div id="safe-house-space-5" class="safe-house-space"></div>
                `, 'space-16-worker-space');
            // CAFE
            dojo.place(`
                <div class="cafe-spaces">
                    <div id="cafe-space-1" class="cafe-space"></div>
                    <div id="cafe-space-2" class="cafe-space"></div>
                </div>
                `, 'spaces');

            // ADDITIONAL SPACES AT BRIDGES
            dojo.place(`
                <div id="space-24" class="space board-space bridge-space">
                    <div id="space-24-marker-space">
                        <div id="space-24-marker-space-1" class="marker-space"></div>
                    </div>
                    <div id="space-24-background-space" class="background-space"></div>
                </div>
                <div id="space-25" class="space board-space bridge-space">
                    <div id="space-25-marker-space">
                        <div id="space-25-marker-space-1" class="marker-space"></div>
                    </div>
                    <div id="space-25-background-space" class="background-space"></div>
                </div>
                `, 'spaces');
                
            // PAWNS
            resistanceWorkers.forEach(worker => { 
                this.placeWorker(worker.name, worker.location);
            });
            [...milice].reverse().forEach(milice => {
                if (milice.location !== 'off_board') this.placeMilice(milice.name, milice.location);
            });
            [...soldiers].reverse().forEach(soldier => {
                this.placeSoldier(soldier.name, soldier.location);
            });

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
            Object.values(discardedPatrolCards).forEach((card) => this.discardPatrolCard(card.type_arg, false));

            // DARK LADY LOCATION REMINDER
            if (darkLadyLocation !== 'off_board') {
                this.placeDarkLadyLocationReminder(darkLadyLocation);
            }

            // Event Listeners
            dojo.query('.background-space').connect('click', this, "onSpaceClicked");

            dojo.query('#patrol-discard').connect('click', this, "onPatrolDiscardClicked");
 
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
                case 'shootMilice':
                    const spacesWithMilice = Object.values(args.args.spacesWithMilice);

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
                case 'removeBridge':
                    const bridgesWithMarkers = Object.values(args.args);

                    ['24', '25'].filter(x => !bridgesWithMarkers.includes(x)).forEach(spaceID => {
                        let space = dojo.byId(`space-${spaceID}-background-space`);
                        if (space) dojo.addClass(space, 'space-with-bridge-to-remove');
                    })
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

                case 'removeBridge':
                    dojo.query('.space-with-bridge-to-remove').removeClass('space-with-bridge-to-remove');
                    break;
            }          
        }, 
        
        onUpdateActionButtons: function(stateName, args) {
            // console.log('onUpdateActionButtons: ' + stateName, args);
                      
            if (this.isCurrentPlayerActive())
            {            
                switch(stateName) {
                    case 'activateWorker':
                        if (args.canShoot) {
                            this.addActionButton('actDeclareShootingMilice-btn', _('Shoot milice'), () => this.bgaPerformAction("actDeclareShootingMilice"), null, null, 'gray');
                        }
                        break;

                    case 'takeAction':
                        Object.values(args.actions).forEach(action => this.addActionButton(`actTakeAction_${action.action_name}`, action.action_description, () => this.bgaPerformAction("actTakeAction", { actionName: action.action_name }), null, null, 'blue'));
                        this.addActionButton(`actReturn`, _('Return to Safe House'), () => this.bgaPerformAction("actTakeAction", { actionName: 'return'}), null, null, 'gray');
                        this.addActionButton('actBack', _('Back'), () => this.bgaPerformAction("actBack"), null, null, 'red');
                        break;

                    case 'airdropSelectSupplies':
                        Object.values(args.options).forEach(option => this.addActionButton(`actAirdropSelectSupplies_${option.resourceName}`, option.airdropOptionDescription, () => this.bgaPerformAction("actSelectSupplies", { supplyType: option.resourceName}), null, null, 'blue'));
                        break;

                    case 'selectSpareRoom':
                        Object.values(args.availableRooms).forEach(room => this.addActionButton(`actSelectRoom_${room.name}`, args.roomsDescriptions[room.name.replace("room_", "")], () => this.bgaPerformAction("actSelectRoom", { roomID: room.name}), null, null, 'blue'));
                        break;

                    case 'shootMilice':
                        this.addActionButton('actReturn', _('Back'), () => this.bgaPerformAction("actBack"), null, null, 'red');
                        break;

                    case 'useFixer':
                        Object.values(args.actions).forEach(action => this.addActionButton(`actUseFixer_${action.action_name}`, action.action_description, () => this.bgaPerformAction("actUseFixer", { actionName: action.action_name }), null, null, 'blue'));
                        this.addActionButton('actBack', _('Back'), () => this.bgaPerformAction("actBack"), null, null, 'red');
                        break;

                    case 'placeFakeId':
                        this.addActionButton('actPlace', _('Place'), () => this.bgaPerformAction("actPlaceFakeId"), null, null, "blue");
                        this.addActionButton('actDontPlace', _('Don\'t place'), () => this.bgaPerformAction("actDontPlaceFakeId"), null, null, "blue");
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
                });
            }
            else if (evt.currentTarget.classList.contains('space-with-bridge-to-remove')) {
                this.bgaPerformAction("actRemoveBridge", {
                    spaceID: spaceID
                });
            }
        },

        onPatrolDiscardClicked: function(evt) {
            evt.preventDefault();
            evt.stopPropagation();

            const patrolDiscard = document.getElementById('patrol-discard');
            const htmlContent = patrolDiscard.innerHTML;

            console.log(htmlContent);

            let dialog = new ebg.popindialog();
            dialog.create('discarded-patrol-cards-dialog');
            dialog.setTitle(`${'Discarded patrol cards'}`);
            dialog.setContent(`<div id="discarded-patrol-cards-wrapper">${htmlContent}</div>`);
            dialog.show();
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
        
        notif_workerMoved: function({workerID, spaceID}) {
            this.moveWorker(workerID, spaceID);
        },

        notif_workerRecruited: function({workerID}) {
            this.recruitWorker(workerID);
        },

        notif_workerReturned: function({activeSpace, workerID}) {
            this.returnWorker(activeSpace, workerID);
        },

        notif_workerArrested: function({workerID}) {
            this.arrestWorker(workerID);
        },

        notif_workerRemoved: function({workerID}) {
            this.removeWorker(workerID);
        },

        notif_patrolPlaced: function({placeSoldier, patrolID, spaceID}) {
            if (placeSoldier) {
                this.moveSoldier(patrolID, spaceID);
            } else {
                this.moveMilice(patrolID, spaceID);
            }
        },

        notif_patrolReturned: function({patrolID}) {
            if (patrolID.split("_")[0] === 'milice') {
                this.returnMilice(patrolID);
            } else {
                this.returnSoldier(patrolID);
            }
        },
        
        notif_patrolCardDiscarded: function({patrolCardID}) {
            this.discardPatrolCard(patrolCardID);
        },

        notif_patrolRemoved: function({patrolID}) {
            this.removePatrol(patrolID);
        },

        notif_roundNumberSet: function({round}) {
            this.moveRoundMarker(round);
        },

        notif_moraleSet: function({morale}) {
            this.moveMoraleMarker(morale);
        },

        notif_resourcesChanged: function({resource_name, quantity}) {
            dojo.byId(`${resource_name}-quantity`).innerHTML = quantity;
        },

        notif_tokensPlaced: function({tokens}) {
            this.placeTokens(Object.values(tokens));
        },

        notif_fakeIdRemoved: function({location}) {
            this.removeFakeId(location);
        },

        notif_aaGunRemoved: function({location}) {
            this.removeAAGun(location);
        },

        notif_tokensCollected: function({tokenType, location}) {
            this.removeTokens(tokenType, location);
        },

        notif_activeSoldiersSet: function({soldierNumber}) {
            this.moveSoldiersMarker(soldierNumber);
        },

        notif_markerPlaced: function({spaceID, markerNumber}) {
            this.placeMissionMarker(spaceID, markerNumber);
        },

        notif_markerRemoved: function({spaceID, markerNumber}) {
            this.removeMissionMarker(spaceID, markerNumber);
        },

        notif_missionCompleted: function({missionName, playerScore, playerId}) {
            dojo.byId("player_score_" + playerId).innerHTML = `${playerScore}`;

            this.flipMission(`mission_card_${missionName}`);
        },

        notif_roomPlaced: function({roomID, spaceID}) {
            this.placeRoomTile(spaceID, roomID);
        },

        notif_cardPeeked: function({cardId}) {
            this.displayModalWithCard(cardId, _("Next patrol card"));
        },
        
        notif_patrolCardsShuffled: function() {
            dojo.query('.patrol-card').forEach(node => {
                dojo.destroy(node.id);
            });
        },

        notif_darkLadyFound: function({cardId, location}) {
            this.displayModalWithCard(cardId, _("Dark Lady found at place #1"));
            this.placeDarkLadyLocationReminder(location);
        },

        notif_darkLadyLocationReminderRemoved: function() {
            this.removeDarkLadyLocationReminder();
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
        
        placeWorker: async function(resistanceID, spaceID) {
            if (spaceID === 'safe_house') {
                for (let i = 1; i <= 5; i++) {
                    const safeHouseSpace = dojo.byId(`safe-house-space-${i}`);
                    if (!safeHouseSpace.firstElementChild) {
                        dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `safe-house-space-${i}`); 
                        break; 
                    }
                }
            } else if (spaceID === 'cafe') {
                for (let i = 1; i <= 2; i++) {
                    const cafeSpace = dojo.byId(`cafe-space-${i}`);
                    if (!cafeSpace.firstElementChild) {
                        dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `cafe-space-${i}`);
                        break; 
                    }
                }
            } else if (spaceID === 'arrest') {
                for (let i = 1; i <= 5; i++) {
                    const arrestSpace = dojo.byId(`arrest-space-${i}`);
                    if (!arrestSpace.firstElementChild) {
                        dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `arrest-space-${i}`);
                        break; 
                    }
                }
            } else {
                dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `space-${spaceID}-worker-space`);            
            }
        },

        moveWorker: async function(resistanceID, spaceID) {
            const resistanceNode = dojo.byId(resistanceID);
            const parentNode = resistanceNode.parentNode;

            dojo.destroy(resistanceID);
            dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `space-${spaceID}-worker-space`);
            this.placeOnObject(resistanceID, parentNode.id);

            const animation = this.slideToObject(resistanceID, `space-${spaceID}-worker-space`);
            await this.bgaPlayDojoAnimation(animation);
        },

        recruitWorker: async function(resistanceID) {
            const resistanceNode = dojo.byId(resistanceID);
            const parentNode = resistanceNode.parentNode;

            for (let i = 1; i <= 5; i++) {
                const safeHouseSpace = dojo.byId(`safe-house-space-${i}`);
                if (!safeHouseSpace.firstElementChild) {
                    dojo.destroy(resistanceID);
                    dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `safe-house-space-${i}`);
                    this.placeOnObject(resistanceID, parentNode.id);

                    const animation = this.slideToObject(resistanceID, `safe-house-space-${i}`);
                    await this.bgaPlayDojoAnimation(animation);
                    break; 
                }
            }
        },

        returnWorker: async function(spaceID, resistanceID) {
            dojo.destroy(resistanceID);
            for (let i = 1; i <= 5; i++) {
                const safeHouseSpace = dojo.byId(`safe-house-space-${i}`);
                if (!safeHouseSpace.firstElementChild) {
                    dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `safe-house-space-${i}`); 
                    this.placeOnObject(resistanceID, `space-${spaceID}-worker-space`);
                    const animation = this.slideToObject(resistanceID, `safe-house-space-${i}`);
                    await this.bgaPlayDojoAnimation(animation);
                    break; 
                }
            }
        },

        arrestWorker: async function(resistanceID) {
            const resistanceNode = dojo.byId(resistanceID);
            const parentNode = resistanceNode.parentNode;

            for (let i = 1; i <= 5; i++) {
                const arrestSpace = dojo.byId(`arrest-space-${i}`);
                if (!arrestSpace.firstElementChild) {
                    dojo.destroy(resistanceID);
                    dojo.place(`<div id="${resistanceID}" class="worker resistance"></div>`, `arrest-space-${i}`);
                    this.placeOnObject(resistanceID,  parentNode.id);
                    const animation = this.slideToObject(resistanceID, `arrest-space-${i}`);
                    await this.bgaPlayDojoAnimation(animation);
                    break; 
                }
            }
        },

        removeWorker: async function(resistanceID) {
            const animation = this.slideToObject(`${resistanceID}`, 'custom-player-board');
            await this.bgaPlayDojoAnimation(animation);
            dojo.destroy(`${resistanceID}`);
        },
        
        placeMilice: async function(miliceID, spaceID) {
            if (spaceID === 'barracks') {
                for (let i = 5; i >= 1; i--) {
                    const barracksSpace = dojo.byId(`barracks-milice-space-${i}`);
                    if (!barracksSpace.firstElementChild) {
                        dojo.place(`<div id="${miliceID}" class="worker milice"></div>`, `barracks-milice-space-${i}`); 
                        break; 
                    }
                }
            } else {
                dojo.place(`<div id="${miliceID}" class="worker milice"></div>`, `space-${spaceID}-worker-space`);
            }
        },

        placeSoldier: async function(soldierID, spaceID) {
            if (spaceID === 'barracks') {
                for (let i = 5; i >= 1; i--) {
                    const barracksSpace = dojo.byId(`barracks-soldier-space-${i}`);
                    if (!barracksSpace.firstElementChild) {
                        dojo.place(`<div id="${soldierID}" class="worker soldier"></div>`, `barracks-soldier-space-${i}`); 
                        break; 
                    }
                }
            } else {
                dojo.place(`<div id="${soldierID}" class="worker soldier"></div>`, `space-${spaceID}-worker-space`);
            }
        },

        moveMilice: async function(miliceID, spaceID) {
            const miliceNode = dojo.byId(miliceID);
            const parentNode = miliceNode.parentNode;

            dojo.destroy(miliceID);
            dojo.place(`<div id="${miliceID}" class="worker milice"></div>`, `space-${spaceID}-worker-space`);
            this.placeOnObject(miliceID, parentNode.id);

            const animation = this.slideToObject(miliceID, `space-${spaceID}-worker-space`);
            await this.bgaPlayDojoAnimation(animation);
        },

        moveSoldier: async function(soldierID, spaceID) {
            const soldierNode = dojo.byId(soldierID);
            const parentNode = soldierNode.parentNode;

            dojo.destroy(soldierID);
            dojo.place(`<div id="${soldierID}" class="worker soldier"></div>`, `space-${spaceID}-worker-space`);
            this.placeOnObject(soldierID, parentNode.id);

            const animation = this.slideToObject(soldierID, `space-${spaceID}-worker-space`);
            await this.bgaPlayDojoAnimation(animation);
        },

        returnMilice: async function(miliceID) {
            const miliceNode = dojo.byId(miliceID);
            const parentNode = miliceNode.parentNode;

            for (let i = 5; i >= 1; i--) {
                const barracksSpace = dojo.byId(`barracks-milice-space-${i}`);
                if (!barracksSpace.firstElementChild) {
                    dojo.destroy(miliceID);
                    dojo.place(`<div id="${miliceID}" class="worker milice"></div>`, `barracks-milice-space-${i}`); 
                    this.placeOnObject(miliceID, parentNode.id);
                    const animation = this.slideToObject(miliceID, `barracks-milice-space-${i}`);
                    await this.bgaPlayDojoAnimation(animation);
                    break; 
                }
            }
        },

        returnSoldier: async function(soldierID) {
            const soldierNode = dojo.byId(soldierID);
            const parentNode = soldierNode.parentNode;

            for (let i = 5; i >= 1; i--) {
                const barracksSpace = dojo.byId(`barracks-soldier-space-${i}`);
                if (!barracksSpace.firstElementChild) {
                    dojo.destroy(soldierID);
                    dojo.place(`<div id="${soldierID}" class="worker soldier"></div>`, `barracks-soldier-space-${i}`);
                    this.placeOnObject(soldierID, parentNode.id);
                    const animation = this.slideToObject(soldierID, `barracks-soldier-space-${i}`);
                    await this.bgaPlayDojoAnimation(animation);
                    break; 
                }
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
            } else {
                dojo.toggleClass(dojo.byId(`patrol-${patrolCardID}`), 'flipped');
            }
        },

        removePatrol: async function(patrolID) {
            const animation = this.slideToObject(`${patrolID}`, "player_boards");
            await this.bgaPlayDojoAnimation(animation);
            dojo.destroy(`${patrolID}`);
        },

        placeTokens: async function(tokens, animate = true) {
            for (const token of tokens) {
                let tokenID = token.name.split('_').join('-');
                let tokenClass = tokenID.split('-').slice(0, -1).join('-');
                let targetID = '';
                if (['18', '19', '20'].includes(token.location)) {
                    targetID = 'mission-a-token-space';
                } else if (['21', '22', '23'].includes(token.location)) {
                    targetID = 'mission-b-token-space';
                } else {
                    for (let i = 1; i < 5; i++) {
                        targetID = `space-${token.location}-token-space-${i}`;
                        if (!dojo.byId(targetID).firstElementChild) {
                            break;
                        }
                    }
                }

                dojo.place(`
                    <div id=${tokenID} class="token ${tokenClass}">
                        <div class="token-circle"></div>
                    </div>`, 
                targetID);

                if (animate) {
                    this.placeOnObject(tokenID, `custom-player-board`);
                    const animation = this.slideToObject(tokenID, targetID);
                    await this.bgaPlayDojoAnimation(animation);
                }
            };
        },

        removeTokens: async function(tokenType, spaceID) {
            for (let i = 4; i > 0; i--) {
                let space = dojo.byId(`space-${spaceID}-token-space-${i}`);
                if (space?.firstElementChild) {
                    let tokenID = space.firstElementChild.id;
                    let tokenTypeHTML = tokenID.split('-').slice(0, -2).join('_');
                    if (tokenTypeHTML === tokenType) {
                        const animation = this.slideToObject(`${tokenID}`, `${tokenType}-icon`);
                        await this.bgaPlayDojoAnimation(animation);
                        dojo.destroy(`${tokenID}`);
                    }
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

        placeMissionMarker: async function(spaceID, markerNumber, animate = true) {
            const markerIDs = dojo.query(".marker-mission").map(node => node.id);
            const markerID = markerIDs.length

            dojo.place(`<div id="mission-marker-${markerID}" class="marker marker-mission"></div>`, `space-${spaceID}-marker-space-${markerNumber}`);
            if (animate) {
                this.placeOnObject(`mission-marker-${markerID}`, 'player_boards');
                const animation = this.slideToObject(`mission-marker-${markerID}`, `space-${spaceID}-marker-space-${markerNumber}`);
                await this.bgaPlayDojoAnimation(animation);
            }
        },

        removeMissionMarker: async function(spaceID, markerNumber) {
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
            dialog.create('one-card-dialog');
            dialog.setTitle(title);
            dialog.setContent(`
                <div class="patrol-card-dialog-wrapper">
                    <div id="patrol-${cardId}" class="card patrol-card">
                        <div class="card patrol-card-front" style="transform:none"></div>
                    </div>
                </div>`);
            dialog.show();
        },

        placeDarkLadyLocationReminder: function(spaceID) {
            dojo.place(`
                <div id="dark-lady-location"></div>
            `, `space-${spaceID}`);

            this.addTooltipHtml(`space-${spaceID}`, `Dark Lady's Location`);
        },

        removeDarkLadyLocationReminder: function() {
            dojo.destroy("dark-lady-location");
        },

        removeAAGun: async function(location) {
            console.log("hello");
            let space = dojo.byId(`space-${location}-token-space-1`);
            if (space.firstElementChild) {
                let tokenID = space.firstElementChild.id;
                const animation = this.slideToObject(`${tokenID}`, "player_boards");
                await this.bgaPlayDojoAnimation(animation);
                dojo.destroy(`${tokenID}`);
            }            
        },

        removeFakeId: async function(location) {
            space = undefined;
            if (['18', '19', '20'].includes(String(location))) {
                space = dojo.byId(`mission-a-token-space`);
            } else if (['21', '22', '23'].includes(String(location))) {
                space = dojo.byId(`mission-b-token-space`);
            } else {
                space = dojo.byId(`space-${location}-token-space-2`);
                if (!space || !space.firstElementChild) {
                    space = dojo.byId(`space-${location}-token-space-1`);
                }
            }
            if (space.firstElementChild) {
                let tokenID = space.firstElementChild.id;
                const animation = this.slideToObject(`${tokenID}`, "fake_id-icon");
                await this.bgaPlayDojoAnimation(animation);
                dojo.destroy(`${tokenID}`);
            }            
        }
    });
});
