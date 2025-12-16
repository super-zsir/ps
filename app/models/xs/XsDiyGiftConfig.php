<?php


namespace Imee\Models\Xs;


class XsDiyGiftConfig extends BaseModel
{
    protected static $primaryKey = 'id';

	protected $allowEmptyStringArr = [
		'bg',
	];
}