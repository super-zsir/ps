<?php

/**
 * rpcx 形式
 */

namespace Imee\Comp\Common\Rpc;

class BaseRpcx
{
    public $gateway = '';
    public $host = '';
    private $_autoId = 0;
    private $_ch = null;
    private $_pid = 0;

    public function getMessageId()
    {
        $this->_autoId++;
        $pid = $this->getThreadId();
        return intval($pid . "" . rand(1, 99) . intval($this->microtimeFloat() * 1000 + $this->_autoId));
    }

    private function microtimeFloat()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    private function getThreadId()
    {
        if ($this->_pid == 0) {
            $pid = getmypid();
            if ($pid === false) {
                $pid = rand(1, 65535);
            }
            $this->_pid = $pid;
        }
        return $this->_pid;
    }

    public function request($servName, $method, $post, $autoClose = null)
    {
        if (is_array($post)) {
            $post = json_encode($post);
        }
        if (is_null($autoClose)) {
            $autoClose = substr(php_sapi_name(), 0, 3) == 'cli' ? false : true;
        }
        //geteway 会自己重试，这里不用处理重试
        return $this->makeQuery($servName, $method, $post, $autoClose);
    }

    //对于网络错误或者rpc内部错误，都抛出异常
    private function makeQuery($servName, $method, $post, $autoClose)
    {
        if ($this->_ch == null) {
            $this->_ch = curl_init();
        }

        //servName method 放在get请求中，便于lvs日志记录统计
        curl_setopt($this->_ch, CURLOPT_URL, $this->gateway . "/{$servName}/{$method}");
        curl_setopt($this->_ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_ch, CURLOPT_DNS_CACHE_TIMEOUT, 600);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->_ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($this->_ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($this->_ch, CURLOPT_HEADER, true); //输出header
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->_ch, CURLOPT_POST, 1);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post);

        // curl_setopt($this->_ch, CURLINFO_HEADER_OUT, true); //输出header

        $header = array(
            "Content-type: application/rpcx",
            "X-RPCX-MessageID: " . $this->getMessageId(),
            "X-RPCX-MesssageType: 0",
            "X-RPCX-SerializeType: 1",
            "X-RPCX-Meta: a=1",
            //"X-RPCX-ServicePath: " . $servName,
            //"X-RPCX-ServiceMethod: " . $method,
            "Content-Length: " . strlen($post),
            "Expect:"
        );

        //支持绑定host
        if ($this->host) {
            $header[] = "Host: " . $this->host;
        }

        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $header);
        // print_r(curl_getinfo($this->_ch));
        $response = curl_exec($this->_ch);
        $headerStr = curl_getinfo($this->_ch, CURLINFO_HEADER_OUT);
        $lastError = curl_error($this->_ch);
        $headerSize = curl_getinfo($this->_ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $resp = substr($response, $headerSize);

        // print_r($headerStr);
        // print_r("resp::\n");
        // print_r($response);
        // echo PHP_EOL;

        if ($autoClose) {
            curl_close($this->_ch);
            $this->_ch = null;
        }

        if (!empty($lastError)) {
            throw new \Exception("Exception:" . $lastError);
        }

        $map = array();
        preg_match_all("/(X\-Rpcx\-\S+): (.*?)\n/is", $header, $match);
        foreach ($match[1] as $index => $key) {
            $map[$key] = trim($match[2][$index]);
        }
        if (isset($map['X-Rpcx-Messagestatustype']) && $map['X-Rpcx-Messagestatustype'] == 'Error') {
            throw new \Exception($map['X-Rpcx-Errormessage']);
        }

        $res = @json_decode($resp, true);
        if ($res === false || is_null($res) || !is_array($res)) {
            //根据协议，理论上不存在这种可能，同时 rpc server 也不会直接返回最基本的数据类型
            $message = "[RpcBase]{$servName}/{$method} " . $post . ", " . $lastError;
            if (substr(php_sapi_name(), 0, 3) == 'cli') {
                echo "[" . date('Y-m-d H:i:s') . "]" . $message . "\n";
            }

            throw new \Exception('rpc response data error' . $message);
        }
        return $res;
    }

    public function close()
    {
        if ($this->_ch != null) {
            curl_close($this->_ch);
            $this->_ch = null;
        }
    }
}
