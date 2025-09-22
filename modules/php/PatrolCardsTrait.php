<?php

namespace Bga\Games\Maquis;

trait PatrolCardsTrait {
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
}