<?php

namespace Imee\Models\Xss;

class XssNewUserInvalidRecord extends BaseModel
{
	const USER_BIND_MOBILE_INVALID = 1;
	const USER_SAFE_MOBILE_INVALID = 2;
	const USER_PAY_ACCOUNT_INVALID = 3;
	const USER_IDENTITY_INVALID = 4;
	const USER_MAC_INVALID = 5;
}
