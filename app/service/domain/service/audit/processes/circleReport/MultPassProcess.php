<?php


namespace Imee\Service\Domain\Service\Audit\Processes\CircleReport;


use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xs\XsCircleReport;
use Imee\Models\Xsst\XsstCircleVerifyLog;
use Imee\Service\Domain\Service\Audit\Context\CircleReport\MultPassContext;
use Imee\Service\Domain\Service\Audit\Exception\CsmsException;

class MultPassProcess
{

    protected $context;

    public function __construct(MultPassContext $context)
    {
        $this->context = $context;
    }

    public function handle()
    {
        $ids = $this->context->ids;
        $status = $this->context->status;
        $reason = $this->context->reason;
        $admin = $this->context->admin;


        if($status == 'pending'){
            CsmsException::throwException(CsmsException::CIRCLE_REPORT_ERROR);
        }

        if($status == 'empty' && empty($reason)){
            CsmsException::throwException(CsmsException::CIRCLE_REPORT_EMPTY);
        }

        if($ids){
            $reports = XsCircleReport::find([
                'conditions' => 'rpid in ({ids:array})',
                'bind' => [
                    'ids' => $ids
                ]
            ]);
            if($reports){
                foreach ($reports as $report){

                    $report->status = $status;
                    $report->save();

                    // 朋友圈举报 清空，直接影响 动态或者评论 下线
                    if ($status == XsCircleReport::STATUS_EMPTY) {
                        if ($report->rotype == 'topic') {
                            NsqClient::publish(NsqConstant::TOPIC_XS_CIRCLE, array(
                                'cmd' => 'topic.verify',
                                'data' => array(
                                    'uid' => intval($report->ruid),
                                    'topic_id' => intval($report->roid),
                                    'result' => 'failed',
                                    'reason' => $reason
                                )
                            ));
                        } elseif ($report->rotype == 'comment') {
                            NsqClient::publish(NsqConstant::TOPIC_XS_CIRCLE, array(
                                'cmd' => 'comment.verify',
                                'data' => array(
                                    'cmtid' => intval($report->roid),
                                    'topic_id' => intval($report->tpid),
                                    'result' => 'failed',
                                    'reason' => $reason
                                ),
                            ));
                        }
                    }

                    $verifyLog = new XsstCircleVerifyLog();
                    $verifyLog->relate_id = $report->rpid;
                    $verifyLog->type = 3;
                    $verifyLog->admin = $admin;
                    $verifyLog->operate = $status;
                    $verifyLog->reason = $reason;
                    $verifyLog->create_time = $report->create_time;
                    $verifyLog->dateline = time();
                    $verifyLog->app_id = APP_ID;
                    $verifyLog->language = $report->language;
                    $verifyLog->audit_item = $report->rotype;
                    $verifyLog->save();
                }
            }
        }

    }
}
