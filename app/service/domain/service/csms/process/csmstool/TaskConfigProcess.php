<?php


namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;


use Imee\Service\Domain\Service\Csms\Traits\CsmsTrait;

class TaskConfigProcess
{

    use CsmsTrait;


    public function handle()
    {
        $res = [];

        $choices = $this->getAllChoice();
        if ($choices) {
            foreach ($choices as $choice) {
                $res['choice'][] = ['label' => $choice['choice_name'], 'value' => $choice['choice']];
            }
        }


        return $res;
    }

}