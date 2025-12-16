<?php


namespace Imee\Service\Domain\Service\Csms;



use Imee\Models\Redis\CsmsRedis;
use Imee\Models\Rpc\PsRpc;
use Imee\Service\Domain\Service\Csms\Traits\CsmswarningTrait;
use Imee\Service\Helper;
use Imee\Service\Rpc\PtAdminService;

class CsmsPushService
{

    use CsmswarningTrait;


    // 长度
    public $pushLength = (ENV == 'dev') ? 2 : 50;

    // 间隔时间
    public $pushTimer = 5;

    // 脚本开始时间
    public $start;


    public function push($params = [])
    {
        $this->start = time();
        $i = 0;
        while ($i < 10) {
            if (time() - $this->start > 50) {
                break;
            }
            $i++;
            $type = $params['type'] ?? 'csmsPush';
            $this->{$type}();
            sleep($this->pushTimer);
        }
    }


    /**
     * @param string $start
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function csmsPush()
    {
        // 每5秒 把当前队列里面的 都推送调
        $length = CsmsRedis::getLength(CsmsRedis::CSMS_PUSH);

        Helper::console(CsmsRedis::CSMS_PUSH . ' 队列长度:' . $length);

        if (!$length) {
            return false;
        }

        $count = ceil($length / $this->pushLength);

        for ($i = 1; $i <= $count; $i++) {
            $list = CsmsRedis::pushRange(CsmsRedis::CSMS_PUSH, $this->pushLength);
            if ($list) {
                list($ok, $result) = (new PtAdminService())->push([
                    'list' => $list
                ]);

                if(ENV == 'dev'){
                    echo CsmsRedis::CSMS_PUSH . "推送结果" . PHP_EOL;
                    var_dump($ok);
                    print_r($result, true);
                    echo PHP_EOL;
                }

                if ($ok) {
                    continue;
                } else {
                    // 重新往队列丢数据
                    CsmsRedis::csmsLPush(CsmsRedis::CSMS_PUSH, $list);
                    $this->pushError($list, $result);
                }
            }
        }
    }


    /**
     * csms push error
     * @param $data
     * @param $result
     */
    public function pushError($data, $result)
    {
        $content = <<<STR
【csms push error】
> DATA: {data}
> RESULT: {result}
> DATE: {date}
STR;
        $wechatMsg = str_replace(
            ['{data}', '{result}', '{date}'],
            [json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), date('Y-m-d H:i:s')],
            $content
        );
        $this->sendCsms($wechatMsg);
    }

}