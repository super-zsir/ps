<?php

namespace Imee\Models\Config;

class BbcWebTitle extends BaseModel
{
    public static $linkPrefix = 'https://page.partystar.chat/img-viewer/?id=%d';
	public static $linkDevPrefix = 'http://partystar-dev.iambanban.com/frontend/img-viewer/?id=%d';

    protected static $primaryKey = 'id';
}