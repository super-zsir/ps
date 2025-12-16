<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Exception\ApiException;
use Imee\Models\Xs\XsEmoticonsMeta;
use Imee\Models\Xs\XsFamily;
use Imee\Models\Xs\XsUserProfile;
use Imee\Models\Xsst\BmsOperateLog;
use Imee\Models\Xsst\XsstEmoticonsMeta;
use Imee\Service\Helper;
use Imee\Service\Rpc\PsService;
use Imee\Service\StatusService;
use OSS\OssUpload;

class EmoticonsMaterialService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    public function getList(array $params): array
    {
        $conditions = [];

        if (isset($params['id']) && !empty($params['id'])) {
            $conditions[] = ['id', '=', $params['id']];
        }
        if (isset($params['create_time_sdate']) && !empty($params['create_time_sdate'])) {
            $conditions[] = ['create_time', '>=', strtotime($params['create_time_sdate'])];
        }
        if (isset($params['create_time_edate']) && !empty($params['create_time_edate'])) {
            $conditions[] = ['create_time', '<=', strtotime($params['create_time_edate'])];
        }

        $list = XsEmoticonsMeta::getListAndTotal($conditions, '*', 'id desc', $params['page'] ?? 1, $params['limit'] ?? 15);

        if (empty($list['data'])) {
            return $list;
        }

        $metas = XsstEmoticonsMeta::getBatchCommon(array_column($list['data'], 'id'), ['meta_id', 'name', 'name_en'], 'meta_id');

        $logs = BmsOperateLog::getFirstLogList('emoticonsmaterial', array_column($list['data'], 'id'));
        foreach ($list['data'] as &$item) {
            $item['is_odds'] = XsEmoticonsMeta::NO_ODDS_EMOTICONS;
            $detail = json_decode($item['detail'], true);
            $item['icon'] = $detail[0]['icon'] ?? '';
            if (empty($item['icon'])) {
                $item['is_odds'] = XsEmoticonsMeta::ODDS_EMOTICONS;
                $item['icon'] = $detail[0]['extra'][0] ?? '';
            }
            $item['icon'] = Helper::getHeadUrl($item['icon']);
            $item['icon_show'] = Helper::getHeadUrl($detail[0]['icon_show'] ?? '');
            $item['name'] = $detail[0]['name']['cn'] ?? '';
            $item['name_en'] = $detail[0]['name']['en'] ?? '';
            $item['count'] = [
                'title'    => count($detail),
                'value'    => count($detail),
                'type'     => 'manMadeModal',
                'modal_id' => 'emoticons_info',
                'params'   => [
                    'detail'  => $this->formatDetail($detail),
                    'is_odds' => $item['is_odds'],
                ]
            ];
            $item['operator'] = $logs[$item['id']]['operate_name'] ?? '-';
            $item['dateline'] = isset($logs[$item['id']]['created_time']) ? Helper::now($logs[$item['id']]['created_time']) : '';

            $item['meta_name'] = $metas[$item['id']]['name'] ?? '';
            $item['meta_name_en'] = $metas[$item['id']]['name_en'] ?? '';
        }

        return $list;
    }

    public function add(array $params): array
    {
        $detail = $this->setRpcData($params);
        list($res, $msg) = $this->rpcService->createEmoticonsMeta(['emoticons' => $detail]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        is_numeric($msg) && XsstEmoticonsMeta::addRow([
            'meta_id'  => $msg,
            'name'     => $params['meta_name'],
            'name_en'  => $params['meta_name_en'] ?? '',
            'dateline' => time(),
        ], ['meta_id' => $msg]);

        return ['id' => $msg, 'after_json' => $detail];
    }

    public function edit(array $params): array
    {
        $info = XsEmoticonsMeta::findOne($params['id']);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '标签不存在');
        }

        $detail = $this->setRpcData($params);
        list($res, $msg) = $this->rpcService->updateEmoticonsMeta([
            'emoticons' => $detail,
            'id'        => (int)$params['id']
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        XsstEmoticonsMeta::addRow([
            'meta_id'  => $params['id'],
            'name'     => $params['meta_name'],
            'name_en'  => $params['meta_name_en'] ?? '',
            'dateline' => time(),
        ], ['meta_id' => $params['id']]);

        return ['id' => $params['id'], 'before_json' => $info, 'after_json' => ['detail' => $detail]];
    }

    private function setRpcData(array $data): array
    {
        $detail = [];
        foreach ($data['emoticons'] as $item) {
            $tmp = [];
            foreach ($item as $key => $value) {
                if (strpos($key, 'name_') !== false) {
                    $name = str_replace('name_', '', $key);
                    $tmp['name'][$name] = $value;
                } else {
                    $tmp[$key] = $value;
                }
            }
            $detail[] = $tmp;
        }
        
        // 先处理动态图路径，确保使用最新的路径
        foreach ($detail as &$val) {
            if ($data['is_odds'] == XsEmoticonsMeta::NO_ODDS_EMOTICONS) {
                // 非预期表情：确保icon字段使用最新的动态图路径
                if (!empty($val['extra']) && is_array($val['extra']) && !empty($val['extra'][0])) {
                    $val['icon'] = $val['extra'][0];
                }
            } else {
                // 预期表情：确保extra字段使用最新的动态图路径
                if (!empty($val['icon'])) {
                    if (!isset($val['extra']) || !is_array($val['extra'])) {
                        $val['extra'] = [];
                    }
                    $val['extra'][0] = $val['icon'];
                }
            }
        }
        
        // 处理面板展示图：如果没有上传，则获取动态图第一帧并上传到OSS
        $detail = $this->processIconShow($detail, $data['is_odds']);
        
        foreach ($detail as &$val) {
            if ($data['is_odds'] == XsEmoticonsMeta::NO_ODDS_EMOTICONS) {
                $val['icon'] = $val['extra'][0];
                unset($val['extra']);
            } else {
                unset($val['icon']);
            }
            unset($val['extra_all']);
            unset($val['icon_show_all']);
        }
        return $detail;
    }

    /**
     * 处理面板展示图：如果没有上传，则获取动态图第一帧并上传到OSS
     */
    private function processIconShow(array $detail, int $isOdds): array
    {
        if ($isOdds == XsEmoticonsMeta::NO_ODDS_EMOTICONS) {
            // 非预期表情，只有一个动态图
            foreach ($detail as $key => $item) {
                // 检查面板展示图是否为空、null或不存在
                if ((!isset($item['icon_show']) || empty($item['icon_show']) || $item['icon_show'] === '') && !empty($item['icon'])) {
                    $detail[$key]['icon_show'] = $this->getFirstFrameAndUpload($item['icon']);
                }
            }
        } else {
            // 预期表情，有多个动态图
            foreach ($detail as $key => $item) {
                // 检查面板展示图是否为空、null或不存在
                if ((!isset($item['icon_show']) || empty($item['icon_show']) || $item['icon_show'] === '') && !empty($item['extra']) && is_array($item['extra'])) {
                    // 使用第一个动态图获取第一帧
                    $detail[$key]['icon_show'] = $this->getFirstFrameAndUpload($item['extra'][0]);
                }
            }
        }
        
        return $detail;
    }

    /**
     * 获取动态图第一帧并上传到OSS
     */
    private function getFirstFrameAndUpload(string $dynamicImageUrl): string
    {
        try {
            $dynamicImageUrl = Helper::getHeadUrl($dynamicImageUrl);
            
            // 实例化OSS上传类
            $ossUpload = new OssUpload(ENV == 'dev' ? BUCKET_DEV : BUCKET_ONLINE);
            
            // 使用OSS的IMG服务获取第一帧，转换为jpg格式
            $firstFrameUrl = $dynamicImageUrl . '?x-oss-process=image/auto-orient,1/format,jpg/interlace,1/quality,q_90';
            
            // 生成新的文件名
            $extension = '.jpg';
            $fileName = date('Y/m') . '/' . uniqid() . $extension;
            
            // 下载第一帧图片到临时文件
            $tempFile = $this->downloadImageToTemp($firstFrameUrl);
            if (!$tempFile) {
                // 如果下载失败，直接抛出异常
                throw new ApiException(ApiException::MSG_ERROR, "下载动态图第一帧失败");
            }
            
            // 验证下载的文件是否为有效图片
            if (!$this->isValidImage($tempFile)) {
                @unlink($tempFile);
                throw new ApiException(ApiException::MSG_ERROR, "下载的文件不是有效的图片格式");
            }
            
            // 上传到OSS
            $result = $ossUpload->moveFileTo($tempFile, $fileName);
            
            // 删除临时文件
            @unlink($tempFile);
            
            if ($result) {
                return $fileName;
            }
            
            // 如果上传失败，直接抛出异常
            throw new ApiException(ApiException::MSG_ERROR, "上传第一帧图片到OSS失败");
            
        } catch (ApiException $e) {
            // 重新抛出ApiException
            throw $e;
        } catch (\Exception $e) {
            // 其他异常转换为ApiException
            throw new ApiException(ApiException::MSG_ERROR, "获取第一帧并上传到OSS失败: " . $e->getMessage());
        }
    }

    /**
     * 验证文件是否为有效图片
     */
    private function isValidImage(string $filePath): bool
    {
        try {
            // 检查文件是否存在
            if (!file_exists($filePath)) {
                return false;
            }
            
            // 检查文件类型
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo === false) {
                return false;
            }
            
            $mimeType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            
            // 只允许图片类型
            $allowedMimeTypes = [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'image/webp'
            ];
            
            return in_array($mimeType, $allowedMimeTypes);
            
        } catch (\Exception $e) {
            throw new ApiException(ApiException::MSG_ERROR, "Error validating image: " . $e->getMessage());
        }
    }

    /**
     * 下载图片到临时文件
     */
    private function downloadImageToTemp(string $url): ?string
    {
        $tempFile = null;
        
        try {
            // 设置下载超时和限制
            $context = stream_context_create([
                'http' => [
                    'timeout' => 30, // 30秒超时
                    'max_redirects' => 3, // 最多3次重定向
                    'user_agent' => 'Mozilla/5.0 (compatible; EmoticonService/1.0)'
                ]
            ]);
            
            $tempFile = tempnam(sys_get_temp_dir(), 'emoticon_frame_');
            if ($tempFile === false) {
                return null;
            }
            
            // 使用stream context下载文件
            $content = file_get_contents($url, false, $context);
            
            if ($content === false) {
                @unlink($tempFile);
                return null;
            }
            
            // 检查内容长度（限制为10MB）
            if (strlen($content) > 10 * 1024 * 1024) {
                @unlink($tempFile);
                return null;
            }
            
            $result = file_put_contents($tempFile, $content);
            if ($result === false) {
                @unlink($tempFile);
                return null;
            }
            
            return $tempFile;
            
        } catch (\Exception $e) {
            // 清理临时文件
            if ($tempFile && file_exists($tempFile)) {
                @unlink($tempFile);
            }
            throw new ApiException(ApiException::MSG_ERROR, "Failed to download image: " . $e->getMessage());
        }
    }

    public function delete(int $id): void
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, '标签不存在');
        }

        list($res, $msg) = $this->rpcService->deletedEmoticonsMeta($id);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }
    }

    public function info(int $id): array
    {
        $info = XsEmoticonsMeta::findOne($id);
        if (empty($info)) {
            throw new ApiException(ApiException::MSG_ERROR, '标签不存在');
        }
        $info['is_odds'] = XsEmoticonsMeta::NO_ODDS_EMOTICONS;
        $info['detail'] = json_decode($info['detail'], true);
        foreach ($info['detail'] as $emo) {
            if (array_key_exists('extra', $emo)) {
                $info['is_odds'] = XsEmoticonsMeta::ODDS_EMOTICONS;
            }
        }
        $info['emoticons'] = $this->formatDetail($info['detail']);

        $meta = XsstEmoticonsMeta::findOneByWhere([['meta_id', '=', $id]]);
        $info['meta_name'] = $meta['name'] ?? '';
        $info['meta_name_en'] = $meta['name_en'] ?? '';

        unset($info['detail']);
        return $info;
    }

    private function formatDetail(array $detail): array
    {
        foreach ($detail as &$item) {
            if (isset($item['extra']) && is_array($item['extra'])) {
                $item['extra_all'] = array_map(function ($v) {
                    return ['icon' => Helper::getHeadUrl($v)];
                }, $item['extra']);
                $item['extra'] = array_map(function ($v) {
                    return ['icon' => $v];
                }, $item['extra']);
            } else {
                $item['extra'][] = ['icon' => $item['icon']];
                $item['extra_all'][] = ['icon' => Helper::getHeadUrl($item['icon'])];
            }
            $item['icon_show_all'] = Helper::getHeadUrl($item['icon_show']);
            foreach ($item['name'] as $key => $value) {
                $item['name_' . $key] = $value;
            }
        }

        return $detail;
    }

    public function formatParams(array $params): array
    {
        $params['emoticons'] = json_decode($params['emoticons'], true);
        return $params;
    }
}