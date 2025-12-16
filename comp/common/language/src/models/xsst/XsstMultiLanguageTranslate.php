<?php

namespace Imee\Comp\Common\Language\Models\Xsst;

use Imee\Comp\Common\Language\Models\Redis\LanguageRedis;
use Imee\Exception\ApiException;
use Imee\Service\Helper;
use Phalcon\Di;

class XsstMultiLanguageTranslate extends BaseModel
{
    public static $primaryKey = 'id';

    /**
     * 获取功能翻译
     * @return array
     */
    public static function getTranslateByMid(int $mid): array
    {
        $translate = LanguageRedis::get($mid);
        if (!$translate) {
            $conditions = [['mid', '=', $mid]];
            if ($mid == 0) {
                $conditions[] = ['system_id', '=', SYSTEM_ID];
            }
            $translate = self::findOneByWhere($conditions)['translate_json'] ?? [];
            $translate && LanguageRedis::set($mid, $translate);
        }
        return $translate ? json_decode($translate, true) : [];
    }

    /**
     * 获取模块翻译
     * @param int $moduleId
     * @param string $language
     * @return array
     */
    public static function getModulesLanguage(int $moduleId, string $language): array
    {
        $translate = XsstMultiLanguageTranslate::getTranslateByMid($moduleId);

        if (empty($translate)) {
            return [];
        }

        // 英文兜底，英文也不存在的话，直接返回空
        return $translate[$language] ?? ($language != 'zh_cn' ? $translate['en'] : []) ?? [];
    }

    /**
     * 设置多语言
     * @param int $mid
     * @param string $translateJson
     * @return array
     * @throws ApiException
     */
    public static function addData(int $mid, string $translateJson): array
    {
        $data = [
            'mid'            => $mid,
            'translate_json' => $translateJson,
            'admin_id'       => Helper::getSystemUid(),
            'dateline'       => time(),
            'system_id'      => SYSTEM_ID,
        ];
        $info = XsstMultiLanguageTranslate::findOneByWhere([['mid', '=', $mid], ['system_id', '=', SYSTEM_ID]]);
        if ($info) {
            list($res, $msg) = XsstMultiLanguageTranslate::edit($info['id'], $data);
        } else {
            list($res, $msg) = XsstMultiLanguageTranslate::add($data);
        }
        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        LanguageRedis::set($mid, $translateJson);
        return ['module_id' => $mid, 'after_json' => $data];
    }
}