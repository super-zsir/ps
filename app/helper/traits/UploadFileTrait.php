<?php

/**
 * 图片上传自定义转换方法，解析参数type为方法名
 */

namespace Imee\Helper\Traits;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsGift;
use Imee\Models\Xsst\XsstGiftOperationLog;
use Imee\Models\Xsst\XsstGiftUpload;
use Imee\Service\Helper;
use Imee\Service\Operate\Gift\GiftService;
use OSS\OssUpload;

trait UploadFileTrait
{
    protected function commodity($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        //校验文件大小
        if ($mimeType == 'image/webp' || $mimeType == 'video/mp4') {
            $maxSize = 10240;
        } else {
            $maxSize = 2048;
        }
        $fileSizeInKB = $context->file->getSize() / 1024;
        if ($fileSizeInKB > $maxSize) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, 'webp:10M,其它:2M');
        }

        //最后返回文件path
        switch ($type) {
            case 'header':
            case 'union_header':
                $remoteFile = "h" . date("ymdHis") . rand(10, 99);
                $remoteName = "static/effect/" . $remoteFile . "." . $context->file->getExtension();
                break;
            case 'bubble':
            case 'ring':
            case 'decorate':
            case 'effect':
                if (in_array($context->file->getExtension(), ['webp', 'mp4'])) {
                    $head = 'h';
                } else {
                    $head = 'c';
                }
                $remoteFile = $head . date("ymdHis") . rand(10, 99);
                $remoteName = "static/commodity/" . $remoteFile . "." . $context->file->getExtension();
                break;
            default:
                $remoteFile = "c" . date("ymdHis") . rand(10, 99);
                $remoteName = "static/commodity/" . $remoteFile . "." . $context->file->getExtension();
        }

        return $remoteName;
    }

    protected function pushcontent($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        //校验文件大小
        if ($mimeType == 'image/webp' || $mimeType == 'video/mp4') {
            $maxSize = 10240;
        } else {
            $maxSize = 2048;
        }
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '2M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/pushcontent/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function gift($context, $type)
    {
        $id = $context->request->getQuery('id', 'int', 0);
        $uploadType = $type;

        if ($uploadType == 'tag') {
            $name = date("ymdHis") . rand(10, 99);
            $extname = $context->file->getExtension();
            $remote = XsGift::$uploadTypeMap[$uploadType] ?? '';
            $remote = str_replace('.png', '.' . $extname, $remote);
            return str_replace('{ymd}', $name, $remote);
        }

        if ($id < 1 || !in_array($uploadType, ['list', 'spng', 'epng', 'zip', 'webp', 'head', 'mp4', 'json', 'preview', 'android', 'ios', 'bg', 'blind_mp4'])) {
            throw new ApiException(ApiException::GIFT_UPLOAD_PARAMS_ERROR);
        }

        $gift = XsGift::findOne($id, true);
        if (empty($gift)) {
            throw new ApiException(ApiException::MSG_ERROR, '当前礼物不存在，无法上传资源');
        }

        $origin = $context->file->getTempName();

        $path = '';
        $ext = $context->file->getExtension();

        if ($uploadType == 'list') {
            $gift['is_interact_gift'] && $path = 'cover/';
            $fname = $id . '.png';
        } else if ($uploadType == 'spng') {
            $gift['is_interact_gift'] && $path = 'cover_start/';
            $fname = $id . '.' . ($gift['is_interact_gift'] == 0 ? 's.png' : $ext);
        } else if ($uploadType == 'epng') {
            $gift['is_interact_gift'] && $path = 'cover_end/';
            $fname = $id . '.' . ($gift['is_interact_gift'] == 0 ? 'e.png' : $ext);
        } else if ($uploadType == 'zip') {
            $fname = $id . '.zip';
        } else if ($uploadType == 'webp') {
            $gift['is_interact_gift'] && $path = 'video_one/';
            $fname = $id . '.webp';
        } else if ($uploadType == 'head') {
            $fname = $id . '.h.png';
        } else if ($uploadType == 'json') {
            $fname = $id . '.json';
        } else if ($uploadType == 'mp4') {
            $gift['is_interact_gift'] && $path = 'video_two/';
            $fname = $id . '.mp4';
        } else if ($uploadType == 'preview') {
            $fname = $id . '_diy_preview.mp4';
        } else if ($uploadType == 'bg') {
            $fname = 'diy_' . time() . mt_rand(100000, 999999) . '_bg.mp4';
        } else if ($uploadType == 'android') {
            $zip = new \ZipArchive();
            if ($zip->open($context->file->getTempName())) {
                $zip->extractTo('/tmp/diy_android');
                $zip->close();
            }
            $origin = '/tmp/diy_android/' . $id;
            @copy($origin, $context->file->getTempName());
            $fname = 'android/' . $id;
        } else if ($uploadType == 'ios') {
            $zip = new \ZipArchive();
            if ($zip->open($context->file->getTempName())) {
                $zip->extractTo('/tmp/diy_ios');
                $zip->close();
            }
            $origin = '/tmp/diy_ios/' . $id;
            @copy($origin, $context->file->getTempName());
            $fname = 'ios/' . $id;
        } elseif ($uploadType == 'blind_mp4') {
            if ($gift['is_blind_box'] != 1) {
                throw new ApiException(ApiException::MSG_ERROR, '当前礼物不是盲盒礼物，无法上传资源');
            }
            $path = 'blind/';
            $fname = $id . '.mp4';
        }

        $remoteName = "static/gift_big/" . $path . $fname;

        if (in_array($uploadType, ['webp', 'mp4', 'list'])) {
            $size = $context->file->getSize();

            $client = new OssUpload(ENV == 'dev' ? BUCKET_DEV : BUCKET_ONLINE);

            $hasUploadSucc = $client->moveFileTo($origin, $remoteName);
            if (!$hasUploadSucc) {
                throw new ApiException(ApiException::MSG_ERROR, '上传失败');
            }

            if ($uploadType == 'list' && $gift['is_interact_gift']) {

                $hasUploadSucc = $client->moveFileTo($origin, 'static/gift_big/' . $fname);
                if (!$hasUploadSucc) {
                    throw new ApiException(ApiException::MSG_ERROR, '兼容老版本失败');
                }
            }

            list($res, $msg) = GiftService::updateGiftInfo($uploadType, $gift, $size);
            if (!$res) {
                throw new ApiException(ApiException::MSG_ERROR, $msg);
            }

            // 更新客户资源需要
            if ($uploadType == 'list') {
                $fileName = 'static/gift_big/V' . ($gift['version'] + 1) . DS . $fname;
                $hasUploadSucc = $client->moveFileTo($origin, $fileName);
                if (!$hasUploadSucc) {
                    throw new ApiException(ApiException::MSG_ERROR, '更新版本失败');
                }
            }
        }

        $now = time();
        $content = [
            'type' => $uploadType,
            'before_version' => ($gift['img_update_time'] ?? '') ? date('Y-m-d H:i:s', $gift['img_update_time']) : '',
            'after_version' => date('Y-m-d H:i:s', $now),
        ];
        if ($uploadType == 'mp4') {
            $content = array_merge($content, [
                'before_vap_size' => $gift['vap_size'],
                'after_vap_size' => $size,
            ]);
        }
        $baseLog = [
            'cid'      => $id,
            'content'  => json_encode($content),
            'type'     => XsstGiftOperationLog::TYPE_UPDATE,
            'admin'    => Helper::getSystemUid(),
            'dateline' => $now,
        ];

        [$res, $msg] = XsGift::edit($id, ['img_update_time' => $now]);
        if ($res) {
            XsstGiftOperationLog::add($baseLog);
        }
        
        //图片资源校验
        [$result, $msg, $md5] = XsstGiftUpload::validateMd5((int)$id, $uploadType, $origin);
        if (!$result) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
        //礼物上传资源图地址保存
        XsstGiftUpload::addRow([
            'gid'  => $id,
            'type' => $uploadType,
            'md5'  => $md5,
            'path' => $remoteName,
            'dateline' => time(),
        ], ['gid' => $id, 'type' => $uploadType]);
        
        return $remoteName;
    }

    protected function gamehotrenewal($context, $type)
    {
        $prePath = md5(file_get_contents($context->file->getTempName()));
        return "game/room/zip/" . $prePath . '/' . $context->file->getName();
    }

    //房间背景图管理
    protected function roomBlackground($context, $type)
    {
        $id = $context->request->getQuery('id');
        $source = $context->request->getQuery('source');
        $info = XsChatroomBackground::findOne($id);
        $path = XsChatroomBackground::getImageNamePath($info['type'], $source);
        if (!$path) {
            throw new \Exception('path组装无效');
        }
        return $path;
    }

    protected function medal($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        //校验文件大小
        if ($mimeType == 'image/webp' || $mimeType == 'video/mp4') {
            $maxSize = 10240;
        } else {
            $maxSize = 2048;
        }
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '图片2M视频10M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/medal/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function link($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = [
            'image/jpeg', 'image/png', 'image/tiff', 'image/webp', 'image/gif', 'image/svg-xml',
            'audio/x-wav', 'audio/flac', 'audio/mpeg', 'audio/x-aac',
            'video/x-flv', 'video/x-f4v', 'video/webm', 'video/x-ms-wmv', 'video/x-msvideo',
            'video/mpeg', 'video/vnd.dlna.mpeg-tts', 'text/plain',
            'application/x-MS-bmp', 'application/x-msmetafile', 'application/json', 'application/octet-stream'
        ];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        $maxSize = 30720;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '文件不可超过30M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/link/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function link2($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = [
            'image/jpeg', 'image/png', 'image/tiff', 'image/webp', 'image/gif', 'image/svg-xml',
            'audio/x-wav', 'audio/flac', 'audio/mpeg', 'audio/x-aac',
            'video/x-flv', 'video/x-f4v', 'video/webm', 'video/x-ms-wmv', 'video/x-msvideo',
            'video/mpeg', 'video/vnd.dlna.mpeg-tts', 'text/plain', 'application/zip',
            'application/x-MS-bmp', 'application/x-msmetafile', 'application/json', 'application/octet-stream',
            'application/x-rar'
        ];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        $maxSize = 10240;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '文件不可超过10M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/link/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function link3($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = [
            'image/jpeg', 'image/png', 'image/tiff', 'image/webp', 'image/gif', 'image/svg-xml',
            'audio/x-wav', 'audio/flac', 'audio/mpeg', 'audio/x-aac',
            'video/x-flv', 'video/x-f4v', 'video/webm', 'video/x-ms-wmv', 'video/x-msvideo',
            'video/mpeg', 'video/vnd.dlna.mpeg-tts', 'text/plain', 'application/zip',
            'application/x-MS-bmp', 'application/x-msmetafile', 'application/json', 'application/octet-stream',
            'application/x-rar'
        ];
        $mimeType = mime_content_type($context->file->getTempName());
        if (!$mimeType || !in_array($mimeType, $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $mimeType);
        }
        $maxSize = 51200;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '文件不可超过50M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/link/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function openScreen($context, $type): string
    {
        //校验文件扩展名
        $allowMimeType = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4'];
        $file = @getimagesize($context->file->getTempName());
        if (!$file['mime'] || !in_array($file['mime'], $allowMimeType)) {
            throw new ApiException(ApiException::MIME_NOALLOW_ERROR, $file['mime']);
        }
        if ($file[0] > 1100 || $file[1] > 1800) {
            throw new ApiException(ApiException::MSG_ERROR, '图片长度不能超过1800，宽不能超过1100');
        }
        //校验文件大小
        $maxSize = 5120;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '5M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/openscreen/" . $remoteFile . "." . $context->file->getExtension();
    }

    protected function activity($context, $type): string
    {
        //校验文件后缀名
        $allowExt = ['xlsx', 'xls'];
        $ext = $context->file->getExtension();
        if (!$ext || !in_array($ext, $allowExt)) {
            throw new ApiException(ApiException::EXTENSION_NOALLOW_ERROR);
        }
        //校验文件大小
        $maxSize = 1024 * 5;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '5M');
        }
        $remoteFile = date("ymdHis") . rand(10, 99);
        return "static/activity/" . $remoteFile . "." . $ext;
    }

    protected function soundEffect($context, $type): string
    {
        //校验文件后缀名
        $allowExt = ['mp3', 'wav'];
        $ext = $context->file->getExtension();
        if (!$ext || !in_array($ext, $allowExt)) {
            throw new ApiException(ApiException::EXTENSION_NOALLOW_ERROR);
        }
        //校验文件大小
        $maxSize = 1024 * 10;
        $size = $context->file->getSize();
        if ($maxSize < ceil($size / 1024)) {
            throw new ApiException(ApiException::FILE_SIZE_LARGE_ERROR, '10M');
        }
        $date = date("ymdHis");

        //获取文件时长
        $duration = 0;
        $path = $context->file->getTempName();
        if (file_exists($path)) {
            require_once ROOT . "/comp/common/upload/service/getid3/getid3.php";
            $getID3 = new \getID3();
            $fileInfo = $getID3->analyze($path);
            $duration = $fileInfo['playtime_seconds'] ?? 0;
        }

        $date = sprintf("%s_%s", $date, $duration);
        $fileName = $context->file->getName();
        return "resource/sound/" . $date . "/" . $fileName;
    }
}