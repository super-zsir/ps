<?php

/**
 * 权限系统云之家参数
 */

namespace Imee\Helper\Traits;

trait AuthTrait
{

    protected $type = 'xrxs';

    protected $formCodeId = '748163';

    protected $clientId = 'a3f9967471fe707e2750bc659ec72439';
    protected $clientSecret = 'faa7e4fcaa2ec16643b6a0a766a9d04373b424ba';

    protected $flowParams = [
        '_app'        => [
            'control' => 'Const',
            'id'      => '377ebe6b8911446fb88d587a2d1cfbe1',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
            'value'   => [
                'fieldValue'   => 'PS（Party Star）',
                'fieldValueId' => 'ccb910a13bca46dabbf0af09a6347190',
            ],
            /*
            'value'   => [
                'fieldValue'   => 'PA（Pati）',
                'fieldValueId' => '87890ba200c14749b165e36f025daa49',
            ],
            */
        ],
        'title'      => [
            'control' => 'Text',
            'id'      => '0d38526428df40e386cc9d04cef50c9a',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
        ],
        'apply_time' => [ //申请时间
            'control' => 'Text',
            'id'      => 'edcb00c81d5141cfbe536a66d5e0d88b',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
        ],
        'type'       => [
            'control' => 'Select',
            'id'      => 'ad63b990390940eab7f4926660b2982b',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
            'map' => [
                '1' => [
                    'fieldValue'   => '通用General',
                    'fieldValueId' => '0e5bffd4586446e797836569c5316171',
                ],
                '2' => [
                    'fieldValue'   => '敏感Sensitive',
                    'fieldValueId' => '2eca0e7079834f04bd1a2d8b2c7c42a2',
                ],
            ],
        ],
        'apply_info' => [ //权限详情
            'control' => 'Text',
            'id'      => '30fc6c48a28b4838b453e235f5bf2567',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
        ],
        'reason'     => [
            'control' => 'Text',
            'id'      => 'cd21507356644a38893a9505673734ec',
            'groupId' => '028c391d778d480cb6a9f7fb20abd9fd',
        ],
    ];

    protected $stateMap = [
        1 => 'FINISH',
        2 => 'DISAGREE',
        3 => 'DISAGREE',
        4 => 'DISAGREE',
        5 => 'DISAGREE',
    ];

    /*protected $clientId = ENV == 'dev' ? '388258d64d044253fcef60d46f4cd299' : '7e4411812a86a1835f8e64f770642169';
    protected $clientSecret = ENV == 'dev' ? 'd562cf57aa979520d615fbf8ba13c45b61377e67' : 'b2c2ce1fe2eea898746ae70b9096fc516593908c';

    // 审批流程模板ID
    protected $formCodeId = '1469636330fd4a3697cb149474e4a612';

    // 模板参数
    protected $flowParams = [
        'title'      => [
            'type'  => 'string',
            'field' => '_S_TITLE',
        ],
        'apply_time' => [ //申请时间
            'type'  => 'string',
            'field' => '_S_DATE',
        ],
        'type'       => [ //权限类型
            'type'   => 'enum',
            'field'  => 'Ra_1',
            'map' => [
                '1' => 'AaBaCcDd', //通用
                '2' => 'EeFfGgHh', //敏感
            ],
        ],
        'apply_info' => [ //权限详情
            'type'  => 'string',
            'field' => 'Ta_0',
        ],
        'reason' => [
            'type'  => 'string',
            'field' => 'Te_0',
        ],
    ];*/
}
