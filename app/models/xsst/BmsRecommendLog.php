<?php

namespace Imee\Models\Xsst;

class BmsRecommendLog extends BaseModel
{
    public function beforeCreate()
    {
        $this->create_time = time();
    }

    public function beforeUpdate()
    {
        $this->update_time = time();
    }

	const RECOMMEND_HIDDEN = 1;
	const RECOMMEND_HIDDEN_CANCEL = 2;
	const RECOMMEND_CONFIRM = 3;
	const RECOMMEND_CANCEL = 4;

	public static $recommendType = [
		self::RECOMMEND_HIDDEN        => '隐藏',
		self::RECOMMEND_HIDDEN_CANCEL => '取消隐藏',
		self::RECOMMEND_CONFIRM       => '推荐',
		self::RECOMMEND_CANCEL        => '取消推荐'
	];
}