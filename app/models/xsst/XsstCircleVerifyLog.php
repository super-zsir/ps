<?php
namespace Imee\Models\Xsst;

class XsstCircleVerifyLog extends BaseModel
{
	const VERIFY_TYPE_OP = 'op';
	const VERIFY_TYPE_OP2 = 'op2';

	const TYPE_CIRCLE = 1;                    // 朋友圈
	const TYPE_CIRCLE_COMMENT = 2;            // 朋友圈评论
	const TYPE_CIRCLE_REPORT = 3;            // 朋友圈举报

	const AUDIT_ITEM_COMMENT = 'circle_comment';        // 朋友圈评论选项
	const AUDIT_ITEM_REPORT = 'circle_report';            // 朋友圈举报选项
}