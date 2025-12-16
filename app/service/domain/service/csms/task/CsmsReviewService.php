<?php


namespace Imee\Service\Domain\Service\Csms\Task;

use Imee\Service\Domain\Service\Csms\Process\Callback\BbuUnionProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\CircleVerifyProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\CircleCommentProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\FriendCardProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\IconIdGsProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsChatroomIconProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsFleetIconProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsLiveConfigProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsOrderVoteProcess;
use Imee\Service\Domain\Service\Csms\Process\Callback\XsScreenImageProcess;

class CsmsReviewService extends ReviewBaseService
{


    /**
     * 组装status
     */
    public function reviewStatus()
    {
        $this->reviewData['status'] = (int)$this->data['deleted'];
        $this->reviewData['admin'] = (int)$this->data['op'];
    }

    /**
     * 声音审核 - 初审处理
     * @param array $data
     * @return false|void
     */
    public function xsFriendCard()
    {
        $process = new FriendCardProcess();
        $this->result = $process->handle($this->reviewData);
    }



    /**
     * 朋友圈评论 - 初审处理
     * @param array $data
     * @return bool|void
     */
    public function circleComment()
    {
        $reviewData = $this->reviewData;
        if($this->data['extra']){
            $extra = json_decode($this->data['extra'], true);
            $reviewData = array_merge($reviewData, $extra);
        }
        $process = new CircleCommentProcess();
        $this->result = $process->handle($reviewData);
    }

    /**
     * 粉丝牌审核
     */
    public function xsLiveConfig()
    {
        $process = new XsLiveConfigProcess();
        $this->result = $process->handle($this->reviewData);
    }


    /**
     * 房间公屏
     */
    public function xsScreenImage()
    {
        $process = new XsScreenImageProcess();
        $this->result = $process->handle($this->reviewData);
    }


    /**
     * 联盟审核
     */
    public function bbuUnion()
    {
        $process = new BbuUnionProcess();
        $this->result = $process->handle($this->reviewData);
    }

    /**
     * 订单评论
     */
    public function xsOrderVote()
    {
        $process = new XsOrderVoteProcess();
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
