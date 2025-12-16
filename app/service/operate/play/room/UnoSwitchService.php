<?php

namespace Imee\Service\Operate\Play\Room;

use Imee\Models\Xs\XsBigarea;

class UnoSwitchService extends SwitchBaseService
{
    public function __construct()
    {
        $this->type = XsBigarea::LAYA_UNO_ROOM_SWITCH;
        $this->guid = 'roomunoregionswitch';
        $this->field = ['laya_uno_room_switch', 'laya_uno_room_switch_diamond'];
    }
}