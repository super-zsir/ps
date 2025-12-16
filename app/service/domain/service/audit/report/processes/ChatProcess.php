<?php

namespace Imee\Service\Domain\Service\Audit\Report\Processes;

use Imee\Models\Xss\XsChatMessageNew;
use Imee\Service\Domain\Service\Audit\Report\Context\ChatContext;
use Imee\Service\Domain\Service\Audit\Report\Traits\CommonTrait;

class ChatProcess
{
    use CommonTrait;
    /**
     * @param ChatContext $context
     * @return array
     */
    public function getList(ChatContext $context)
    {
        $conditions = array(
            'sid' => $context->sid,
            'from_user_id' => $context->fromUserId,
        );
        $conditions = self::commonFilter($conditions);
        $num = XsChatMessageNew::handleTotal($conditions);
        $conditions['orderBy'] = $context->sort ? "{$context->sort} {$context->dir}" : '';
        $conditions['limit'] = $context->limit;
        $conditions['offset'] = $context->offset;
        $conditions['columns'] = ['id'];
        $res = XsChatMessageNew::handleList($conditions);
        if (empty($res)) {
            return ['data' => [], 'total' => 0];
        }
        $resIds = array_column($res, 'id');
        $res = XsChatMessageNew::handleList(array(
            'id_array' => $resIds,
            'orderBy' => $context->sort ? "{$context->sort} {$context->dir}" : ''
        ));
        return array('data' => $res, 'total' => intval($num));
    }
}