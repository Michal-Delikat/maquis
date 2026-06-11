<?php
namespace Bga\Games\Maquis;
trait BoardActionsTrait {
    protected function addSpaceAction(int|array $spaceID, string $actionName): void {
        $spaceIDs = is_array($spaceID) ? $spaceID : [$spaceID];
        $values = implode(', ', array_map(fn($id) => "($id, '$actionName')", $spaceIDs));
        
        self::DbQuery("
            INSERT INTO board_action (space_id, action_name)
            VALUES $values;
        ");
    }

    protected function removeSpaceAction(int $spaceID, string $actionName): void {
        self::DbQuery("
            DELETE FROM board_action 
            WHERE space_id = $spaceID AND action_name = '$actionName';
        ");
    }

    protected function addSpareRoomActions(int $spaceID, string $roomID): void {
        switch (str_replace("room_", "", $roomID)) {
            case ROOM_INFORMANT:
                $this->addSpaceAction($spaceID, ACTION_GET_INTEL);
                $this->removeSpaceAction(FIXER, ACTION_GET_INTEL);
                break;
            case ROOM_COUNTERFEITER:
                $this->addSpaceAction($spaceID, ACTION_GET_MONEY);
                $this->removeSpaceAction(FIXER, ACTION_GET_MONEY);
                break;
            case ROOM_SAFE_HOUSE:
                $this->updateFieldsSafety($spaceID, isSafe: true);
                break;
            case ROOM_CHEMISTS_LAB:
                $this->addSpaceAction($spaceID, ACTION_BUY_EXPLOSIVES);
                $this->removeSpaceAction(FIXER, ACTION_BUY_EXPLOSIVES);
                break;
            case ROOM_SMUGGLER:
                $this->addSpaceAction($spaceID, ACTION_GET_3_FOOD);
                $this->addSpaceAction($spaceID, ACTION_GET_3_MEDICINE);
                $this->removeSpaceAction(FIXER, ACTION_GET_3_FOOD);
                $this->removeSpaceAction(FIXER, ACTION_GET_3_MEDICINE);
                break;
            case ROOM_PROPAGANDIST:
                $this->addSpaceAction($spaceID, ACTION_INCREASE_MORALE);
                $this->removeSpaceAction(FIXER, ACTION_INCREASE_MORALE);
                break;
            case ROOM_PHARMACIST:
                $this->addSpaceAction($spaceID, ACTION_BUY_POISON);
                $this->removeSpaceAction(FIXER, ACTION_BUY_POISON);
                break;
            case ROOM_FORGER:
                $this->addSpaceAction($spaceID, ACTION_FORGE_FAKE_ID);
                $this->removeSpaceAction(FIXER, ACTION_FORGE_FAKE_ID);
                break;
            case ROOM_FIXER:
                $this->addSpaceAction($spaceID, ACTION_USE_FIXER);
                break;
        }
    } 
}