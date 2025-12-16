<?php

namespace Imee\Models\Xsst;

use Imee\Models\Xs\XsGift;
use OSS\OssUpload;

class XsstGiftUpload extends BaseModel
{
    public static $type = ['mp4', 'blind_mp4'];

    public static function validateMd5(int $gid, string $type, string $tmpFile): array
    {
        //$client = new OssUpload(ENV == 'dev' ? BUCKET_DEV : BUCKET_ONLINE);
        $md5 = md5(file_get_contents($tmpFile));

        if (!in_array($type, self::$type)) {
            return [true, '', $md5];
        }

        /* $types = array_values(array_diff(self::$type, [$type]));
        $types = self::getListByWhere([['gid', '=', $gid], ['type', 'in', $types]], 'type,md5');
        $types = array_column($types, 'md5', 'type');
        foreach (self::$type as $t) {
            if ($t == $type) {
                continue;
            }
            if (isset($types[$t])) {
                if ($types[$t] == $md5) {
                    return [false, '觉醒特效/预览动画/普通特效 文件不能一致', ''];
                }
            } else {
                $fname = str_replace('{gid}', $gid, XsGift::$uploadTypeMap[$t]);
                $name = explode('/', $fname);
                $tmp = '/tmp/' . date('YmdHis') . '_' . end($name);

                if (!$client->doesObjectExist($fname)) {
                    continue;
                }

                $client->downloadToLocal($fname, $tmp);
                if (file_exists($tmp)) {
                    $remoteMd5 = md5(file_get_contents($tmp));
                    @unlink($tmp);
                    if ($remoteMd5 == $md5) {
                        return [false, '觉醒特效/预览动画/普通特效 文件不能一致', ''];
                    }
                }
            }
        }
        */
        return [true, '', $md5];
    }
}