<?php

namespace Imee\Controller\Operate\Activity;

use Imee\Comp\Common\Export\Service\ExportService;
use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Controller\BaseController;
use Imee\Export\Operate\Activity\ActivityAwardListExport;
use Imee\Export\Operate\Activity\ActivityRankTemplateExport;
use Imee\Models\Config\BaseModel;
use Imee\Models\Config\BbcActGiftGroup;
use Imee\Models\Config\BbcRankAward;
use Imee\Models\Config\BbcRankButtonList;
use Imee\Models\Config\BbcRankButtonTag;
use Imee\Models\Config\BbcRankScoreConfig;
use Imee\Models\Config\BbcRankWhiteList;
use Imee\Models\Config\BbcTemplateConfig;
use Imee\Models\Xs\XsCertificationSign;
use Imee\Models\Xs\XsChatroomBackgroundMall;
use Imee\Models\Xs\XsCommodity;
use Imee\Models\Xs\XsCoupon;
use Imee\Models\Xs\XsCustomizePrettyStyle;
use Imee\Models\Xs\XsItemCard;
use Imee\Models\Xs\XsMedalResource;
use Imee\Models\Xs\XsNameIdLightingGroup;
use Imee\Models\Xs\XsPropCard;
use Imee\Models\Xs\XsPropCardConfig;
use Imee\Models\Xs\XsRoomSkin;
use Imee\Models\Xs\XsRoomTopCard;
use Imee\Models\Xs\XsUserProfile;
use Imee\Service\Operate\Activity\AwardListService;
use Imee\Service\Operate\Activity\AwardService;
use Imee\Service\Operate\Activity\BlackListService;
use Imee\Service\Operate\Activity\ButtonGiftListService;
use Imee\Service\Operate\Activity\ButtonGiftTagService;
use Imee\Service\Operate\Activity\ButtonListService;
use Imee\Service\Operate\Activity\ButtonTagService;
use Imee\Service\Operate\Activity\ScoreSetService;
use Phalcon\Di;
use Imee\Service\Helper;
use Imee\Service\Operate\Activity\ActivityService;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftListCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftListUpdateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftListWeekStarCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftListWeekStarUpdateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftTagWeekStarCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftTagWeekStarUpdateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftTagCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonGiftTagUpdateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonListCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonListUpdateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonTagCreateValidation;
use Imee\Controller\Validation\Operate\Activity\ButtonTagUpdateValidation;

class ActivityController extends BaseController
{
    /**
     * @var ActivityService
     */
    private $service;

    public function onConstruct()
    {
        parent::onConstruct();
        $this->service = new ActivityService();
    }

    /**
     * @page activity
     * @name 活动模板
     */
    public function mainAction()
    {
    }

    /**
     * @page activity
     * @point 列表
     */
    public function listAction()
    {
        $filter = [
            'id'           => intval($this->params['id'] ?? 0),
            'status'       => intval($this->params['status'] ?? 0),
            'audit_status' => intval($this->params['audit_status'] ?? 0),
            'admin_id'     => intval($this->params['operate_id'] ?? 0),
            'bigarea'      => intval($this->params['bigarea_id'] ?? 0),
            'onlineMode'   => intval($this->params['online_mode'] ?? 0),
            'start'        => trim($this->params['start'] ?? ''),
            'end'          => trim($this->params['end'] ?? ''),
        ];
        $query = [
            'pageSize' => $this->params['limit'],
            'page'     => $this->params['page']
        ];
        $res = $this->service->getActivityList($filter, $query);
        return $this->outputSuccess($res['data'] ?? [], ['total' => $res['total'] ?? 0]);
    }

