<?php

namespace Imee\Models\Shared;

class BmsSharedGiftOverview extends BaseModel
{
    protected static $primaryKey = 'id';

	protected $allowEmptyStringArr = ['excludes', 'tag_ids', 'description', 'jump_page'];
}
