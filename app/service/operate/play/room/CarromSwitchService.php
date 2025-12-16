<?php

namespace Imee\Service\Operate\Play\Room;

use Imee\Models\Xs\XsBigarea;

class CarromSwitchService extends SwitchBaseService
{
    public function __construct()
    {
        $this->type = XsBigarea::LAYA_CARROM_ROOM_SWITCH;
        $this->guid = 'roomcarromregionswitch';
        $this->field = ['laya_carrom_room_switch', 'laya_carrom_room_switch_diamond'];
    }
}