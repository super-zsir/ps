<?php

namespace Imee\Service\Domain\Service\Csms\Test;

use Imee\Comp\Common\Rpc\BaseRpcx;

class TestRpcService extends BaseRpcx
{
    /**
     * 视频直播审核回调
     */
    public function livevideo($params = [])
    {
        $this->gateway = 'http://172.16.1.64:9981/rpc';

        $data = [
            'choice' => 'live_video_screen',
            'uid' => '2432343243',
            'pk_value' => '275843543',
        ];

        try{
            $res = $this->request('Room.Info.Pt', 'AdminScreenshotAudit', $data);
            if($res){
                if(isset($res['state']) && is_bool($res['state']) && $res['state']){
                    return $res;
                }else{
                    return [
                        'state' => false,
                        'message' => $res['message'] ?? 'rpc审核回调接口错误',
                        'result' => $res
                    ];
                }
            }else{
                return [
                    'state' => false,
                    'message' => 'rpc接审核回调接口未返回任何结果'
                ];
            }
        }catch (\Exception $e){
            return [
                'state' => false,
                'message' => 'rpc异常错误：'.$e->getMessage()
            ];
        }
    }

}