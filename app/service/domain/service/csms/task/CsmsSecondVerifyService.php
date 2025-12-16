<?php


namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Service\Domain\Service\Csms\Process\Callback\CircleVerifyProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\CircleCommentProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\FriendCardProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\IconIdGsProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsChatroomIconProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsFleetIconProcess;

/**
 * 内容管理系统 - 复审外显
 * Class CsmsSecondVerifyService
 * @package Imee\Service\Domain\Service\Csms\Task
 */
class CsmsSecondVerifyService extends ReviewBaseService
{


    /**
     * 组装status
     */
    public function reviewStatus()
    {
        $this->reviewData['status'] = (int)$this->data['deleted2'];
        $this->reviewData['admin'] = (int)$this->data['op2'];
    }



    /**
     * 用户昵称 - 复审处理
     * @param $data
     */
    public function xsUserName($data)
    {
        print_r($data);
    }

    /**
     * 朋友圈评论 - 复审处理
     * @param array $data
     * @return bool|void
     */
    public function circleComment(array $data)
    {
        $reviewData = $this->reviewData;
        $reviewData = array_merge($reviewData, json_decode($this->data['extra'], true));
        $process = new CircleCommentProcess();
        $this->result = $process->handle($reviewData);
    }

    /**
     * 声音审核 - 初审处理
     * @param array $data
     * @return false|void
     */
    public function xsFriendCard(array $data)
    {
        $process = new FriendCardProcess();
        $this->result = $process->handle($this->reviewData);
    }


    /**
     * 朋友圈审核
     * @return array
     */
    public function circleVerify()
    {
        $process = new CircleVerifyProcess();
        $this->result = $process->handle($this->reviewData);
    }


    /**
     * 印尼GS用户头像审核
     */
    public function iconIdGs()
    {
        $process = new IconIdGsProcess();
        $this->result = $process->handle($this->reviewData);
    }

    /**
     * 家族封面外显
     */
    public function xsFleetIcon()
    {
        $process = new XsFleetIconProcess();
        $this->result = $process->handle($this->reviewData);
    }

    /**
     * 房间封面审核
     */
    public function xsChatroomIcon()
    {
        $process = new XsChatroomIconProcess();
        $this->result = $process->handle($this->reviewData);
    }


}
