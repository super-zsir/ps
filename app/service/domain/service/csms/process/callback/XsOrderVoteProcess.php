<?php


namespace Imee\Service\Domain\Service\Csms\Process\Callback;


use Imee\Models\Xs\XsOrderVote;

class XsOrderVoteProcess
{


    public function handle($data)
    {

        $id = $data['pk_value'];
        $state = $data['status'];

        $res = XsOrderVote::findFirst([
            'conditions' => 'id == :id:',
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$res){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'未找到:'.$id
            ];
        }

        $res->state = $state;
        $res->admin = $data['admin'];
        $res->update_time = time();

        $res->save();

        return [
            'state' => true,
            'message' => ''
        ];
    }


}