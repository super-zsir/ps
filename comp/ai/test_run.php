<?php

define('ROOT', '/workspace');

require_once ROOT . '/comp/ai/app/loader.php';
require_once ROOT . '/comp/ai/src/ModelScanner.php';
require_once ROOT . '/comp/ai/src/CodeGenerator.php';

use Imee\Comp\Ai\CodeGenerator;

$generator = new CodeGenerator();

try {
    // 测试生成 logic
    // 模拟 ka 模块下的 user 业务
    $code = $generator->generateListProcess('ka', 'user', '测试需求', false);
    echo "Generate Success:\n" . substr($code, 0, 100) . "...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
