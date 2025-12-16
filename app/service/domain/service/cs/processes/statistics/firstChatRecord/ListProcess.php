<?php

namespace Imee\Service\Domain\Service\Cs\Processes\Statistics\FirstChatRecord;

use Imee\Models\Xs\XsBigarea;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xss\XssFirstChatMessage;
use Imee\Models\Xss\XssFirstChatRecord;
use Imee\Service\Domain\Context\Cs\Statistics\FirstChatRecord\ListContext;
use Imee\Service\Domain\Service\Abstracts\NormalListAbstract;

/**
 * 首次会话记录管理
 */
class ListProcess extends NormalListAbstract
{
    protected $context;
    protected $masterClass;
    protected $query;

    public function __construct(ListContext $context)
    {
		$this->context = $context;
		$this->masterClass = XssFirstChatRecord::class;
		$this->query = XssFirstChatRecord::query();
    }

    protected function buildWhere()
    {
        $where = ['condition' => [], 'bind' => []];

        if (!empty($this->context->start)) {
            $where['condition'][] = 'dateline >= :start:';
            $where['bind']['start'] = strtotime($this->context->start);
        }

        if (!empty($this->context->end)) {
            $where['condition'][] = 'dateline <= :end:';
            $where['bind']['end'] = strtotime($this->context->end) + 86400;
        }

        if (!empty($this->context->fromBigArea)) {
            $where['condition'][] = 'from_big_area = :from_big_area:';
            $where['bind']['from_big_area'] = $this->context->fromBigArea;
        }

		if (!empty($this->context->fromSex)) {
			$where['condition'][] = 'from_sex = :from_sex:';
			$where['bind']['from_sex'] = $this->context->fromSex;
		}

		if (!empty($this->context->toSex)) {
			$where['condition'][] = 'to_sex = :to_sex:';
			$where['bind']['to_sex'] = $this->context->toSex;
		}

        if (is_numeric($this->context->isReply)) {
            $where['condition'][] = 'is_reply = :is_reply:';
            $where['bind']['is_reply'] = $this->context->isReply;
        }

        $this->where = $where;
    }

	protected function formatList($items)
	{
		$format = [];
		$sids = [];
		if (empty($items)) {
			return $format;
		}

		foreach ($items as $item) {
			$tmp = $item->toArray();
			$format[] = $tmp;
			$sids[] = $item->sid;
		}
		if (empty($format)) {
			return $format;
		}

		$bigareaRes = XsBigarea::getAllNewBigArea();
		$allChatMsg = $this->getAllChatMessage($sids);

		foreach ($format as &$v) {
			$v['dateline'] = date("Y-m-d H:i:s", $v['dateline']);
			$v['from_big_area'] = $bigareaRes[$v['from_big_area']] ?? '-';
			$v['to_big_area'] = $bigareaRes[$v['to_big_area']] ?? '-';
			$v['from_sex'] = XsUserProfile::$sex_arr[$v['from_sex']] ?? '-';
			$v['to_sex'] = XsUserProfile::$sex_arr[$v['to_sex']] ?? '-';
			$v['is_reply'] = XssFirstChatRecord::$isReply[$v['is_reply']] ?? '-';
			$messages = $allChatMsg[$v['sid']] ?? [];
			if (!empty($messages)) {
				foreach ($messages as $key => $message) {
					$v['message' . $key] = $message;
				}
			}
		}

		return $format;
	}

	private function getAllChatMessage($sids)
	{
		if (empty($sids)) return [];

		$messages = XssFirstChatMessage::query()
			->inWhere('sid', $sids)
			->orderBy('dateline asc')
			->execute()
			->toArray();

		if (empty($messages)) return [];

		$res = [];
		foreach ($messages as $message) {
			$content = @json_decode($message['content'], true);
			if (empty($content)) continue;
			$res[$message['sid']][] = [
				'content' => $message['content'],
				'dateline' => date("Y-m-d H:i:s", $message['dateline']),
				'from_uid' => $message['from_user_id'],
				'object_name' => $message['object_name'],
			];
		}

		return $res;
	}
}
