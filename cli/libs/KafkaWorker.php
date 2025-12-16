<?php

namespace Imee\Cli\Libs;

use Phalcon\Di;
use RdKafka;


$_wait_exit = false;

declare(ticks=1);

pcntl_signal(SIGTERM, function ($signo) {
    global $_wait_exit;
    echo date('Y-m-d H:i:s') . " => sig_handler {$signo}\n";
    if ($signo == SIGTERM) {
        //wait to exit
        $_wait_exit = true;
    }
});


class KafkaWorker
{
    private $_callback;
    private $_messageErrorCallback;

    private $_className;
    private $_consumer;
    private $_topics;
    private $_groupName;

    public function __construct(array $topics, $groupName, callable $callback, $brokerlist = '172.16.0.148:9092,172.16.0.149:9092,172.16.0.150:9092')
    {
        $broker_config = Di::getDefault()->getShared('config')->kafka_brokerlist;
        $brokerlist = $broker_config ? $broker_config : $brokerlist;

        $this->_topics = $topics;
        $this->_groupName = $groupName;
        $this->_callback = $callback;
        $this->_className = get_class($callback[0]);
        cli_set_process_title("php-cli-kafka-group-" . $groupName);

        $conf = new RdKafka\Conf();
        $conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
            switch ($err) {
                case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
                    echo "Assign: \n";
                    $kafka->assign($partitions);
                    break;

                case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
                    echo "Revoke: \n";
                    $kafka->assign(NULL);
                    break;

                default:
                    throw new \Exception($err);
            }
        });
        $conf->set('group.id', $this->_groupName);
        $conf->set('metadata.broker.list', $brokerlist);
        $conf->set('auto.offset.reset', 'latest');
        $conf->set('enable.auto.commit', 'false');
        // $conf->set('enable.auto.commit', 'true');

        $this->_consumer = new RdKafka\KafkaConsumer($conf);
        $this->_consumer->subscribe($this->_topics);
    }

    public function start()
    {
//		echo "Waiting for partition assignment... (make take some time when\n";
//		echo "quickly re-joining the group after leaving it.)\n";
        $this->run();
    }

    public function setMessageError($callback)
    {
        $this->_messageErrorCallback = $callback;
    }

    public function parseCanalJson($message)
    {
        $json = json_decode($message->payload, true);
        if (!$json || (isset($json['isDdl']) && $json['isDdl'])) {
            return false;
        }
        if ($json['type'] == 'INIT' || $json['type'] == 'INSERT') {
            return array(
                'db'    => $json['database'],
                'table' => $json['table'],
                'data'  => $json['data'],
                'type'  => 'write',
                'ts'    => $json['ts'],
            );
        } else if ($json['type'] == 'DELETE') {
            //删除数据
            return array(
                'db'    => $json['database'],
                'table' => $json['table'],
                'data'  => $json['old'],
                'type'  => 'delete',
                'ts'    => $json['ts'],
            );
        } else if ($json['type'] == 'UPDATE') {
            //更新数据
            $data = array();
            foreach ($json['data'] as $index => $after) {
                $data[] = array(
                    'before' => $json['old'][$index],
                    'after'  => $after,
                );
            }
            return array(
                'db'    => $json['database'],
                'table' => $json['table'],
                'data'  => $data,
                'type'  => 'update',
                'ts'    => $json['ts'],
            );
        } else {
            return false;
        }
    }

    private function run()
    {
        global $_wait_exit;
        while (true) {
            $message = $this->_consumer->consume(60 * 1000);
            switch ($message->err) {
                case RD_KAFKA_RESP_ERR_NO_ERROR:
                    try {
                        $r = call_user_func_array($this->_callback, array($message));
                    } catch (\Exception $e) {
                        sleep(3);
                        throw $e;
                    }
                    if ($r === false) {
                        $this->_consumer->commit($message);
                    } else {
                        sleep(3);
                        $_wait_exit = true;
                    }
                    if ($_wait_exit) {
                        return;
                    }
                    break;

                case RD_KAFKA_RESP_ERR__PARTITION_EOF:
                    // echo "No more messages; will wait for more\n";
                    break;

                case RD_KAFKA_RESP_ERR__TIMED_OUT:
                    echo "Timed out\n";
                    call_user_func_array($this->_callback, [new \RdKafka\Message()]);
                    break;

                default:
                    throw new \Exception($message->errstr(), $message->err);
            }
        }
    }
}
