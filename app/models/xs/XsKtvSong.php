<?php

namespace Imee\Models\Xs;

class XsKtvSong extends BaseModel
{
	const STATUS_OFF = 0;
	const STATUS_ON = 1;
	const STATUS_FOREVER_OFF = -3;

	protected $allowEmptyStringArr = ['hq_music'];
}
