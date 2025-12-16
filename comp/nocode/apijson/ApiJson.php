<?php

namespace Imee\Comp\Nocode\Apijson;

use Imee\Comp\Nocode\Apijson\Parse\Parse;
use Imee\Comp\Nocode\Apijson\Exception\ApiJsonException;
use Imee\Comp\Nocode\Apijson\Validator\DataValidator;
use Imee\Comp\Nocode\Apijson\Optimizer\QueryOptimizer;
use Imee\Exception\ApiException;
use Phalcon\Di;

class ApiJson
{
    protected $conn;
    protected $params;
    protected $method;

    public function __construct(string $method)
    {
        $this->method = $method;
    }

    /**
     * 检测是否为命令行环境
     * @return bool
     */
    private function isCli(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }



    public function Query(string $content, string $tag = ''): array
    {
        try {
            $content = (array)@json_decode($content, true);
            if (empty($content)) {
                throw new ApiJsonException('请求参数为空', ApiJsonException::ERROR_INVALID_SYNTAX);
            }

            // 数据验证
            $validator = new DataValidator();
            foreach ($content as $tableName => $tableData) {
                $validator->validate($content, $tableName);
            }

            // 查询优化
            $optimizer = new QueryOptimizer();
            $content = $optimizer->optimize($content);

            $parse = new Parse($content, $this->method, $tag);
            $response = $parse->handle();

            // 根据 HTTP 方法决定响应格式
            // GET 请求遵循"所求即所得"原则，直接返回数据
            // 其他方法（POST, PUT, DELETE）返回标准格式
            if ($this->method === 'GET') {
                return $response;
            } else {
                return array_merge([
                    'code' => 200,
                    'msg' => 'success'
                ], $response);
            }

        } catch (ApiJsonException $e) {
            return [
                'code' => $e->getErrorCode(),
                'msg' => $e->getMessage(),
                'error' => $e->toArray()
            ];
        } catch (ApiException $e) {
            // 业务异常：对非 GET 请求返回精简错误结构
            if ($this->method !== 'GET') {
                return [
                    'code' => 500,
                    'msg' => $e->getMsg(),
                ];
            }
            // GET 仍保留原来详细信息
            $traceMsg = method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : '';
            return [
                'code' => 500,
                'msg' => '执行错误',
                'error' => [
                    'message' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'detail' => $e->getMsg(),
                    ],
                    'type' => gettype('string'),
                    'trace' => $traceMsg,
                ]
            ];
        } catch (\Throwable $e) {
            // 统一返回更多上下文，便于定位
            $traceMsg = method_exists($e, 'getTraceAsString') ? $e->getTraceAsString() : '';
            return [
                'code' => 500,
                'msg' => '执行错误',
                'error' => [
                    'message' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'detail' => $e->getMessage(),
                    ],
                    'type' => gettype('string'),
                    'trace' => $traceMsg,
                ]
            ];
        }
    }
}