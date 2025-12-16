<?php


namespace Imee\Service\Domain\Service\Csms\Process\Callback;


use Imee\Models\Xs\XsScreenImage;

class XsScreenImageProcess
{


    public function handle($data)
    {
        $id = $data['pk_value'];

        $state = $data['status'];

        $resource = XsScreenImage::findFirst([
            'conditions' => "id = :id:",
            'bind' => [
                'id' => $id
            ]
        ]);
        if(!$resource){
            return [
                'state' => false,
                'message' => 'csms'.$data['choice'].'未找到记录:'.$id
            ];
        }

        // 更改状态
        $resource->status = $state;
        $resource->save();

        return [
            'state' => true,
            'message' => ''
        ];

    }

}