<?php

namespace Imee\Service\Operate\Emoticons;

use Imee\Comp\Gid\Models\Xs\XsUserProfile;
use Imee\Exception\ApiException;
use Imee\Service\Rpc\PsService;
use Imee\Service\Helper;
use OSS\OssUpload;

class CustomizedEmoticonService
{
    /**
     * @var PsService $rpcService
     */
    private $rpcService;

    public function __construct()
    {
        $this->rpcService = new PsService();
    }

    /**
     * 获取定制表情列表
     * @param array $params
     * @return array
     */
    public function getList(array $params): array
    {
        $queryData = [];
        
        if (!empty($params['id'])) {
            $queryData['id'] = (int)$params['id'];
        }
        if (!empty($params['start_time'])) {
            $queryData['start_time'] = strtotime($params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $queryData['end_time'] = strtotime($params['end_time']);
        }
        if (!empty($params['limit'])) {
            $queryData['page_size'] = (int)$params['limit'];
        } else {
            $queryData['page_size'] = 15; // 默认每页15条
        }
        if (!empty($params['page'])) {
            $queryData['page_num'] = (int)$params['page'];
        } else {
            $queryData['page_num'] = 1; // 默认第1页
        }

        list($res, $msg, $data) = $this->rpcService->queryCustomizedEmoticon($queryData);

        if (!$res) {
            // 开发环境使用 mock 数据
            if (ENV == 'dev') {
                $mockResult = $this->getMockData($queryData);
                return [
                    'data' => $this->formatList($mockResult['data'] ?? []),
                    'total' => $mockResult['total'] ?? 0
                ];
            }
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return [
            'data' => $this->formatList($data['list'] ?? []),
            'total' => $data['total'] ?? 0
        ];
    }

    /**
     * 创建定制表情
     * @param array $params
     * @return array
     */
    public function add(array $params): array
    {
        $detail = $this->formatDetail($params);
        list($res, $msg) = $this->rpcService->createCustomizedEmoticon([
            'detail' => json_encode($detail),
            'operator' => Helper::getSystemUserInfo()['user_name'] ?? ''
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $msg, 'after_json' => $detail];
    }

    /**
     * 修改定制表情
     * @param array $params
     * @return array
     */
    public function edit(array $params): array
    {
        $detail = $this->formatDetail($params);
        
        list($res, $msg) = $this->rpcService->updateCustomizedEmoticon([
            'id' => (int)$params['id'],
            'detail' => json_encode($detail),
            'operator' => Helper::getSystemUserInfo()['user_name'] ?? ''
        ]);

        if (!$res) {
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        return ['id' => $params['id'], 'after_json' => $detail];
    }

    /**
     * 删除定制表情
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'ID不能为空');
        }

        // 这里需要根据实际的删除接口来实现
        // 目前 protobuf 中没有删除接口，可能需要添加
        throw new ApiException(ApiException::MSG_ERROR, '删除功能暂未实现');
    }

    /**
     * 获取定制表情详情
     * @param int $id
     * @return array
     */
    public function info(int $id): array
    {
        if (empty($id)) {
            throw new ApiException(ApiException::MSG_ERROR, 'ID不能为空');
        }

        $queryData = [
            'id' => $id,
            'page_size' => 1,
            'page_num' => 1
        ];


        // 生产环境调用 RPC 接口
        list($res, $msg, $data) = $this->rpcService->queryCustomizedEmoticon($queryData);

        if (!$res) {
            // 开发环境使用 mock 数据
            if (ENV == 'dev') {
                $mockResult = $this->getMockData($queryData);
                if (empty($mockResult['data'])) {
                    throw new ApiException(ApiException::MSG_ERROR, '定制表情不存在');
                }
                // 对 mock 数据也进行格式化处理
                return $this->formatEmoticonInfo($mockResult['data'][0]);
            }
            throw new ApiException(ApiException::MSG_ERROR, $msg);
        }

        if (empty($data['list'])) {
            throw new ApiException(ApiException::MSG_ERROR, '定制表情不存在');
        }

        return $this->formatEmoticonInfo($data['list'][0]);
    }

    /**
     * 格式化表情信息数据
     * @param array $info
     * @return array
     */
    private function formatEmoticonInfo(array $info): array
    {
        $detail = json_decode($info['detail'], true);
        
        // 构建返回数据
        $result = [
            'id' => $info['id'],
            'operator' => $info['operator'] ?? '',
            'update_time' => $info['update_time'] ?? ''
        ];
        
        // 处理详情数据
        if (!empty($detail) && is_array($detail)) {
            $emoticon = $detail;
            
            // 设置图片字段
            $result['icon'] = $emoticon['icon'] ?? '';
            $result['icon_show'] = $emoticon['icon_show'] ?? '';
            $result['icon_url'] = Helper::getHeadUrl($result['icon']);
            $result['icon_show_url'] = Helper::getHeadUrl($result['icon_show']);
            
            // 处理多语言名称字段
            if (isset($emoticon['name']) && is_array($emoticon['name'])) {
                $nameFields = ['cn', 'en', 'ar', 'ko', 'id', 'th', 'vi', 'tr', 'ms', 'ja', 'bn', 'hi', 'ur', 'tl'];
                foreach ($nameFields as $lang) {
                    $fieldName = "name_{$lang}";
                    $result[$fieldName] = $emoticon['name'][$lang] ?? '';
                }
            }
        }
        
        return $result;
    }

    /**
     * 格式化数据用于 RPC 调用
     * @param array $data
     * @return array
     */
    private function formatDetail(array $data): array
    {
        // 前端现在直接提交 icon, icon_show, name_cn 等字段
        
        // 构建表情数据
        $emoticon = [];
        
        // 处理图片字段
        if (isset($data['icon'])) {
            $emoticon['icon'] = $data['icon'];
        }
        
        // 处理面板展示图：如果没有上传，则自动处理
        if (isset($data['icon_show']) && !empty($data['icon_show'])) {
            $emoticon['icon_show'] = $data['icon_show'];
        } else if (isset($data['icon']) && !empty($data['icon'])) {
            // 如果没有上传 icon_show，则自动处理
            $emoticon['icon_show'] = $this->processIconShow($data['icon']);
        }
        
        // 处理多语言名称字段
        $names = [];
        $nameFields = ['cn', 'en', 'ar', 'ko', 'id', 'th', 'vi', 'tr', 'ms', 'ja', 'bn', 'hi', 'ur', 'tl'];
        
        // 先获取英文值作为默认值
        $defaultEnglishValue = '';
        if (isset($data['name_en']) && !empty($data['name_en'])) {
            $defaultEnglishValue = $data['name_en'];
        }
        
        foreach ($nameFields as $lang) {
            $fieldName = "name_{$lang}";
            if (isset($data[$fieldName]) && !empty($data[$fieldName])) {
                $names[$lang] = $data[$fieldName];
            } else if (!empty($defaultEnglishValue)) {
                // 如果当前语言为空，使用英文值作为默认值
                $names[$lang] = $defaultEnglishValue;
            }
        }
        
        if (!empty($names)) {
            $emoticon['name'] = $names;
        }
        
        return $emoticon;
    }



    /**
     * 处理面板展示图：如果没有上传，则自动处理
     * @param string $iconUrl
     * @return string
     */
    private function processIconShow(string $iconUrl): string
    {
        // 检查是否为动图格式
        $isAnimated = $this->isAnimatedImage($iconUrl);
        
        if ($isAnimated) {
            // 动图：获取第一帧并上传到OSS
            return $this->getFirstFrameAndUpload($iconUrl);
        } else {
            // 静态图：直接使用原图
            return $iconUrl;
        }
    }

    /**
     * 判断是否为动图
     * @param string $imageUrl
     * @return bool
     */
    private function isAnimatedImage(string $imageUrl): bool
    {
        $extension = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
        return in_array($extension, ['gif', 'webp']);
    }

    /**
     * 获取动态图第一帧并上传到OSS
     * @param string $dynamicImageUrl
     * @return string
     */
    private function getFirstFrameAndUpload(string $dynamicImageUrl): string
    {
        try {
            $dynamicImageUrl = Helper::getHeadUrl($dynamicImageUrl);
            
            // 实例化OSS上传类
            $ossUpload = new OssUpload(ENV == 'dev' ? BUCKET_DEV : BUCKET_ONLINE);
            
            // 使用OSS的IMG服务获取第一帧，转换为jpg格式
            $firstFrameUrl = $dynamicImageUrl . '?x-oss-process=image/auto-orient,1/format,png';
            
            // 生成新的文件名
            $extension = '.png';
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
     * @param string $filePath
     * @return bool
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
     * @param string $url
     * @return string|null
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

    /**
     * 获取开发环境的 mock 数据
     * @param array $queryData
     * @return array
     */
    private function getMockData(array $queryData): array
    {
        $mockData = [
            [
                'id' => 3,
                'operator' => 'admin',
                'update_time' => strtotime('2025-09-03 12:00:00'),
                'detail' => json_encode([
                    [
                        'icon' => '202509/03/213070643368b7b54597e5e3.29607132.gif',
                        'icon_show' => '2025/09/68b7b58cb2085.jpg',
                        'name' => [
                            'cn' => '开心',
                            'en' => 'cat',
                            'ar' => 'ar',
                            'ko' => 'ko',
                            'id' => 'id',
                            'th' => 'cat',
                            'vi' => 'vi',
                            'tr' => 'cat',
                            'ms' => 'ms',
                            'ja' => 'cat',
                            'bn' => 'cat',
                            'hi' => 'cat',
                            'ur' => 'cat',
                            'tl' => 'cat'
                        ]
                    ]
                ])
            ],
            [
                'id' => 1,
                'operator' => 'admin',
                'update_time' => strtotime('2025-09-03 10:30:00'),
                'detail' => json_encode([
                    [
                        'icon' => 'file/202312/23122115423730.webp',
                        'icon_show' => 'file/202312/23122115430153.jpeg',
                        'name' => [
                            'cn' => '测试表情1',
                            'en' => 'Test Emoticon 1',
                            'ar' => 'اختبار التعبير 1',
                            'ko' => '테스트 이모티콘 1',
                            'id' => 'Emoticon Test 1',
                            'th' => 'อิโมจิทดสอบ 1',
                            'vi' => 'Biểu tượng cảm xúc thử nghiệm 1',
                            'tr' => 'Test Emoji 1',
                            'ms' => 'Emoji Ujian 1',
                            'ja' => 'テスト絵文字1',
                            'bn' => 'পরীক্ষামূলক ইমোজি 1',
                            'hi' => 'परीक्षण इमोजी 1',
                            'ur' => 'ٹیسٹ ایموجี 1',
                            'tl' => 'Test Emoji 1'
                        ]
                    ]
                ])
            ],
            [
                'id' => 2,
                'operator' => 'admin',
                'update_time' => strtotime('2025-09-03 11:00:00'),
                'detail' => json_encode([
                    [
                        'icon' => '2025/09/68b7af459cd2c.jpg',
                        'icon_show' => '2025/09/68b7af459cd2c.jpg',
                        'name' => [
                            'cn' => '测试表情2',
                            'en' => 'Test Emoticon 2',
                            'ar' => 'اختبار التعبير 2',
                            'ko' => '테스트 이모티콘 2',
                            'id' => 'Emoticon Test 2',
                            'th' => 'อิโมจิทดสอบ 2',
                            'vi' => 'Biểu tượng cảm xúc thử nghiệm 2',
                            'tr' => 'Test Emoji 2',
                            'ms' => 'Emoji Ujian 2',
                            'ja' => 'テスト絵文字2',
                            'bn' => 'পরীক্ষামূলক ইমোজি 2',
                            'hi' => 'परीक्षण इमोजी 2',
                            'ur' => 'ٹیسٹ ایموجی 2',
                            'tl' => 'Test Emoji 2'
                        ]
                    ]
                ])
            ]
        ];

        // 处理分页
        $pageSize = $queryData['page_size'] ?? 15;
        $pageNum = $queryData['page_num'] ?? 1;
        $start = ($pageNum - 1) * $pageSize;

        // 如果有ID查询，过滤出对应数据
        if (!empty($queryData['id'])) {
            $filteredData = [];
            foreach ($mockData as $item) {
                if ($item['id'] == $queryData['id']) {
                    $filteredData[] = $item;
                    break;
                }
            }
            $mockData = $filteredData;
        }

        // 返回分页数据
        $pagedData = array_slice($mockData, $start, $pageSize);
        
        return [
            'data' => $pagedData,
            'total' => count($mockData)
        ];
    }

    /**
     * 格式化列表数据，转换为前端需要的结构
     * @param array $list
     * @return array
     */
    private function formatList(array $list): array
    {
        $formattedData = [];
        foreach ($list as $item) {
            $formattedItem = [
                'id' => $item['id'] ?? 0,
                'operator' => $item['operator'] ?? '',
                'update_time' => date('Y-m-d H:i:s', $item['update_time'] ?? 0),
                'uploader' => $item['uploader'] > 0 ? $item['uploader'] . ' - ' . (XsUserProfile::findOne($item['uploader'])['name'] ?? '') : '',
            ];
            
            // 处理 detail 字段
            if (isset($item['detail'])) {
                $detail = is_string($item['detail']) ? json_decode($item['detail'], true) : $item['detail'];
                
                if (!empty($detail) && is_array($detail)) {
                    $emoticon = $detail;
                    
                    // 设置图片字段
                    $formattedItem['icon'] = $emoticon['icon'] ?? '';
                    $formattedItem['icon_show'] = $emoticon['icon_show'] ?? '';
                    $formattedItem['icon_url'] = Helper::getHeadUrl($formattedItem['icon']);
                    $formattedItem['icon_show_url'] = Helper::getHeadUrl($formattedItem['icon_show']);
                    
                    // 处理多语言名称字段
                    if (isset($emoticon['name']) && is_array($emoticon['name'])) {
                        $nameFields = ['cn', 'en', 'ar', 'ko', 'id', 'th', 'vi', 'tr', 'ms', 'ja', 'bn', 'hi', 'ur', 'tl'];
                        foreach ($nameFields as $lang) {
                            $fieldName = "name_{$lang}";
                            $formattedItem[$fieldName] = $emoticon['name'][$lang] ?? '';
                        }
                    }
                }
            }

            $formattedItem['name_show'] = $formattedItem['id'] . ' - ' . ($formattedItem['name_cn'] ?? '');
            
            $formattedData[] = $formattedItem;
        }
        
        return $formattedData;
    }
}