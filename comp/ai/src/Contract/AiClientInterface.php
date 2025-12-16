<?php

namespace Imee\Comp\Ai\Contract;

interface AiClientInterface
{
    /**
     * 发送提示词给 AI 并获取响应
     * @param string $systemPrompt 系统角色设定
     * @param string $userPrompt 用户输入
     * @return string AI 响应内容
     */
    public function chat(string $systemPrompt, string $userPrompt): string;
}
