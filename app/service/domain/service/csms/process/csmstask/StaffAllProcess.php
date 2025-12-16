<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmstask;

use Imee\Comp\Operate\Auth\Service\StaffService;

class StaffAllProcess
{
    public function handle()
    {
        $service = new StaffService();
        $users = $service->getAllStaff();
        $data = [];
        foreach ($users as $user) {
            $data[] = ['label' => $user['user_name'], 'value' => $user['user_id']];
        }
        return $data;
    }
}