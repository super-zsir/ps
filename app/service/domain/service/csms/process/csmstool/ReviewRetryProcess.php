<?php

namespace Imee\Service\Domain\Service\Csms\Process\Csmstool;

use Imee\Comp\Common\Phpnsq\NsqClient;
use Imee\Helper\Constant\NsqConstant;
use Imee\Models\Xss\CsmsAudit;
use Imee\Models\Xss\CsmsReviewLog;
use Imee\Service\Domain\Service\Csms\Exception\CsmsToolException;

class ReviewRetryProcess
{

    public function __construct($context)
    {
        $this->context = $context;
    }

    public $csmsCmd = [
        'csmsaudit' => 'csms.verify',
        'recheckcsms' => 'csms.second.verify',
    ];

    public function handle()
    {
        $ids = $this->context->ids;
        if(!$ids){
            CsmsToolException::throwException(CsmsToolException::REVIEW_RETRY_ERROR);
        }
        $reviews = CsmsReviewLog::find([
            'conditions' => 'id in ({ids:array})',
            'bind' => [
                'ids' => $ids
            ]
        ])->toArray();

        if(!$reviews){
            CsmsToolException::throwException(CsmsToolException::REVIEW_RETRY_NULL);
        }
        foreach ($reviews as $review){

            $cid = $review['cid'];
            $csmsAudit = CsmsAudit::findFirstValue($cid);
            if(!$csmsAudit){
                CsmsToolException::throwException(CsmsToolException::REVIEW_RETRY_REVIEWNULL);
            }


            $stage = $review['stage'];
            $cmd = $this->csmsCmd[$stage];


            $res = NsqClient::publish(NsqConstant::TOPIC_CSMS_REVIEW, array(
                'cmd' => $cmd,
                'data' => $csmsAudit,
            ));
            if(!$res){
                CsmsToolException::throwException(CsmsToolException::REVIEW_RETRY_FAILED);
            }

        }
        return false;
    }

}