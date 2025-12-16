<?php

namespace Imee\Comp\Nocode\Apijson\Parse;

use Imee\Comp\Nocode\Apijson\Entity\ConditionEntity;
use Imee\Comp\Nocode\Apijson\Entity\TableEntity;
use Imee\Comp\Nocode\Apijson\Handle\AbstractHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionCacheHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionColumnHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionCombineHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionCountHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionDistinctHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionSumHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionAliasHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionExplainHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionFuncHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionGroupHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionHavingHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionLimitHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionOffsetHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionOrderHandle;
use Imee\Comp\Nocode\Apijson\Handle\FunctionProcedureHandle;
use Imee\Comp\Nocode\Apijson\Handle\WhereParserHandle;
use Imee\Comp\Nocode\Apijson\Handle\ValidateMustHandle;
use Imee\Comp\Nocode\Apijson\Handle\ValidateRefuseHandle;
use Imee\Comp\Nocode\Apijson\Handle\ValidateStrictHandle;
use Imee\Comp\Nocode\Apijson\Replace\AbstractReplace;
use Imee\Comp\Nocode\Apijson\Replace\KeywordCountReplace;
use Imee\Comp\Nocode\Apijson\Replace\KeywordPageReplace;
use Imee\Comp\Nocode\Apijson\Replace\QuoteReplace;

class Handle
{

    /**
     * 替换规则
     * @var AbstractReplace[]
     */
    protected $replaceRules = [
        'query' => [
            KeywordCountReplace::class, //count => @limit
            KeywordPageReplace::class,  //page => @offset
            QuoteReplace::class,        //"user_id@": "[]/CmsUser/user_id"
        ],
        'update' => [
            // 更新时同样需要路径引用解析与 WHERE 解析
            QuoteReplace::class,
        ]
    ];


    /**
     * 匹配规则 根据从上自下优先先匹先出
     * @var AbstractHandle[]
     */
    protected $queryMethodRules = [
        'query' => [
            // 1. 结构和安全校验
            ValidateMustHandle::class,
            ValidateRefuseHandle::class,
            ValidateStrictHandle::class, // 必须在 Must 和 Refuse 之后

            // 2. 功能性 Handle
            FunctionCacheHandle::class,
            FunctionFuncHandle::class,
            FunctionProcedureHandle::class,
            FunctionColumnHandle::class,
            FunctionHavingHandle::class,
            FunctionOffsetHandle::class,
            FunctionLimitHandle::class,
            FunctionCountHandle::class,
            FunctionSumHandle::class,
            FunctionDistinctHandle::class,
            FunctionAliasHandle::class,
            FunctionExplainHandle::class,
            FunctionGroupHandle::class,
            FunctionOrderHandle::class,
            
            // 3. 统一的 WHERE 条件解析器
            WhereParserHandle::class,
            
            // 4. 其他
            FunctionCombineHandle::class
        ],
        'update' => [
            // 更新仅需构建 WHERE，不涉及列/排序/聚合等
            ValidateMustHandle::class,
            ValidateRefuseHandle::class,
            ValidateStrictHandle::class,
            WhereParserHandle::class,
        ]
    ];

    /** @var ConditionEntity */
    protected $conditionEntity;

    /** @var TableEntity */
    protected $tableEntity;

    public function __construct(ConditionEntity $conditionEntity, TableEntity $tableEntity)
    {
        $this->conditionEntity = $conditionEntity;
        $this->tableEntity = $tableEntity;
    }

    public function buildQuery()
    {
        $this->build('query');
    }

    public function buildUpdate()
    {
        $this->build('update');
    }

    protected function build(string $action)
    {
        foreach ($this->replaceRules[$action] ?? [] as $replaceRuleClass) {
            /** @var AbstractReplace $replaceRule */
            $replaceRule = new $replaceRuleClass($this->conditionEntity);
            $replaceRule->handle();
        }
        foreach ($this->queryMethodRules[$action] ?? [] as $methodRuleClass) {
            /** @var AbstractHandle $methodRule */
            $methodRule = new $methodRuleClass($this->conditionEntity);
            // 调试信息：记录处理的 Handle 类
            error_log("Handle build - Processing: " . $methodRuleClass);
            $methodRule->handle();
        }
    }
}