    /**
     * @page activity
     * @point 编辑
     */
    public function modifyAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $admin = intval($this->uid);
        if ($c == "modify") {
            $data = $this->request->getPost();
            if (!isset($data['id'])) {
                return $this->outputError(-1, '数据出现问题');
            }
            $res = $this->service->activeModify($data);
            return $this->outputSuccess($res);
        } elseif ($c == "add") {
            $data = $this->request->getPost();
            $res = $this->service->activeCreate($data);
            return $this->outputSuccess($res);
        } else if ($c == 'version') {
            $type = $this->request->getQuery('type', 'int', 0);
            $res = $this->service->getActiveVersion($type);
            return $this->outputJson($res);
        } elseif ($c == "info") {
            $id = $this->request->get("id", 'int', 0);
            $data = BbcTemplateConfig::findFirst($id);
            if ($data) {
                $res = $data->toArray();
                // 默认展示7
                if ($res['data_period'] == 0) {
                    $res['data_period'] = 7;
                }
                $res['online_mode'] = "{$res['online_mode']}";
                $bigareaIds = explode('|', $res['bigarea_id']);
                $res['bigarea_id[]'] = $bigareaIds;
                $res['gift_act_ids'] = str_replace(',', "\n", $res['gift_act_ids']);
                $res['page_url'] = $this->service->getInfoPageUrl($id);
                $res['vision_type'] = (int)$res['vision_type'];
                $res['relate_type'] = $this->service->getRelateType($res['relate_id']);
                $res['vision_type'] = (int)$res['vision_type'];
                $res['relate_icon_preview'] = Helper::getHeadUrl($res['relate_icon']);
                $res['relate_icon_preview'] = "<img width='50px' height='50px' src='{$res['relate_icon_preview']}' />";
                $res['rule_img_new'] = $res['rule_img'];
                $res['rule_button_img_new'] = $res['rule_button_img'];
                $res['rule_content_json'] = json_decode($res['rule_content_json'], true);
                $res['award_content_json'] = json_decode($res['award_content_json'], true);
                $res['gift_act_type'] = $res['gift_act_ids'] == ActivityService::ALL_CUSTOMIZED_GIFT ? '1' : '2';
                $extra = @json_decode($res['extra'], true);
                $res['relate_btn_text'] = $extra['relate_btn_text'] ?? '';
                $res = array_merge($res, $this->service->formatVisionJson($res['vision_content_json']));
                $res['version_id'] = BbcTemplateConfig::VERSION_ID;
                return $this->outputSuccess($res);
            }
            return $this->outputSuccess();
        } elseif ($c == 'pubCheck') {
            $id = $this->request->get("id", 'int', 0);
            list($res, $msg, $data) = $this->service->publishCheck($id);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess($data);
        } elseif ($c == "pub") {
            $id = $this->request->getPost("id", 'int', 0);
            $descPath = $this->request->getPost("desc_path", 'trim', '');
            list($res, $msg) = $this->service->publishOld($id, $descPath);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess($msg);
        } elseif ($c == "link") {
            // 关闭模版链接生成入口
            return $this->outputError(-1, '模版链接可通过新增/编辑生成');
        } elseif ($c == "copy") {
            $id = $this->request->getPost('id', 'int', 0);
            if ($id < 1) return $this->outputError(-1, '数据出现问题');
            $rec = BbcTemplateConfig::useMaster()->findFirst($id);
            if (!$rec) return $this->outputError(-1, '当前数据不存在，请确认');
            $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
            $conn->begin();
            try {
                //复制活动
                $tmpRows = $rec->toArray();
                $activeAdd = BbcTemplateConfig::useMaster();
                foreach ($tmpRows as $field => $field_v) {
                    $value = $field_v;
                    if ($field == 'id') {
                        continue;
                    }
                    if ($field == 'status' || $field == 'publisher_id') {
                        $value = 0;
                    }
                    if ($field == 'admin_id') {
                        $value = $admin;
                    }
                    $activeAdd->{$field} = $value;
                }
                $activeAdd->dateline = time();

                $activeAdd->save();
                //新增活动id
                $activeId = $activeAdd->id;

                //复制buttonTag buttonList
                $button_tag = BbcRankButtonTag::useMaster()->find(array(
                    "conditions" => "act_id = :act_id:",
                    "bind"       => [
                        'act_id' => $tmpRows['id'],
                    ]
                ))->toArray();
                if ($button_tag) {
                    foreach ($button_tag as $item) {
                        //复制button_tag
                        $tmpRowsTag = array(
                            'act_id'              => $activeId,
                            'button_tag_type'     => $item['button_tag_type'],
                            'button_content'      => $item['button_content'],
                            'rank_object'         => $item['rank_object'],
                            'cycle_gift_id'       => $item['cycle_gift_id'],
                            'cycle_gift_id_num'   => $item['cycle_gift_id_num'],
                            'cycles'              => $item['cycles'],
                            'rank_object_content' => $item['rank_object_content'],
                            'tag_list_type'       => $item['tag_list_type'],
                            'top1_show'           => $item['top1_show'],
                            'admin_id'            => $admin,
                            'dateline'            => time(),
                        );
                        $buttonTagAdd = BbcRankButtonTag::useMaster();
                        foreach ($tmpRowsTag as $k => $v) {
                            $buttonTagAdd->{$k} = $v;
                        }
                        $buttonTagAdd->save();
                        $buttonTagId = $buttonTagAdd->id;

                        if ($item['rank_object'] == BbcRankButtonTag::RANK_OBJECT_GIFT) {
                            $giftGroups = BbcActGiftGroup::useMaster()->getListByWhere([
                                ['button_tag_id', '=', $item['id']],
                                ['act_id', '=', $tmpRows['id']],
                            ], 'name, icon, gift_act_ids');
                            if ($giftGroups) {
                                foreach ($giftGroups as &$group) {
                                    $group['act_id'] = $activeId;
                                    $group['button_tag_id'] = $buttonTagId;
                                }
                                BbcActGiftGroup::addBatch($giftGroups);
                            }
                        }
                        $button_list = BbcRankButtonList::useMaster()->find(array(
                            "conditions" => "button_tag_id = :button_tag_id:",
                            "bind"       => [
                                'button_tag_id' => $item['id'],
                            ]
                        ))->toArray();
                        if ($button_list) {
                            foreach ($button_list as $value) {
                                //复制button_list
                                $tmpRowsList = array(
                                    'act_id'                    => $activeId,
                                    'button_tag_id'             => $buttonTagId,
                                    'button_content'            => $value['button_content'],
                                    'button_desc'               => $value['button_desc'],
                                    'rank_tag'                  => $value['rank_tag'],
                                    'is_upgrade'                => $value['is_upgrade'],
                                    'start_time'                => $value['start_time'],
                                    'end_time'                  => $value['end_time'],
                                    'upgrade_num'               => $value['upgrade_num'],
                                    'is_award'                  => $value['is_award'],
                                    'award_time'                => $value['award_time'],
                                    'rank_list_num'             => $value['rank_list_num'],
                                    'is_open'                   => $value['is_open'],
                                    'level'                     => $value['level'],
                                    'p_level'                   => $value['p_level'],
                                    'room_support'              => $value['room_support'],
                                    'upgrade_type'              => $value['upgrade_type'],
                                    'upgrade_score'             => $value['upgrade_score'],
                                    'upgrade_extend_num'        => $value['upgrade_extend_num'],
                                    'cp_gender'                 => 1,
                                    'divide_track'              => $value['divide_track'],
                                    'days'                      => $value['days'],
                                    'score_max'                 => $value['score_max'],
                                    'score_min'                 => $value['score_min'],
                                    'show_honour'               => $value['show_honour'],
                                    'honour_desc'               => $value['honour_desc'],
                                    'hide_score'                => $value['hide_score'],
                                    'broker_distance_start_day' => $value['broker_distance_start_day'],
                                    'divide_type'               => $value['divide_type'],
                                    'divide_object'             => $value['divide_object'],
                                    'admin_id'                  => $admin,
                                    'has_prize_pool'            => $value['has_prize_pool'],
                                    'prize_pool_proportion'     => $value['prize_pool_proportion'],
                                    'cycle_days'                => $value['cycle_days'],
                                    'cycle_limit'               => $value['cycle_limit'],
                                    'is_only_cross_room_pk'     => $value['is_only_cross_room_pk'],
                                    'is_total_wins'             => $value['is_total_wins'],
                                    'total_wins_extend'         => $value['total_wins_extend'],
                                    'dateline'                  => time(),
                                );
                                $buttonListAdd = BbcRankButtonList::useMaster();
                                foreach ($tmpRowsList as $k => $v) {
                                    $buttonListAdd->{$k} = $v;
                                }
                                $buttonListAdd->save();
                                $buttonListId = $buttonListAdd->id;

                                $randAward = BbcRankAward::useMaster()->find(array(
                                    "conditions" => "button_list_id = :button_list_id:",
                                    "bind"       => [
                                        'button_list_id' => $value['id'],
                                    ]
                                ))->toArray();
                                if ($randAward) {
                                    //复制奖品
                                    foreach ($randAward as $award) {
                                        $tmpRowsAward = array(
                                            'act_id'             => $activeId,
                                            'button_list_id'     => $buttonListId,
                                            'rank'               => $award['rank'],
                                            'award_type'         => $award['award_type'],
                                            'num'                => $award['num'],
                                            'diamond_proportion' => $award['diamond_proportion'],
                                            'cid'                => $award['cid'],
                                            'rank_award_type'    => $award['rank_award_type'],
                                            'score_max'          => $award['score_max'],
                                            'score_min'          => $award['score_min'],
                                            'exp_days'           => $award['exp_days'],
                                            'award_object_type'  => $award['award_object_type'],
                                            'extend_rank_max'    => $award['extend_rank_max'],
                                            'extend_rank_min'    => $award['extend_rank_min'],
                                            'admin_id'           => $admin,
                                            'dateline'           => time(),
                                            'award_extend_info'  => $award['award_extend_info'],
                                            'can_transfer'       => $award['can_transfer'],
                                            'award_object'       => $award['award_object']
                                        );
                                        BbcRankAward::addRows($tmpRowsAward);
                                    }
                                }

                                $randScore = BbcRankScoreConfig::useMaster()->find(array(
                                    "conditions" => "button_list_id = :button_list_id:",
                                    "bind"       => [
                                        'button_list_id' => $value['id'],
                                    ]
                                ))->toArray();
                                if ($randScore) {
                                    //复制奖品
                                    foreach ($randScore as $score) {
                                        $tmpRowsScore = array(
                                            'act_id'                => $activeId,
                                            'button_list_id'        => $buttonListId,
                                            'score'                 => $score['score'],
                                            'type'                  => $score['type'],
                                            'gift_id'               => $score['gift_id'],
                                            'recharge_channels'     => $score['recharge_channels'],
                                            'pk_valid_extend'       => $score['pk_valid_extend'],
                                            'is_check_pk_valid'     => $score['is_check_pk_valid'],
                                            'admin_id'              => $admin,
                                            'dateline'              => time(),
                                        );
                                        BbcRankScoreConfig::addRows($tmpRowsScore);
                                    }
                                }


                            }
                        }
                        if (!in_array($item['rank_object'], [1, 2, 10])) {
                            $userList = BbcRankWhiteList::useMaster()->find(array(
                                "conditions" => "button_tag_id = :button_tag_id:",
                                "bind"       => [
                                    'button_tag_id' => $item['id'],
                                ]
                            ))->toArray();
                            if ($userList) {
                                foreach ($userList as $user) {
                                    $tmpRowsuser = array(
                                        'act_id'        => $activeId,
                                        'button_tag_id' => $buttonTagId,
                                        'uid'           => $user['uid'],
                                        'extend_id'     => $user['extend_id'],
                                        'admin_id'      => $admin,
                                        'dateline'      => time(),
                                        'type'          => $user['type']
                                    );
                                    $RankWhiteListAdd = BbcRankWhiteList::useMaster();
                                    foreach ($tmpRowsuser as $k => $v) {
                                        $RankWhiteListAdd->{$k} = $v;
                                    }
                                    $RankWhiteListAdd->save();
                                }
                            }
                        }
                    }
                }
                //更新活动链接
                $this->service->updatePageUrl($activeId, $activeAdd->status);
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }

            return $this->outputSuccess();
        } elseif ($c == "del") {
            $id = $this->request->get("id", 'int', 0);
            $res = array();
            try {
                $data = BbcTemplateConfig::findFirst($id);
                if ($data) {
                    $buttonTag = BbcRankButtonTag::find(array(
                        "conditions" => "act_id = :act_id:",
                        "bind"       => [
                            'act_id' => $id,
                        ]
                    ))->toArray();
                    if ($buttonTag) {
                        foreach ($buttonTag as $item) {
                            $buttonList = BbcRankButtonList::find(array(
                                "conditions" => "button_tag_id = :button_tag_id:",
                                "bind"       => [
                                    'button_tag_id' => $item['id'],
                                ]
                            ))->toArray();
                            if ($buttonList) {
                                foreach ($buttonList as $value) {
                                    $rankAward = BbcRankAward::useMaster()->find(array(
                                        "conditions" => "button_list_id = :button_list_id:",
                                        "bind"       => [
                                            'button_list_id' => $value['id'],
                                        ]
                                    ));
                                    if ($rankAward) {
                                        $rankAward->delete();
                                    }
                                    $randScore = BbcRankScoreConfig::useMaster()->find(array(
                                        "conditions" => "button_list_id = :button_list_id:",
                                        "bind"       => [
                                            'button_list_id' => $value['id'],
                                        ]
                                    ));
                                    if ($randScore) {
                                        $randScore->delete();
                                    }
                                }
                                $buttonListIds = implode(',', array_column($buttonList, 'id'));
                                Helper::exec("DELETE FROM bbc_rank_button_list WHERE id IN ($buttonListIds)", BaseModel::SCHEMA);
                            }
                            $userList = BbcRankWhiteList::useMaster()->find(array(
                                "conditions" => "button_tag_id = :button_tag_id:",
                                "bind"       => [
                                    'button_tag_id' => $item['id'],
                                ]
                            ));
                            if ($userList) {
                                $userList->delete();
                            }

                        }
                        $buttonTagids = implode(',', array_column($buttonTag, 'id'));
                        Helper::exec("DELETE FROM bbc_rank_button_tag WHERE id IN ($buttonTagids)", BaseModel::SCHEMA);
                    }
                    $data->delete();
                }
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess($res);
        } elseif ($c == "export") {
            $id = $this->request->getQuery('id', 'int', 0);
            if ($id < 1) return $this->outputError(-1, '数据出现问题');
            $rec = BbcTemplateConfig::findFirst($id);
            if (!$rec) return $this->outputError(-1, '当前数据不存在，请确认');
            $dataParam = [
                'id' => intval($id),
            ];
            return $this->syncExportWork('ActivityRankTemplateExport', ActivityRankTemplateExport::class, $dataParam, '活动榜单导出');
        } elseif ($c == 'export_config') {
            $id = $this->request->getQuery('id', 'int', '0');
            if ($id < 1) {
                exit('数据出现问题');
            }
            $arr = [];
            $rec = BbcTemplateConfig::findFirst($id);
            if (!$rec) {
                exit('当前数据不存在，请确认');
            }
            $arr['temp_config'] = $rec->toArray();
            $button_tag = BbcRankButtonTag::find(array(
                "conditions" => "act_id = :act_id:",
                "bind"       => [
                    'act_id' => $id,
                ]
            ))->toArray();
            if ($button_tag) {
                $arr['button_tag'] = $button_tag;
            }
            $button_list = BbcRankButtonList::find(array(
                "conditions" => "act_id = :act_id:",
                "bind"       => [
                    'act_id' => $id,
                ]
            ))->toArray();
            if ($button_list) {
                $arr['button_list'] = $button_list;
            }
            $userList = BbcRankWhiteList::find(array(
                "conditions" => "act_id = :act_id:",
                "bind"       => [
                    'act_id' => $id,
                ]
            ))->toArray();
            if ($userList) {
                $arr['userList'] = $userList;
            }
            $randAward = BbcRankAward::find(array(
                "conditions" => "act_id = :act_id:",
                "bind"       => [
                    'act_id' => $id,
                ]
            ))->toArray();
            if ($randAward) {
                $arr['randAward'] = $randAward;
            }
            $randScore = BbcRankScoreConfig::find(array(
                "conditions" => "act_id = :act_id:",
                "bind"       => [
                    'act_id' => $id,
                ]
            ))->toArray();
            if ($randScore) {
                $arr['randScore'] = $randScore;
            }
            $arrJson = json_encode($arr, JSON_UNESCAPED_UNICODE);
            exit($arrJson);
        } elseif ($c == 'upload') {
            if (ENV != 'dev') return $this->outputError(-1, '只有测试环境可以上传');
            if (!$this->request->hasFiles()) return $this->outputError(-1, '没有文件');
            $files = $this->request->getUploadedFiles();
            $file = $files[0];
            $extension = array('config');
            $ext = $file->getExtension();
            if (!in_array($ext, $extension)) return $this->outputError(-1, '上传格式不正确');
            $file_name = $file->getTempName();
            if ($file_name == '') return $this->outputError(-1, '没有文件');
            $file = CACHE_DIR . '/active_rank.config';
            move_uploaded_file($file_name, $file);
            $file_content = file_get_contents($file);
            $output = array(
                'url'  => $file_content,
                'name' => $file_name,
            );
            return $this->outputSuccess($output);
        } elseif ($c == 'import') {
            $config = $this->request->getPost('config', 'trim', '0');
            if (!$config) {
                return $this->outputError(-1, '缺少配置');
            }
            $config_array = json_decode($config, true);
            $temConfig = $config_array['temp_config'] ? $config_array['temp_config'] : '';
            if (!$temConfig) {
                return $this->outputError(-1, '缺少配置');
            }
            $button_tag = $config_array['button_tag'] ? $config_array['button_tag'] : '';
            $button_list = $config_array['button_list'] ? $config_array['button_list'] : '';
            $userList = $config_array['userList'] ? $config_array['userList'] : '';
            $randAward = $config_array['randAward'] ? $config_array['randAward'] : '';
            $randScore = $config_array['randScore'] ? $config_array['randScore'] : '';

            $conn = Di::getDefault()->getShared(BaseModel::SCHEMA);
            $conn->begin();
            try {
                //复制活动
                $activeAdd = BbcTemplateConfig::useMaster();
                foreach ($temConfig as $field => $field_v) {
                    if ($field == 'id') {
                        continue;
                    }
                    if ($field == 'status') {
                        $field_v = 0;
                    }
                    if ($field == 'admin_id') {
                        $field_v = $admin;
                    }
                    if ($field == 'dateline' || $field == 'updated_at') {
                        $field_v = time();
                    }
                    $activeAdd->{$field} = $field_v;
                }

                $activeAdd->save();
                //新增活动id
                $activeId = $activeAdd->id;
                //复制buttonTag buttonList
                if ($button_tag) {
                    foreach ($button_tag as $item) {
                        //复制button_tag
                        $tmpRowsTag = array(
                            'act_id'              => $activeId,
                            'button_tag_type'     => $item['button_tag_type'],
                            'button_content'      => $item['button_content'],
                            'rank_object'         => $item['rank_object'],
                            'rank_object_content' => $item['rank_object_content'],
                            'admin_id'            => $admin,
                            'dateline'            => time(),
                        );
                        $buttonTagAdd = BbcRankButtonTag::useMaster();
                        foreach ($tmpRowsTag as $k => $v) {
                            $buttonTagAdd->{$k} = $v;
                        }
                        $buttonTagAdd->save();
                        $buttonTagId = $buttonTagAdd->id;
                        if ($button_list) {
                            foreach ($button_list as $value) {
                                //复制button_list
                                $tmpRowsList = array(
                                    'act_id'         => $activeId,
                                    'button_tag_id'  => $buttonTagId,
                                    'button_content' => $value['button_content'],
                                    'button_desc'    => $value['button_desc'],
                                    'rank_tag'       => $value['rank_tag'],
                                    'is_upgrade'     => $value['is_upgrade'],
                                    'start_time'     => $value['start_time'],
                                    'end_time'       => $value['end_time'],
                                    'upgrade_num'    => $value['upgrade_num'],
                                    'is_award'       => $value['is_award'],
                                    'award_time'     => $value['award_time'],
                                    'rank_list_num'  => $value['rank_list_num'],
                                    'is_open'        => $value['is_open'],
                                    'level'          => $value['level'],
                                    'p_level'        => $value['p_level'],
                                    'admin_id'       => $admin,
                                    'dateline'       => time(),
                                );
                                $buttonListAdd = BbcRankButtonList::useMaster();
                                foreach ($tmpRowsList as $k => $v) {
                                    $buttonListAdd->{$k} = $v;
                                }
                                $buttonListAdd->save();
                                $buttonListId = $buttonListAdd->id;
                                if ($randAward) {
                                    //复制奖品
                                    foreach ($randAward as $award) {
                                        $tmpRowsAward = array(
                                            'act_id'            => $activeId,
                                            'button_list_id'    => $buttonListId,
                                            'rank'              => $award['rank'],
                                            'award_type'        => $award['award_type'],
                                            'num'               => $award['num'],
                                            'cid'               => $award['cid'],
                                            'award_extend_info' => $award['award_extend_info'],
                                            'admin_id'          => $admin,
                                            'dateline'          => time(),
                                        );
                                        BbcRankAward::addRow($tmpRowsAward);
                                    }
                                }
                                if ($randScore) {
                                    //复制奖品
                                    foreach ($randScore as $score) {
                                        $tmpRowsScore = array(
                                            'act_id'         => $activeId,
                                            'button_list_id' => $buttonListId,
                                            'score'          => $score['score'],
                                            'type'           => $score['type'],
                                            'gift_id'        => $score['gift_id'],
                                            'num'            => $score['num'],
                                            'admin_id'       => $admin,
                                            'dateline'       => time(),
                                        );
                                        BbcRankScoreConfig::addRow($tmpRowsScore);
                                    }
                                }
                            }
                        }
                        if ($userList) {
                            foreach ($userList as $user) {
                                $tmpRowsuser = array(
                                    'act_id'        => $activeId,
                                    'button_tag_id' => $buttonTagId,
                                    'uid'           => $user['uid'],
                                    'extend_id'     => $user['extend_id'],
                                    'admin_id'      => $admin,
                                    'dateline'      => time(),
                                );
                                $RankWhiteListAdd = BbcRankWhiteList::useMaster();
                                foreach ($tmpRowsuser as $k => $v) {
                                    $RankWhiteListAdd->{$k} = $v;
                                }
                                $RankWhiteListAdd->save();
                            }
                        }
                    }
                }
                //更新活动链接
                $this->service->updatePageUrl($activeId, $activeAdd->status);
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess();
        } elseif ($c == 'getButtonTag') {
            $id = $this->request->getQuery('id', 'int', 0);
            $temConfig = BbcTemplateConfig::findOne($id);

            if (empty($temConfig) || $temConfig['type'] != 'gift_rank' || $temConfig['vision_type'] != 2) {
                return $this->outputError(-1, '活动配置类型必须为礼物维度的榜单，且活动视觉为“定制礼物活动视觉');
            }
            $tag = BbcRankButtonTag::findOneByWhere([['act_id', '=', $temConfig['id']], ['button_tag_type', '=', 'left']]);
            if (empty($tag)) {
                return $this->outputError(-1, 'tag不存在');
            }
            return $this->outputSuccess($tag);
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point 活动button_tag修改
     */
    public function buttonTagAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $service = new ButtonTagService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );

        if ($c == "modify") {
            ButtonTagUpdateValidation::make()->validators($params);
            [$res, $msg] = $service->edit($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == "add") {
            ButtonTagCreateValidation::make()->validators($params);
            [$res, $msg] = $service->add($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == "info") {
            $id = $this->request->get("id", 'int', 0);
            $info = $service->info($id);
            return $this->outputSuccess($info);
        } elseif ($c == 'list') {
            $res = $service->getList($params, $this->params['page'], $this->params['limit']);
            return $this->outputSuccess($res['data'] ?? [], array('total' => $res['total'] ?? 0));
        } elseif ($c == "del") {
            $id = $this->request->get("id", 'int', 0);
            $res = array();
            try {
                $data = BbcRankButtonTag::findFirst($id);
                if ($data) {
                    $buttonList = BbcRankButtonList::find(array(
                        "conditions" => "button_tag_id = :button_tag_id:",
                        "bind"       => [
                            'button_tag_id' => $data->id,
                        ]
                    ))->toArray();
                    if ($buttonList) {
                        foreach ($buttonList as $item) {
                            $rankAward = BbcRankAward::find(array(
                                "conditions" => "button_list_id = :button_list_id:",
                                "bind"       => [
                                    'button_list_id' => $item['id'],
                                ]
                            ));
                            if ($rankAward) {
                                $rankAward->delete();
                            }
                        }
                        $buttonListids = implode(',', array_column($buttonList, 'id'));
                        Helper::exec("DELETE FROM bbc_rank_button_list WHERE id IN ($buttonListids)", BaseModel::SCHEMA);
                    }
                    $userList = BbcRankWhiteList::find(array(
                        "conditions" => "button_tag_id = :button_tag_id:",
                        "bind"       => [
                            'button_tag_id' => $data->id,
                        ]
                    ));
                    if ($userList) {
                        $userList->delete();
                    }
                    $data->delete();
                }
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess($res);
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point 奖励列表
     */
    public function awardListAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $server = new AwardListService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );
        $filter = [
            'cycle'       => intval($this->params['cycle'] ?? array_get($params, 'cycle', 0)),
            'object_id'   => intval($this->params['object_id'] ?? array_get($params, 'object_id', 0)),
            'list_id_str' => intval($this->params['list_id_str'] ?? array_get($params, 'list_id_str', 0))
        ];

        switch ($c) {
            case 'list':
                list($flg, $data) = $server->getListAndTotal(array_merge($params, $filter));
                return $flg ? $this->outputSuccess($data['data'], array('total' => $data['total'])) : $this->outputError(-1, $data);
            case 'info':
                $data = $server->getInfo($params);
                return $this->outputSuccess($data['data'] ?? [], array('total' => $data['total'] ?? 0));
            case 'modify':
                list($flg, $rec) = $server->modify($params);
                return $flg ? $this->outputSuccess() : $this->outputError(-1, $rec);
            case 'status':
                list($flg, $rec) = $server->getSubmitStatus($params);
                return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
            case 'export':
                if (empty($params['act_id']) || empty($params['cycle']) || empty($params['list_id_str'])) {
                    return $this->outputError(-1, '活动id、周期和榜单id不能为空');
                }
                ExportService::addTask($this->uid, 'activityAwardList.xlsx', [ActivityAwardListExport::class, 'export'], $this->params, '活动奖励列表');
                ExportService::showHtml();
                return $this->outputSuccess();
            case 'pub':
                list($flg, $rec) = $server->pubList($params);
                return $flg ? $this->outputSuccess() : $this->outputError(-1, $rec);
            case 'send':
                list($flg, $rec) = $server->send($params);
                return $flg ? $this->outputSuccess() : $this->outputError(-1, $rec);
            case 'getButtonList':
                return $this->outputSuccess($server->getButtonList(array_merge($params, $filter)));
        }
        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point 黑名单列表
     */
    public function blackListAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $server = new BlackListService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );
        $filter = ['uid' => intval($this->params['uid'] ?? 0)];

        switch ($c) {
            case 'list':
                list($flg, $data) = $server->getListAndTotal(array_merge($params, $filter));
                return $flg ? $this->outputSuccess($data['data'], array('total' => $data['total'])) : $this->outputError(-1, $data);
            case 'add':
                list($flg, $rec) = $server->add($params);
                return $flg ? $this->outputSuccess() : $this->outputError(-1, $rec);
            case 'upload':
                // 批量上传导入
                if (!$this->request->hasFiles()) return $this->outputError(-1, '没有文件');

                @setlocale(LC_ALL, 'zh_CN');
                $file = $this->request->getUploadedFiles()[0] ?? '';

                if (!in_array($file->getExtension(), ['csv'])) return $this->outputError(-1, '上传格式不正确');

                $filename = $file->getName();
                $file_name = $file->getTempName();
                if ($file_name == '') return $this->outputError(-1, '没有文件');
                $handle = fopen($file_name, 'r');
                if ($handle === FALSE) return $this->outputError(-1, '打开文件失败');
                $csv_arr = array();
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if ($data && isset($data[0])) {
                        if (!is_numeric($data[0])) continue;
                        if (empty($data[0])) continue;
                        $csv_arr[] = $data[0];
                    }
                }
                fclose($handle);

                sort($csv_arr);

                $output = array(
                    'url'  => $filename,
                    'name' => $filename,
                    'data' => $csv_arr,
                );

                return $this->outputSuccess($output);
            case 'del':
                list($flg, $rec) = $server->del($params);
                return $flg ? $this->outputSuccess($rec) : $this->outputError(-1, $rec);
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point buttonGiftTag
     */
    public function buttonGiftTagAction()
    {
        $c = $this->request->getQuery('c', 'trim', '');
        $service = new ButtonGiftTagService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );
        if ($c == 'list') {
            $list = $service->getList($params, $params['page'] ?? 1, $params['limit'] ?? 15, 'id desc');
            return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
        } elseif ($c == 'add') {
            if ($params['vision_type'] == BbcTemplateConfig::VISION_TYPE_THREE) {
                ButtonGiftTagWeekStarCreateValidation::make()->validators($params);
            } else {
                ButtonGiftTagCreateValidation::make()->validators($params);
            }
            [$res, $msg] = $service->add($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'modify') {
            if ($params['vision_type'] == 3) {
                ButtonGiftTagWeekStarUpdateValidation::make()->validators($params);
            } else {
                ButtonGiftTagUpdateValidation::make()->validators($params);
            }
            [$res, $msg] = $service->edit($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'info') {
            $data = $service->info($params['id']);
            return $this->outputSuccess($data);
        } elseif ($c == 'del') {
            [$res, $msg] = $service->delete($params['id']);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point buttonGiftList
     */
    public function buttonGiftListAction()
    {
        $c = $this->request->getQuery('c', 'trim', '');
        $service = new ButtonGiftListService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );
        if ($c == 'list') {
            $list = $service->getList($params, $params['page'] ?? 1, $params['limit'] ?? 15, 'id desc');
            return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
        } elseif ($c == 'add') {
            if ($params['vision_type'] == 3) {
                ButtonGiftListWeekStarCreateValidation::make()->validators($params);
            } else {
                ButtonGiftListCreateValidation::make()->validators($params);
            }
            [$res, $msg] = $service->add($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'modify') {
            if ($params['vision_type'] == 3) {
                ButtonGiftListWeekStarUpdateValidation::make()->validators($params);
            } else {
                ButtonGiftListUpdateValidation::make()->validators($params);
            }
            [$res, $msg] = $service->edit($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'info') {
            $data = $service->info($params['id']);
            return $this->outputSuccess($data);
        } elseif ($c == 'del') {
            $data = $service->delete($params['id']);
            if (!$data) {
                return $this->outputError(-1, '删除失败');
            }
            return $this->outputSuccess();
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point scoreSetList
     */
    public function scoreSetListAction()
    {
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );
        $list = (new ScoreSetService())->getList($params);
        return $this->outputSuccess($list['data'] ?? [], ['total' => $list['total'] ?? 0]);
    }

    /**
     * @page activity
     * @point scoreSetModify
     */
    public function scoreSetModifyAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $server = new ScoreSetService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );

        $res = true;

        switch ($c) {
            case 'add':
                list($res, $msg, $_) = $server->create($params);
                break;
            case 'modify':
                list($res, $msg) = $server->modify($params);
                break;
            case 'batch_del':
                list($res, $msg, $_) = $server->deleteBatch($params);
                break;
            case 'info':
                list($res, $msg) = $server->info($params['id']);
                break;
        }

        if (!$res) {
            return $this->outputError(-1, $msg ?? '');
        }

        $data = [];
        if (!empty($msg)) {
            $data = $msg;
        }

        return $this->outputSuccess($data);
    }

    /**
     * @page activity
     * @point buttonList修改
     */
    public function buttonListAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $service = new ButtonListService();
        $params = array_merge(
            $this->request->getQuery(),
            $this->request->getPost(),
            ['admin_id' => $this->uid]
        );

        if ($c == "modify") {
            ButtonListUpdateValidation::make()->validators($params);
            [$res, $msg] = $service->edit($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == "add") {
            ButtonListCreateValidation::make()->validators($params);
            [$res, $msg] = $service->add($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == "info") {
            $id = $params['id'] ?? 0;
            $res = $service->info($id);
            return $this->outputSuccess($res);
        } elseif ($c == 'list') {
            $res = $service->getList($params, $this->params['page'], $this->params['limit']);
            return $this->outputSuccess($res['data'] ?? [], array('total' => $res['total'] ?? 0));
        } elseif ($c == "del") {
            $id = $this->request->get("id", 'int', 0);
            $res = array();
            try {
                $data = BbcRankButtonList::findFirst($id);
                if ($data) {
                    $rankAward = BbcRankAward::find(array(
                        "conditions" => "button_list_id = :button_list_id:",
                        "bind"       => [
                            'button_list_id' => $data->id,
                        ]
                    ));
                    if ($rankAward) {
                        $rankAward->delete();
                    }
                    $data->delete();
                }
                // 更新主榜信息
                $config = BbcTemplateConfig::findOne($data->act_id);
                ButtonListService::updateMasterList(['act_id' => $data->act_id, 'vision_type' => $config['vision_type']]);
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess($res);
        } elseif ($c == "scoreSet") {
            $tp = $this->request->getQuery("tp", "trim", "");
            if ($tp == 'add') {
                $button_list_id = $this->request->getPost("button_list_id", "int", "0");
                $type = $this->request->getPost("type", "int", 0);
                $score = $this->request->getPost("score", "trim", '');
                $gift_id = $this->request->getPost("gift_id", "trim", '');
                if (!$button_list_id) {
                    return $this->outputError(-1, '必须指定活动button_list');
                }
                $score = round($score, 2);
                if (!$type || !$score) {
                    return $this->outputError(-1, '缺少参数');
                }
                if (in_array($type, [2, 3, 5, 6]) && !$gift_id) {
                    return $this->outputError(-1, '必须填写礼物id');
                }
                if ($gift_id) {
                    $arr = $this->service->isGift($gift_id, 1);
                    if ($arr == 1) {
                        return $this->outputError(-1, "礼物id不存在或者无效");
                    } elseif ($arr == 2) {
                        return $this->outputError(-1, "礼物id不是付费礼物");
                    }
                }
                try {
                    $button_info = BbcRankButtonList::findFirst($button_list_id);
                    if (!$button_info || !$button_info->act_id || !$button_info->button_tag_id) {
                        return $this->outputError(-1, '活动button_list信息有误');
                    }
                    if (!in_array($type, [2, 3, 5, 6])) {
                        $ScoreInfo = BbcRankScoreConfig::findFirst(array(
                            "conditions" => "type = :type: and button_list_id = :button_list_id:",
                            "bind"       => [
                                'type'           => $type,
                                'button_list_id' => $button_list_id,
                            ]
                        ));
                        if ($ScoreInfo) {
                            return $this->outputError(-1, "button_list_id:{$button_list_id} 已经设置过此类型的积分配置了");
                        }
                        $gift_id = 0;
                    }
                    $tmpRows = array(
                        'button_list_id' => $button_list_id,
                        'act_id'         => $button_info->act_id,
                        'score'          => $score,
                        'type'           => $type,
                        'gift_id'        => $gift_id,
                        'admin_id'       => Helper::getSystemUid(),
                        'dateline'       => time(),
                    );
                    BbcRankScoreConfig::addRow($tmpRows);
                } catch (\Exception $e) {
                    return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
                }
                return $this->outputSuccess();
            } elseif ($tp == 'list') {
                $button_list_id = $this->request->getQuery("button_list_id", "trim", "");
                $query = array(
                    'order'  => 'id desc',
                    'limit'  => $this->params['limit'],
                    'offset' => max($this->params['page'] - 1, 0) * $this->params['limit'],
                );
                $conditions = array();
                $condsArr = array();
                if (!$button_list_id) {
                    return $this->outputError(-1, 'button_list不存在');
                }
                $button_list_info = BbcRankButtonList::findFirst($button_list_id);
                if (!$button_list_info) {
                    return $this->outputError(-1, 'button_list不存在');
                }
                $condsArr[] = "button_list_id={$button_list_id}";
                $condsArr[] = "act_id={$button_list_info->act_id}";

                if (!empty($condsArr)) {
                    $conditions = array(
                        'conditions' => implode(" and ", $condsArr),
                    );
                }
                try {
                    $total = BbcRankScoreConfig::count($conditions);
                    $res = BbcRankScoreConfig::find(array_merge($query, $conditions))->toArray();
                    foreach ($res as &$val) {
                        $val['dateline'] = $val['dateline'] > 0 ? date('Y-m-d H:i', $val['dateline']) : ' - ';
                        $tmpA = CmsUser::findFirst(intval($val['admin_id']));
                        $val['admin'] = $tmpA ? $tmpA->user_name : ' - ';
                    }
                } catch (\Exception $e) {
                    return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
                }
                return $this->outputSuccess($res, array('total' => $total));
            } elseif ($tp == 'batch_del') {
                $ids = $this->request->get("ids", 'trim', '');
                if (!$ids) return $this->outputError(-1, '数据出现问题');
                $ids_array = explode(',', $ids);
                if (!$ids_array || count($ids_array) < 1) {
                    return $this->outputError(-1, '数据出现问题');
                }
                foreach ($ids_array as $id) {
                    $rec = BbcRankScoreConfig::findFirst($id);
                    if (!$rec) return $this->outputError(-1, '当前数据不存在，请确认');
                    $rec->delete();
                }
                return $this->outputSuccess();
            } elseif ($tp == "info") {
                $id = $this->request->get("id", 'int', 0);
                $res = array();
                $data = BbcRankScoreConfig::findFirst($id);
                if ($data) {
                    $res = $data->toArray();
                    $res['type'] = "{$res['type']}";
                }
                return $this->outputSuccess($res);
            } elseif ($tp == "modify") {
                $id = $this->request->getPost('id', 'int', 0);
                $type = $this->request->getPost("type", "int", 0);
                $score = $this->request->getPost("score", "trim", '');
                $gift_id = $this->request->getPost("gift_id", "int", 0);
                if ($id < 1) return $this->outputError(-1, '数据出现问题');
                $rec = BbcRankScoreConfig::findFirst($id);
                if (!$rec) return $this->outputError(-1, '当前数据不存在，请确认');
                $score = round($score, 2);
                if (!$type || !$score) {
                    return $this->outputError(-1, '缺少参数');
                }
                if (in_array($type, [2, 3, 5, 6]) && !$gift_id) {
                    return $this->outputError(-1, '必须填写礼物id');
                }
                if ($gift_id) {
                    $arr = $this->service->isGift($gift_id, 1);
                    if ($arr == 1) {
                        return $this->outputError(-1, "礼物id不存在或者无效");
                    } elseif ($arr == 2) {
                        return $this->outputError(-1, "礼物id不是付费礼物");
                    }
                }
                try {
                    if (!in_array($type, [2, 3, 5, 6])) {
                        $ScoreInfo = BbcRankScoreConfig::findFirst(array(
                            "conditions" => "type = :type: and button_list_id = :button_list_id: and id != :id:",
                            "bind"       => [
                                'type'           => $type,
                                'button_list_id' => $rec->button_list_id,
                                'id'             => $id,
                            ]
                        ));
                        if ($ScoreInfo) {
                            return $this->outputError(-1, "button_list_id:{$rec->button_list_id} 已经设置过此类型的积分配置了");
                        }
                        $gift_id = 0;
                    }
                    $hasUpdate = false;
                    if ($type != $rec->type) {
                        $rec->type = $type;
                        $hasUpdate = true;
                    }

                    if ($score != $rec->score) {
                        $rec->score = $score;
                        $hasUpdate = true;
                    }

                    if ($gift_id != $rec->gift_id) {
                        $rec->gift_id = $gift_id;
                        $hasUpdate = true;
                    }


                    if ($hasUpdate) {
                        $rec->save();
                    }
                } catch (\Exception $e) {
                    return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
                }
                return $this->outputSuccess();
            }
        }

        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point 活动奖励修改
     */
    public function rankAwardAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        $admin = intval($this->uid);
        if ($c == "add") {
            $button_list_id = $this->request->getPost("button_list_id", "int", "0");
            $rankAwardType = $this->request->getPost("rank_award_type", "int", "0");
            $rank_start = $this->request->getPost('rank_start', 'trim', '0');
            $rank_end = $this->request->getPost('rank_end', 'trim', '0');
            $scoreType = $this->request->getPost('score_type', 'int', '0');
            $scoreMin = $this->request->getPost('score_min', 'trim', '0');
            $scoreMax = $this->request->getPost('score_max', 'trim', '0');
            $extendRankMin = $this->request->getPost('extend_rank_min');
            $extendRankMax = $this->request->getPost('extend_rank_max');
            $awardObjectType = intval($this->request->getPost('award_object_type', 'int', 0));
            $awardObject = intval($this->request->getPost('award_object', 'int', 0));
            $awardTimes = $this->request->getPost('award_times', 'int', 4);//最多允许的奖励个数

            if (!$button_list_id) {
                return $this->outputError(-1, '必须指定活动button_list');
            }
            if ($rank_start > $rank_end && BbcRankAward::RANK_AWARD_TYPE_SCORE != $rankAwardType) {
                return $this->outputError(-1, '名次上限下限区间错误');
            }
            if ($awardObjectType == BbcRankAward::AWARD_OBJECT_TYPE_MAIN) {
                $extendRankMin = $extendRankMax = 0;
            } elseif (BbcRankAward::isExtendRank($awardObjectType)) {
                if (!preg_match("/^\d+$/", $extendRankMin) || !preg_match("/^\d+$/", $extendRankMax)) {
                    return $this->outputError(-1, '成员名次上限下限必须为正整数');
                }
                if ($extendRankMin < 1 || $extendRankMax < 1 || $extendRankMin < $extendRankMax) {
                    return $this->outputError(-1, '成员名次上限下限区间错误');
                }
            }

            // 累胜玩法设置默认值
            if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS) {
                $scoreType = 2;
            }

            // 门槛要求处于 >= 时 score_max 默认为 4294967295
            if ($scoreType == 2) {
                $scoreMax = BbcRankAward::SCORE_MAX_NEW;
            }
            if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_SCORE || $rankAwardType == BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS) {
                $rank_start = $rank_end = 0;
                if (!preg_match("/^\d+$/", $scoreMin) || !preg_match("/^\d+$/", $scoreMax)) {
                    return $this->outputError(-1, '门槛上限下限必须为正整数');
                }
                if ($scoreMin > $scoreMax) {
                    return $this->outputError(-1, '门槛上下限区间错误');
                }
                [$res, $msg] = (new AwardService())->validAwardScore($button_list_id, $rank_start, $awardObjectType, $rankAwardType, $scoreMin, $scoreMax, true);
                if (!$res) {
                    return $this->outputError(-1, $msg);
                }
            } elseif ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE) {
                if (!preg_match("/^\d+$/", $rank_start) || !preg_match("/^\d+$/", $rank_end)) {
                    return $this->outputError(-1, '名次上限下限必须为正整数');
                }
                if (!preg_match("/^\d+$/", $scoreMin) || !preg_match("/^\d+$/", $scoreMax)) {
                    return $this->outputError(-1, '门槛上限下限必须为正整数');
                }
                if ($scoreMin > $scoreMax) {
                    return $this->outputError(-1, '门槛上下限区间错误');
                }
                for ($i = $rank_start; $i <= $rank_end; $i++) {
                    [$res, $msg] = (new AwardService())->validAwardScore($button_list_id, $i, $awardObjectType, BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE, $scoreMin, $scoreMax, true);
                    if (!$res) {
                        return $this->outputError(-1, $msg);
                    }
                }
            } elseif ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_RANK) {
                if (!preg_match("/^\d+$/", $rank_start) || !preg_match("/^\d+$/", $rank_end)) {
                    return $this->outputError(-1, '名次上限下限必须为正整数');
                }
                $scoreMin = $scoreMax = 0;
            }

            try {
                $button_info = BbcRankButtonList::findFirst($button_list_id);
                if (!$button_info || !$button_info->act_id || !$button_info->button_tag_id) {
                    return $this->outputError(-1, '活动button_list信息有误');
                }

                $awardTypeFlg = true;
                for ($i = 1; $i <= $awardTimes; $i++) {
                    $awardType = $this->request->getPost("award_type" . $i, "int", 0);
                    $awardTypeFlg = $awardTypeFlg && empty($awardType);
                }
                if ($awardTypeFlg) {
                    return $this->outputError(-1, '至少一个奖励');
                }

                $addAward = [];
                $isDiamondType = 0;
                $awardService = new AwardService();
                for ($i = 1; $i <= $awardTimes; $i++) {
                    $awardType = $this->request->getPost("award_type" . $i, "int", 0);
                    $cid = $this->request->getPost("cid" . $i, 'trim', '');
                    $num = $this->request->getPost("num" . $i, "trim", 0);
                    $diamondProportion = $this->request->getPost("diamond_proportion" . $i, "trim", 0);
                    $day = $this->request->getPost("day" . $i, "int", 0);
                    $vip = $this->request->getPost("vip" . $i, "int", 0);
                    $expDays = $this->request->getPost('exp_days' . $i, 'int', 0);
                    $canTransfer = $this->request->getPost('can_transfer' . $i, 'int', 0);
                    $content = $this->request->getPost('content' . $i, 'trim', '');
                    $giveType = $this->request->getPost('give_type' . $i, 'int', 0);
                    $daySelect = $this->request->getPost("day_select" . $i, "int", 0);
                    $icon = $this->request->getPost("icon" . $i, "trim", '');
                    $expTime = $this->request->getPost('exp_time' . $i, 'trim', '');
                    $hours = $this->request->getPost('hours' . $i, 'trim', 0);
                    $cardType = $this->request->getPost('card_type' . $i, 'int', -1);
                    $validDay = $this->request->getPost('valid_day' . $i, 'int', 0);

                    if (!empty($num) && !preg_match('/^[1-9]\d*$/', $num)) {
                        return $this->outputError(-1, '数量必须为正整数');
                    }

                    if (!empty($hours) && !preg_match('/^[1-9]\d*$/', $hours)) {
                        return $this->outputError(-1, '有效小时必须为正整数');
                    }

                    if (!empty($expDays) && !preg_match('/^[1-9]\d*$/', $expDays)) {
                        return $this->outputError(-1, '资格使用天数必须为正整数');
                    }

                    if (!empty($day) && !preg_match('/^[1-9]\d*$/', $day)) {
                        return $this->outputError(-1, '天数少必须为正整数');
                    }

                    if ($awardType == BbcRankAward::AWARD_TYPE_PRIZE_POOL) {
                        if ($rankAwardType == BbcRankAward::RANK_AWARD_TYPE_SCORE || $rankAwardType == BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS) {
                            return $this->outputError(-1, '发放条件为门槛时，不支持配置奖池类型奖励');
                        }
                    }

                    if ($awardType == BbcRankAward::AWARD_TYPE_DIAMOND) {
                        if (!empty($diamondProportion) && !empty($num)) {
                            return $this->outputError(-1, '返钻比例和数量只允许填写其中一个');
                        }
                        if (!$isDiamondType && $diamondProportion) {
                            $isDiamondType = 1;
                        }
                        if (!$isDiamondType && $num) {
                            $isDiamondType = 2;
                        }
                        if ($isDiamondType && (($isDiamondType == 1 && $num) || ($isDiamondType == 2 && $diamondProportion))) {
                            return $this->outputError(-1, '同一发放对象不能同时配置返钻比例和固定钻石数目奖励');
                        }
                    }
                    if ($awardType == BbcRankAward::AWARD_TYPE_GAME_COUPON) {
                        $expDays = $daySelect;
                    }

                    $params = [
                        'award_type'         => $awardType,
                        'cid'                => $cid,
                        'vip'                => $vip,
                        'num'                => $num,
                        'diamond_proportion' => $diamondProportion,
                        'day'                => $day,
                        'exp_days'           => $expDays,
                        'can_transfer'       => $canTransfer,
                        'content'            => $content,
                        'give_type'          => $giveType,
                        'icon'               => $icon,
                        'exp_time'           => $expTime,
                        'hours'              => $hours,
                        'card_type'          => $cardType,
                        'valid_day'          => $validDay,
                        'i'                  => $i
                    ];
                    if ($awardType) {
                        list($res, $cidArr) = $awardService->validCid($cid, $awardType, $i);
                        if (!$res) {
                            return $this->outputError(-1, $cidArr);
                        }
                        if ($cidArr) {
                            foreach ($cidArr as $id) {
                                $params['cid'] = $id;
                                list($res, $msg, $data) = $awardService->validateAwardConfig($params);
                                if (!$res) {
                                    return $this->outputError(-1, $msg);
                                }
                                $addAward[] = $data;
                            }
                        } else {
                            list($res, $msg, $data) = $awardService->validateAwardConfig($params);
                            if (!$res) {
                                return $this->outputError(-1, $msg);
                            }
                            $addAward[] = $data;
                        }
                    }
                }

                if (empty($addAward)) {
                    return $this->outputError(-1, '奖励信息不正确');
                }
                $rank = $rank_end - $rank_start;
                $time = time();

                if ($rank > 0) {
                    for ($i = $rank_start; $i <= $rank_end; $i++) {
                        foreach ($addAward as $item) {
                            if ($item['award_type'] == BbcRankAward::AWARD_TYPE_DIAMOND) {
                                $info = BbcRankAward::findOneByWhere([
                                    ['act_id', '=', $button_info->act_id],
                                    ['button_list_id', '=', $button_list_id],
                                    ['rank', '=', $i],
                                    ['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND],
                                    ['cid', '=', $item['cid'] ?? 0],
                                    ['score_min', '=', $scoreMin],
                                    ['score_max', '=', $scoreMax],
                                    ['award_object_type', '=', (int)$awardObjectType],
                                    ['extend_rank_min', '=', (int)$extendRankMin],
                                    ['extend_rank_max', '=', (int)$extendRankMax],
                                ]);

                                if (!empty($info) && (($item['diamond_proportion'] && $info['num']) || ($item['num'] && $info['diamond_proportion']))) {
                                    return $this->outputError(-1, '同一发放对象不能同时配置返钻比例和固定钻石数目奖励');
                                }
                            }
                        }
                    }
                } else {
                    foreach ($addAward as $item) {
                        if ($item['award_type'] == BbcRankAward::AWARD_TYPE_DIAMOND) {
                            $info = BbcRankAward::findOneByWhere([
                                ['act_id', '=', $button_info->act_id],
                                ['button_list_id', '=', $button_list_id],
                                ['rank', '=', $rank_start],
                                ['award_type', '=', BbcRankAward::AWARD_TYPE_DIAMOND],
                                ['cid', '=', $item['cid'] ?? 0],
                                ['score_min', '=', $scoreMin],
                                ['score_max', '=', $scoreMax],
                                ['award_object_type', '=', (int)$awardObjectType],
                                ['extend_rank_min', '=', (int)$extendRankMin],
                                ['extend_rank_max', '=', (int)$extendRankMax],
                            ]);
                            if (!empty($info) && (($item['diamond_proportion'] && $info['num']) || ($item['num'] && $info['diamond_proportion']))) {
                                return $this->outputError(-1, '同一发放对象不能同时配置返钻比例和固定钻石数目奖励');
                            }
                        }
                    }
                }

                if ($rank > 0) {
                    for ($i = $rank_start; $i <= $rank_end; $i++) {
                        foreach ($addAward as $item) {
                            $tmpRows = array(
                                'button_list_id'     => $button_list_id,
                                'rank_award_type'    => $rankAwardType,
                                'act_id'             => $button_info->act_id,
                                'rank'               => $i,
                                'score_min'          => $scoreMin,
                                'score_max'          => $scoreMax,
                                'award_type'         => $item['award_type'],
                                'num'                => $item['num'],
                                'diamond_proportion' => $item['diamond_proportion'] ?? 0,
                                'cid'                => $item['cid'] ?? 0,
                                'admin_id'           => $admin,
                                'dateline'           => $time,
                                'exp_days'           => $item['exp_days'] ?? 0,
                                'award_object_type'  => (int)$awardObjectType,
                                'extend_rank_max'    => (int)$extendRankMax,
                                'extend_rank_min'    => (int)$extendRankMin,
                                'can_transfer'       => intval($item['can_transfer'] ?? 0),
                                'award_extend_info'  => $item['award_extend_info'] ?? '',
                                'award_object'       => $awardObject
                            );
                            (new AwardService())->addData($tmpRows);
                        }
                    }
                } else {
                    foreach ($addAward as $item) {
                        $tmpRows = array(
                            'button_list_id'     => $button_list_id,
                            'rank_award_type'    => $rankAwardType,
                            'act_id'             => $button_info->act_id,
                            'rank'               => $rank_start,
                            'award_type'         => $item['award_type'],
                            'score_min'          => $scoreMin,
                            'score_max'          => $scoreMax,
                            'num'                => $item['num'],
                            'diamond_proportion' => $item['diamond_proportion'] ?? 0,
                            'cid'                => $item['cid'] ?? 0,
                            'admin_id'           => $admin,
                            'dateline'           => $time,
                            'exp_days'           => $item['exp_days'] ?? 0,
                            'award_object_type'  => (int)$awardObjectType,
                            'extend_rank_max'    => (int)$extendRankMax,
                            'extend_rank_min'    => (int)$extendRankMin,
                            'can_transfer'       => intval($item['can_transfer'] ?? 0),
                            'award_extend_info'  => $item['award_extend_info'] ?? '',
                            'award_object'       => $awardObject
                        );
                        (new AwardService())->addData($tmpRows);
                    }
                }
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess();
        } elseif ($c == 'list') {
            $button_list_id = $this->request->getQuery("button_list_id", "trim", "");
            $query = array(
                'order'  => 'id desc',
                'limit'  => $this->params['limit'],
                'offset' => max($this->params['page'] - 1, 0) * $this->params['limit'],
            );
            $conditions = array();
            $condsArr = array();
            if (!$button_list_id) {
                return $this->outputError(-1, 'button_list不存在');
            }
            $button_list_info = BbcRankButtonList::findFirst($button_list_id);
            if (!$button_list_info) {
                return $this->outputError(-1, 'button_list不存在');
            }
            $buttonTag = BbcRankButtonTag::findOne($button_list_info->button_tag_id);
            if (!$buttonTag) {
                return $this->outputError(-1, 'button_tag不存在');
            }
            $activity = BbcTemplateConfig::findOne($button_list_info->act_id);
            if (!$activity) {
                return $this->outputError(-1, '活动不存在');
            }
            $condsArr[] = "button_list_id={$button_list_id}";
            $condsArr[] = "act_id={$button_list_info->act_id}";

            if (!empty($condsArr)) {
                $conditions = array(
                    'conditions' => implode(" and ", $condsArr),
                );
            }
            try {
                $total = BbcRankAward::count($conditions);
                $res = BbcRankAward::find(array_merge($query, $conditions))->toArray();
                foreach ($res as &$val) {
                    $extend = @json_decode($val['award_extend_info'], true);
                    if ($val['award_type'] == BbcRankAward::AWARD_TYPE_COMMODITY) {
                        $val['can_transfer'] = '';
                        $commodity = XsCommodity::findOne($val['cid']);
                        $val['matter_name'] = $commodity['name'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($commodity['image'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_MEDAL) {
                        $val['day'] = $val['num'];
                        $val['num'] = '';
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                        $medal = XsMedalResource::findOne($val['cid']);
                        $val['matter_name'] = json_decode($medal['description_zh_tw'], true)['name'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($medal['image_2'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_DIAMOND) {
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BACKGROUND) {
                        $val['day'] = $val['num'];
                        $val['num'] = '';
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                        $background = XsChatroomBackgroundMall::findOneByWhere([['bg_id', '=', $val['cid']]]);
                        $val['matter_name'] = $background['name'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($background['cover'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_BG_CARD) {
                        $val['day'] = $val['exp_days'];
                        $val['exp_days'] = '';
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $extendInfo = $extend['room_bg_card_extend'] ?? [];
                        $val['card_type'] = BbcRankAward::$roomBgCardTypeMap[$extendInfo['card_type'] ?? ''] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_TOP_CARD) {
                        $val['can_transfer'] = '';
                        $card = XsRoomTopCard::findOne($val['cid']);
                        $val['matter_name'] = json_decode($card['name_json'], true)['cn'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($card['icon'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_ROOM_SKIN) {
                        $val['day'] = $val['num'];
                        $val['num'] = '';
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                        $skin = XsRoomSkin::findOne($val['cid']);
                        $val['matter_name'] = $skin['name'] ?? '';
                        $img = $skin['cover'] ?? '';
                        $type = $skin['type'] ?? 0;
                        $type == 1 && $img = $skin['img'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($img ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_CERTIFICATION_ICON) {
                        $val['day'] = $val['num'];
                        $val['num'] = '';
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                        $certification = XsCertificationSign::findOne($val['cid']);
                        $val['matter_name'] = $certification['name'] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($certification['icon'] ?? '');
                        $val['content'] = $extend['content'] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_PRETTY_ID_CARD) {
                        $val['day'] = $val['num'];
                        $val['num'] = $extend['send_num'] ?? '';
                        $pretty = XsCustomizePrettyStyle::findOne($val['cid']);
                        $val['matter_name'] = $pretty['name'] ?? '';
                        $val['can_transfer'] = BbcRankAward::$canTransferPrettuMap[$val['can_transfer']] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_VIP) {
                        $val['day'] = $val['num'];
                        $val['num'] = $extend['send_num'] ?? '';
                        $val['exp_days'] = '';
                        //vip直接显示vip名称
                        $val['matter_name'] = 'VIP' . $val['cid'];
                        $val['can_transfer'] = BbcRankAward::$giveTypeMap[$extend['extend_type'] ?? ''] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_GAME_COUPON) {
                        $val['day'] = '';
                        $val['day_select'] = $val['exp_days'];
                        $val['exp_days'] = '';
                        $val['can_transfer'] = '';
                        $sCoupon = XsCoupon::findOne($val['cid']);
                        $nameJson = array_get($sCoupon, 'name_json', '');
                        $nameJson = $nameJson ? @json_decode($nameJson, true) : [];
                        $val['matter_name'] = array_get($nameJson, 'cn', $nameJson['en'] ?? '');
                        $val['matter_image'] = Helper::getHeadUrl($sCoupon['icon'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZATION) {
                        $val['matter_image'] = Helper::getHeadUrl($extend['icon'] ?? '');
                        $val['matter_name'] = $extend['content'] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_NAME_ID_LIGHTING) {
                        $val['day'] = $val['num'];
                        $val['num'] = $extend['send_num'] ?? '';
                        $nameIdLight = XsNameIdLightingGroup::findOne($val['cid']);
                        $name = XsNameIdLightingGroup::formatName(json_decode($nameIdLight['name'] ?? '', true));
                        $val['matter_name'] = $name;
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $val['matter_image'] = Helper::_getHeadUrl($nameIdLight['icon'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_ITEM_CARD) {
                        $val['day'] = $val['num'];
                        $val['num'] = $extend['send_num'] ?? '';
                        $card = XsItemCard::findOne($val['cid']);
                        $name = @json_decode($card['name_json'], true)['zh_cn'] ?? '';
                        $val['matter_name'] = $name;
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($card['icon'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_PROP_CARD) {
                        $val['can_transfer'] = '';
                        $val['hours'] = $val['exp_days'] ?? '';
                        $val['exp_days'] = '';
                        $propCard = XsPropCard::findOne($val['cid']);
                        if ($propCard) {
                            $propCardConfig = XsPropCardConfig::findOne($propCard['prop_card_config_id'] ?? 0);
                            if ($propCardConfig) {
                                $nameJson = @json_decode($propCardConfig['name_json'], true);
                                $val['matter_name'] = $nameJson['cn'] ?? $nameJson['en'] ?? '';
                                $val['matter_image'] = Helper::getHeadUrl($propCardConfig['icon'] ?? '');
                            }
                        }
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_OPEN_SCREEN_CARD) {
                        $val['hours'] = $extend['days'] ?? '';
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $val['exp_time'] = Helper::now($val['exp_days']);
                        $val['exp_days'] = '';
                        $extendInfo = $extend['open_screen_card_extend'] ?? [];
                        $val['card_type'] = BbcRankAward::$openScreenCardTypeMap[$extendInfo['card_type'] ?? ''] ?? '';
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_HOMEPAGE_CARD) {
                        $val['day'] = $val['num'];
                        $val['num'] = $extend['send_num'] ?? '';
                        $card = XsItemCard::findOne($val['cid']);
                        $name = @json_decode($card['name_json'], true)['zh_cn'] ?? '';
                        $val['matter_name'] = $name;
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $val['matter_image'] = Helper::getHeadUrl($card['icon'] ?? '');
                    } elseif ($val['award_type'] == BbcRankAward::AWARD_TYPE_CUSTOMIZED_EMOTICON_CARD) {
                        $val['valid_day'] = $extend['days'] ?? '';
                        $val['can_transfer'] = BbcRankAward::$canTransferBgcMap[$val['can_transfer']] ?? '';
                        $val['exp_time'] = Helper::now($val['exp_days']);
                        $val['exp_days'] = '';
                    } 
                    if (in_array($val['rank_award_type'], [BbcRankAward::RANK_AWARD_TYPE_SCORE, BbcRankAward::RANK_AWARD_TYPE_RANK_SCORE, BbcRankAward::RANK_AWARD_TYPE_TOTAL_WINS])) {
                        $scoreMax = ($val['score_max'] ?: BbcRankAward::SCORE_MAX);
                        $val['score'] = $scoreMax != BbcRankAward::SCORE_MAX_NEW ? $val['score_min'] . '-' . $scoreMax : '>=' . $val['score_min'];
                        $val['score_type'] = $scoreMax == BbcRankAward::SCORE_MAX_NEW ? '2' : '1';
                    } else {
                        $val['score'] = '';
                    }
                    if (BbcRankAward::isExtendRank($val['award_object_type'])) {
                        if ($val['extend_rank_min'] == $val['extend_rank_max']) {
                            $val['extend_rank'] = $val['extend_rank_max'];
                        } else {
                            // 之间录入数据有问题，取最小值-最大值吧
                            $val['extend_rank'] = min($val['extend_rank_min'], $val['extend_rank_max']) . '-' . max($val['extend_rank_min'], $val['extend_rank_max']);
                        }
                    }
                    if ($activity['vision_type'] == BbcTemplateConfig::VISION_TYPE_CUSTOMIZED) {
                        $val['award_object_type_name'] = BbcRankAward::$awardObjectTypeCustomizationMap[$val['award_object_type']] ?? '';
                    } else {
                        if ($val['award_object_type'] == BbcRankAward::AWARD_OBJECT_TYPE_MAIN) {
                            if ($buttonTag['rank_object'] == BbcRankButtonTag::BROKER_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '公会长';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::ROOM_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '房主';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '家族长';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::ARCHER_RANK_OBJECT) {
                                $val['award_object_type_name'] = '主播';
                            }
                        } else {
                            if ($buttonTag['rank_object'] == BbcRankButtonTag::BROKER_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '公会成员';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::ROOM_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '房间成员';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::FAMILY_WHITE_LIST_TYPE) {
                                $val['award_object_type_name'] = '家族成员';
                            } else if ($buttonTag['rank_object'] == BbcRankButtonTag::ARCHER_RANK_OBJECT) {
                                $val['award_object_type_name'] = '贡献用户';
                            }
                        }
                    }
                    $val['dateline'] = $val['dateline'] > 0 ? date('Y-m-d H:i', $val['dateline']) : ' - ';
                    $tmpA = CmsUser::findFirst(intval($val['admin_id']));
                    $val['admin'] = $tmpA ? $tmpA->user_name : ' - ';
                    $awardTimeStr = '';
                    if (isset($val["award_time"])) {
                        $awardTimeStr = $val["award_time"] > 0 ? date("Y-m-d H:i:s", $val["award_time"]) : '';
                    }
                    $val['award_time'] = $awardTimeStr;
                }
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess($res, array('total' => $total));
        } elseif ($c == 'batch_del') {
            $ids = $this->request->get("ids", 'trim', '');
            if (!$ids) return $this->outputError(-1, '数据出现问题');
            $ids_array = explode(',', $ids);
            if (!$ids_array || count($ids_array) < 1) {
                return $this->outputError(-1, '数据出现问题');
            }
            list($res, $msg) = (new AwardService())->validAwardBatchDelete($ids_array);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            foreach ($ids_array as $id) {
                $rec = BbcRankAward::findFirst($id);
                if (!$rec) return $this->outputError(-1, '当前数据不存在，请确认');
                $rec->delete();
            }
            return $this->outputSuccess();
        } elseif ($c == 'info') {
            $id = $this->request->getQuery('id', 'int', 0);
            if (empty($id)) {
                return $this->outputError(-1, 'ID错误');
            }
            return $this->outputSuccess((new AwardService())->info($id));
        } elseif ($c == 'modify') {
            $params = $this->request->getPost();
            list($res, $msg) = (new AwardService())->modify($params);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'content') {
            return $this->outputSuccess(XsCertificationSign::getContentMap());
        }

        return $this->outputError(-1, 'c is error');
    }


    /**
     * @page activity
     * @point 活动用户白名单
     */
    public function rankUserWhiteListAction()
    {
        $c = $this->request->getQuery("c", "trim", "");
        if ($c == 'list') {
            $button_tag_id = $this->request->getQuery("button_tag_id", "trim", "");
            $uid = intval($this->params['uid'] ?? 0);
            $query = array(
                'order'  => 'id desc',
                'limit'  => $this->params['limit'],
                'offset' => max($this->params['page'] - 1, 0) * $this->params['limit'],
            );
            $condsArr = array();
            $conditions = array();
            try {
                if (!$button_tag_id) {
                    return $this->outputError(-1, 'button_tag_id不能为空');
                }
                $button_tag_info = BbcRankButtonTag::findFirst($button_tag_id);
                if (!$button_tag_info) {
                    return $this->outputError(-1, 'button_tag不存在');
                }

                $type = BbcRankWhiteList::TYPE_USER;
                $condsArr[] = "button_tag_id={$button_tag_id}";
                $condsArr[] = "type={$type}";
                if ($uid > 0) {
                    $condsArr[] = "uid=" . $uid;
                }
                if (!empty($condsArr)) {
                    $conditions = array(
                        'conditions' => implode(" and ", $condsArr),
                    );
                }
                $total = BbcRankWhiteList::count($conditions);
                if (!$total) {
                    return $this->outputSuccess([], array('total' => 0));
                }
                // 根据条件构造查询语句
                $res = BbcRankWhiteList::find(array_merge($query, $conditions));
                $data = $res->toArray();
                foreach ($data as &$v) {
                    $userRes = XsUserProfile::findFirst($v['uid']);
                    $v['uname'] = $userRes->name;
                    $v['dateline'] = date('Y-m-d H:i:s', $v['dateline']);
                    $tmp = CmsUser::findFirst(intval($v['admin_id']));
                    $v['admin'] = $tmp ? $tmp->user_name : ' - ';
                }
            } catch (\Exception $e) {
                return $this->outputError(-1, "请求(" . $e->getMessage() . ")，请重试");
            }
            return $this->outputSuccess($data, array('total' => $total));

        } elseif ($c == 'upload') {
            if (!$this->request->hasFiles()) return $this->outputError(-1, '没有文件');

            $files = $this->request->getUploadedFiles();
            $file = $files[0];

            $extension = array('csv');
            $ext = $file->getExtension();
            if (!in_array($ext, $extension)) return $this->outputError(-1, '上传格式不正确');
            $filename = $file->getName();
            $file_name = $file->getTempName();

            if ($file_name == '') return $this->outputError(-1, '没有文件');

            $handle = fopen($file_name, 'r');
            if ($handle === FALSE) return $this->outputError(-1, '打开文件失败');
            @setlocale(LC_ALL, 'zh_CN');
            $csv_val = array('uid');
            $csv_arr = array();

            while (($data = fgetcsv($handle)) !== false) {
                $tmp_row = array();
                foreach ($csv_val as $k => $v) {
                    $tmp_row[$v] = $data[$k];
                }

                $csv_arr[] = $tmp_row;
            }
            $output = array(
                'url'  => $filename,
                'name' => $filename,
                'data' => $csv_arr,
            );
            return $this->outputSuccess($output);

        } elseif ($c == 'import') {
            $params = $this->request->getPost();
            $button_tag_id = intval($params['button_tag_id'] ?? 0);
            $type = intval($params['type'] ?: 1);
            $csvData = $params['RankUserWhiteListData'] ?: '';
            $csv_arr = @json_decode($csvData, true);
            if (empty($csv_arr)) {
                return $this->outputError(-1, '上传格式有问题');
            }
            if (!$button_tag_id) {
                return $this->outputError(-1, 'button_tag不存在');
            }
            $button_tag_info = BbcRankButtonTag::findFirst($button_tag_id);
            if (!$button_tag_info) {
                return $this->outputError(-1, 'button_tag不存在');
            }
            $uids = array_column($csv_arr, 'uid');
            $uids = array_map('intval', $uids);
            $uids = array_unique($uids);

            $insertData = [
                'admin_id'      => $this->uid,
                'button_tag_id' => $button_tag_id,
                'act_id'        => $button_tag_info->act_id,
                'dateline'      => time(),
                'type'          => $type,
            ];

            finish_request_response([]);

            foreach (array_chunk($uids, 500) as $uidArr) {
                $insert = [];
                $users = XsUserProfile::getListByUid($uidArr, 'uid');
                foreach ($uidArr as $uid) {
                    if (isset($users[$uid])) {
                        $insertData['uid'] = $uid;
                        $insert[] = $insertData;
                    }
                }
                BbcRankWhiteList::addBatch($insert, 'REPLACE');
                sleep(1);
            }
            return $this->outputSuccess();
        } elseif ($c == 'del') {
            $id = $this->request->getPost('id', 'int', 0);
            $res = BbcRankWhiteList::findFirst($id);
            if (!$res) {
                return $this->outputError(-1, '不存在此信息');
            }
            $res->delete();
            return $this->outputSuccess();
        } elseif ($c == 'clear') {
            $button_tag_id = $this->request->getPost('button_tag_id', 'int', 0);
            list($res, $msg, $row) = (new ButtonTagService())->clearWhiteByButtonTagId($button_tag_id, BbcRankWhiteList::TYPE_USER);
            if (!$res) {
                return $this->outputError(-1, $msg);
            }
            return $this->outputSuccess();
        } elseif ($c == 'add') {
            $params = $this->request->getPost();
            $uid = intval($params['uid'] ?? 0);
            $button_tag_id = intval($params['button_tag_id'] ?? 0);
            if (!$button_tag_id) {
                return $this->outputError(-1, 'button_tag不存在');
            }
            $button_tag_info = BbcRankButtonTag::findFirst($button_tag_id);
            if (!$button_tag_info) {
                return $this->outputError(-1, 'button_tag不存在');
            }
            $arr = XsUserProfile::findFirst(array(
                'conditions' => "uid = :uid:",
                'bind'       => array(
                    'uid' => $uid
                )
            ));
            if (!$arr) {
                return $this->outputError(-1, '此app不存在该用户');
            }
            $res = BbcRankWhiteList::findFirst(array(
                'conditions' => "uid = :uid: and button_tag_id = :button_tag_id:",
                'bind'       => array(
                    'uid'           => $uid,
                    'button_tag_id' => $button_tag_id,
                )
            ));
            if ($res) {
                return $this->outputError(-1, '该类型白名单已经存在');
            }
            $log = new BbcRankWhiteList();
            $log->uid = $uid;
            $log->admin_id = $this->uid;
            $log->button_tag_id = $button_tag_id;
            $log->act_id = $button_tag_info->act_id;
            $log->dateline = time();
            $log->save();
            return $this->outputSuccess();
        }
        return $this->outputError(-1, "params c is error");
    }

    /**
     * @page activity
     * @point addScore
     */
    public function addScoreAction()
    {
        $params = $this->params;

        $c = $params['c'] ?? '';
        switch ($c) {
            case 'getButtonList':
                return $this->outputSuccess($this->service->getAddScoreButtonList($params['act_id'] ?? 0));
            case 'upload':
                list($res, $msg, $data) = $this->service->uploadScore($params);
                return $res ? $this->outputSuccess($data) : $this->outputError(-1, $msg);
            default:
                list($res, $msg, $data) = $this->service->validationAddScore($params);
                if (!$res) {
                    return $this->outputError(-1, $msg);
                }
                finish_request_response([]);
                $this->service->addScore($data);
        }
    }
}