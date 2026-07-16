<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * Maquis implementation : © Michał Delikat michal.delikat0@gmail.com
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

namespace Bga\Games\Maquis;

trait PatrolCards {
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

        $this->notify->all("patrolCardDiscarded", '', array(
            "patrolCardID" => $cardID
        ));

        return $cardID;
    }
}