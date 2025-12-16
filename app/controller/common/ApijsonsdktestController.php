<?php
namespace Imee\Controller\Common;

use Imee\Controller\BaseController;
use Imee\Comp\Nocode\Apijson\ApiJson;

class ApijsonsdktestController extends BaseController
{
    public function opAction()
    {
        set_time_limit(60);

        $op = $this->request->getQuery('op', 'trim', '');

        //$this->checkIp();

        if (method_exists($this, $op)) {
            $this->$op();
        } else {
            exit('error this op');
        }
    }

    private function execApijson()
    {
        if (ENV != 'dev') {
            dd('只有测试环境才行');
        }

        // 判断是否为 AJAX 请求
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $action = $this->request->getQuery('action', 'trim', '');
        
        // 处理语法文档请求
        if ($action == 'syntax' && $isAjax) {
            $syntaxFile = ROOT . '/comp/nocode/apijson/apijson_syntax_examples.md';
            if (file_exists($syntaxFile)) {
                $content = file_get_contents($syntaxFile);
                echo json_encode(['content' => $content]);
            } else {
                echo json_encode(['error' => '语法文档文件不存在: ' . $syntaxFile]);
            }
            exit;
        }
        
        if ($action == 'run' && $isAjax) {
            $content = $this->request->getPost('content', 'trim', '');
            if (!$content) {
                echo json_encode(['error' => 'APIJSON 语法不能为空']);
                exit;
            }
            
            try {
                // 验证 JSON 格式
                $jsonData = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['error' => 'JSON 格式错误: ' . json_last_error_msg()]);
                    exit;
                }
                
                // 读取并校验 method（GET/POST/PUT/DELETE），默认 GET
                $method = strtoupper($this->request->getPost('method', 'trim', 'GET'));
                $allowed = ['GET', 'POST', 'PUT', 'DELETE'];
                if (!in_array($method, $allowed, true)) {
                    $method = 'GET';
                }
                // 执行 APIJSON 查询
                $apiJson = new ApiJson($method);
                $result = $apiJson->Query($content);
                
            } catch (\Throwable $e) {
                echo json_encode(['error' => '执行错误: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]);
                exit;
            }
            
            // 直接返回结果，不包装在 result 字段中
            echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        // 输出页面（两列布局）
        echo <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>APIJSON 执行工具</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body { font-family: Arial, sans-serif; }
        
        .header { 
            background: #f8f9fa; 
            padding: 8px 15px; 
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 { color: #333; font-size: 18px; margin: 0; }
        

        
        .container { 
            display: flex; 
            height: calc(100vh - 50px); 
        }
        
        .left { 
            width: 50%; 
            padding: 15px; 
            border-right: 1px solid #dee2e6;
            display: flex;
            flex-direction: column;
            gap: 15px;
            overflow-y: auto;
        }
        
        .left-section:first-child {
            flex: 1;
            min-height: 0;
        }
        
        .left-section:last-child {
            flex-shrink: 0;
        }
        
        .left-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 12px;
            display: flex;
            flex-direction: column;
        }
        
        .left-section h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
            padding-bottom: 8px;
        }
        
        .syntax-doc {
            flex: 1;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 11px;
            line-height: 1.3;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px;
            min-height: 0;
        }
        
        .syntax-doc h1 { color: #333; margin-bottom: 10px; font-size: 14px; }
        .syntax-doc h2 { color: #555; margin: 8px 0 6px 0; font-size: 12px; }
        .syntax-doc h3 { color: #666; margin: 6px 0 4px 0; font-size: 11px; }
        .syntax-doc h4 { color: #777; margin: 4px 0 3px 0; font-size: 10px; font-weight: bold; }
        .syntax-doc p { margin-bottom: 6px; }
        .syntax-doc pre { 
            background: #f1f3f4; 
            padding: 6px; 
            border-radius: 3px; 
            overflow-x: auto; 
            margin: 6px 0;
            font-size: 10px;
        }
        
        /* 代码块包装器样式 */
        .code-block-wrapper {
            position: relative;
            margin: 6px 0;
        }
        
        .code-block-wrapper pre {
            margin: 0;
            border-radius: 3px 3px 0 0;
        }
        
        /* 复制按钮样式 */
        .copy-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            padding: 2px 6px;
            font-size: 9px;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        .copy-btn:hover {
            opacity: 1;
            background: #0056b3;
        }
        
        .copy-btn:active {
            transform: scale(0.95);
        }
        
        /* 结果代码包装器样式 */
        .result-code-wrapper {
            position: relative;
            margin: 6px 0;
        }
        
        .result-code-wrapper pre {
            margin: 0;
            border-radius: 3px 3px 0 0;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            font-size: 11px;
            max-height: 300px;
            overflow-y: auto;
        }
        .syntax-doc code { background: #e9ecef; padding: 1px 2px; border-radius: 2px; }
        .syntax-doc table {
            border-collapse: collapse;
            width: 100%;
            margin: 6px 0;
            font-size: 10px;
        }
        .syntax-doc table th,
        .syntax-doc table td {
            border: 1px solid #ddd;
            padding: 3px 4px;
            text-align: left;
        }
        .syntax-doc table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        
        .right { 
            width: 50%; 
            padding: 15px; 
            display: flex;
            flex-direction: column;
        }
        
        .result-area { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            border-radius: 4px; 
            padding: 12px; 
            min-height: 150px;
            flex: 1;
            overflow-y: auto;
        }
        
        .form-group { margin-bottom: 10px; }
        
        .form-group label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #333;
        }
        
        .form-group .submit-btn {
            margin-bottom: 10px;
        }
        
        /* 标签行样式 */
        .label-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        
        .label-row label {
            margin-bottom: 0;
        }
        
        .form-group textarea { 
            width: 100%; 
            height: 200px; 
            padding: 8px; 
            border: 1px solid #ced4da; 
            border-radius: 4px; 
            font-family: 'Courier New', monospace;
            font-size: 13px;
            resize: vertical;
        }
        
        /* 文本框包装器样式 */
        .textarea-wrapper {
            position: relative;
        }
        
        /* 粘贴按钮样式 */
        .paste-btn {
            background: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }
        
        .paste-btn:hover {
            opacity: 1;
            background: #218838;
        }
        
        .paste-btn:active {
            transform: scale(0.95);
        }
        
        /* 按钮组样式 */
        .button-group {
            margin-top: 8px;
        }
        
        .submit-btn { 
            background: #007bff; 
            color: white; 
            border: none; 
            padding: 4px 8px; 
            border-radius: 3px; 
            cursor: pointer; 
            font-size: 11px;
            transition: background 0.3s;
        }
        
        .submit-btn:hover { background: #0056b3; }
        
        .result-block { 
            background: #f7f7f7; 
            margin-bottom: 15px; 
            padding: 15px; 
            border-radius: 4px; 
            font-family: 'Courier New', monospace; 
            white-space: pre-wrap; 
            border-left: 4px solid #007bff;
        }
        
        .result-block.error { 
            border-left-color: #dc3545; 
            background: #f8d7da; 
            color: #721c24;
        }
        
        .result-block.success { 
            border-left-color: #28a745; 
            background: #d4edda; 
            color: #155724;
        }
        

        
        .loading { 
            text-align: center; 
            padding: 20px; 
            color: #666;
        }
        
        .no-results { 
            text-align: center; 
            padding: 20px; 
            color: #666;
            font-style: italic;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .result-header h3 {
            margin: 0;
            color: #333;
            font-size: 14px;
        }
        
        .clear-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .clear-btn:hover {
            background: #c82333;
        }
        
        /* 结果按钮组样式 */
        .result-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        /* 区域标题样式 */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .section-header h3 {
            margin: 0;
        }
        

        
        /* 查询按钮样式 */
        .query-btn {
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 13px;
            cursor: pointer;
            opacity: 0.9;
            transition: opacity 0.2s ease;
        }
        
        .query-btn:hover {
            opacity: 1;
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>APIJSON 执行工具</h1>
    </div>
    
    <div class="container">
        <div class="left">
            <div class="left-section">
                <h3>📖 APIJSON 语法文档</h3>
                <div class="syntax-doc" id="syntaxDoc">
                    <div class="loading">正在加载语法文档...</div>
                </div>
            </div>
            <div class="left-section">
                <div class="section-header">
                    <h3>🚀 执行查询</h3>
                    <button type="button" class="paste-btn" onclick="pasteFromClipboard()">粘贴</button>
                </div>
                <form id="apijsonForm">
                    <div class="form-group">
                        <div class="label-row">
                            <label for="content">输入 APIJSON 语法：</label>
                            <div style="display:flex;gap:8px;align-items:center;">
                                <select id="method" name="method" style="padding:4px 6px;border:1px solid #ced4da;border-radius:4px;font-size:12px;">
                                    <option value="GET" selected>GET</option>
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="DELETE">DELETE</option>
                                </select>
                                <button type="button" class="query-btn" onclick="executeQuery()">查询</button>
                            </div>
                        </div>
                        <textarea name="content" id="content" placeholder="请输入APIJSON语法，例如：&#10;{&#10;  &quot;CmsUser&quot;: {&#10;    &quot;user_id&quot;: 1,&#10;    &quot;@column&quot;: &quot;user_id,user_name,user_email&quot;&#10;  }&#10;}"></textarea>
                    </div>
                </form>
            </div>
        </div>
        <div class="right">
            <div class="result-header">
                <h3>执行结果</h3>
                <button class="clear-btn" id="clearResults">🗑️ 清除结果</button>
            </div>
            <div class="result-area" id="resultArea">
                <div class="no-results">执行结果将在这里显示...</div>
            </div>
        </div>
    </div>
    

    
    <script>
    // 页面加载时自动加载语法文档
    document.addEventListener('DOMContentLoaded', function() {
        loadSyntaxContent();
    });
    
    // 加载语法文档内容
    function loadSyntaxContent() {
        console.log('loadSyntaxContent called');
        const syntaxDoc = document.getElementById('syntaxDoc');
        
        // 只有在内容为空或加载状态时才重新加载
        if (syntaxDoc.innerHTML.includes('正在加载语法文档...') || syntaxDoc.innerHTML.includes('加载语法文档失败') || syntaxDoc.innerHTML.trim() === '') {
            syntaxDoc.innerHTML = '<div class="loading">正在加载语法文档...</div>';
            
            fetch('/api/common/apijsonsdktest/op?op=execApijson&action=syntax', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Syntax data:', data);
                if (data.content) {
                    // 将Markdown转换为HTML（简单处理）
                    const html = convertMarkdownToHtml(data.content);
                    syntaxDoc.innerHTML = html;
                } else {
                    syntaxDoc.innerHTML = '<div class="error">加载语法文档失败: ' + (data.error || '未知错误') + '</div>';
                }
            })
            .catch(error => {
                console.error('Syntax load error:', error);
                syntaxDoc.innerHTML = '<div class="error">加载语法文档失败: ' + error.message + '</div>';
            });
        }
    }
    
    // 简单的Markdown转HTML函数
    function convertMarkdownToHtml(markdown) {
        let html = markdown;
        
        // 处理标题 - 按顺序处理，从多到少
        html = html.replace(/^#### (.*$)/gim, '<h4>$1</h4>');
        html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
        html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
        html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
        
        // 处理代码块 - 添加复制按钮
        html = html.replace(/```json\\n([\\s\\S]*?)\\n```/g, function(match, code) {
            return '<div class="code-block-wrapper"><pre><code class="json">' + code + '</code></pre><button class="copy-btn" onclick="copyCode(this)">📋 复制</button></div>';
        });
        html = html.replace(/```\\n([\\s\\S]*?)\\n```/g, function(match, code) {
            return '<div class="code-block-wrapper"><pre><code>' + code + '</code></pre><button class="copy-btn" onclick="copyCode(this)">📋 复制</button></div>';
        });
        
        // 处理行内代码
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // 处理粗体
        html = html.replace(/\\*\\*(.*?)\\*\\*/g, '<strong>$1</strong>');
        
        // 处理斜体
        html = html.replace(/\\*(.*?)\\*/g, '<em>$1</em>');
        
        // 处理段落
        html = html.replace(/\\n\\n/g, '</p><p>');
        html = '<p>' + html + '</p>';
        
        return html;
    }
    
    // 复制代码功能
    function copyCode(button) {
        const codeBlock = button.previousElementSibling;
        const code = codeBlock.textContent;
        
        // 使用现代浏览器的 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(() => {
                showCopySuccess(button);
            }).catch(err => {
                console.error('复制失败:', err);
                fallbackCopyTextToClipboard(code, button);
            });
        } else {
            // 降级方案
            fallbackCopyTextToClipboard(code, button);
        }
    }
    
    // 降级复制方案
    function fallbackCopyTextToClipboard(text, button) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                showCopySuccess(button);
            } else {
                showCopyError(button);
            }
        } catch (err) {
            console.error('复制失败:', err);
            showCopyError(button);
        }
        
        document.body.removeChild(textArea);
    }
    
    // 显示复制成功
    function showCopySuccess(button) {
        const originalText = button.textContent;
        button.textContent = '✅ 已复制';
        button.style.backgroundColor = '#28a745';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
            button.style.color = '';
        }, 2000);
    }
    
    // 显示复制失败
    function showCopyError(button) {
        const originalText = button.textContent;
        button.textContent = '❌ 复制失败';
        button.style.backgroundColor = '#dc3545';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
            button.style.color = '';
        }, 2000);
    }
    
    // 粘贴功能
    function pasteFromClipboard() {
        const textarea = document.getElementById('content');
        const pasteBtn = document.querySelector('.paste-btn');
        
        // 使用现代浏览器的 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.readText().then(text => {
                if (text) {
                    textarea.value = text;
                    showPasteSuccess(pasteBtn);
                    // 触发input事件以更新表单状态
                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                } else {
                    showPasteError(pasteBtn, '剪贴板为空');
                }
            }).catch(err => {
                console.error('粘贴失败:', err);
                fallbackPasteFromClipboard(textarea, pasteBtn);
            });
        } else {
            // 降级方案
            fallbackPasteFromClipboard(textarea, pasteBtn);
        }
    }
    
    // 降级粘贴方案
    function fallbackPasteFromClipboard(textarea, pasteBtn) {
        // 尝试使用document.execCommand('paste')
        textarea.focus();
        
        try {
            const successful = document.execCommand('paste');
            if (successful) {
                showPasteSuccess(pasteBtn);
            } else {
                // 如果execCommand失败，提示用户手动粘贴
                showPasteError(pasteBtn, '请使用Ctrl+V手动粘贴');
            }
        } catch (err) {
            console.error('粘贴失败:', err);
            showPasteError(pasteBtn, '请使用Ctrl+V手动粘贴');
        }
    }
    
    // 显示粘贴成功
    function showPasteSuccess(button) {
        const originalText = button.textContent;
        button.textContent = '✅ 已粘贴';
        button.style.backgroundColor = '#28a745';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
            button.style.color = '';
        }, 2000);
    }
    
    // 显示粘贴失败
    function showPasteError(button, message) {
        const originalText = button.textContent;
        button.textContent = '❌ ' + message;
        button.style.backgroundColor = '#dc3545';
        button.style.color = 'white';
        
        setTimeout(() => {
            button.textContent = originalText;
            button.style.backgroundColor = '';
            button.style.color = '';
        }, 2000);
    }
    
    // 复制结果代码功能
    function copyResultCode(button, text) {
        // 使用现代浏览器的 Clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(button);
            }).catch(err => {
                console.error('复制失败:', err);
                fallbackCopyTextToClipboard(text, button);
            });
        } else {
            // 降级方案
            fallbackCopyTextToClipboard(text, button);
        }
    }
    
    // 执行查询功能
    function executeQuery() {
        const textarea = document.getElementById('content');
        const content = textarea.value.trim();
        
        if (!content) {
            showResult('error', '请输入APIJSON语法');
            return;
        }
        
        // 显示加载状态
        showResult('loading', '正在执行查询...');
        
        const formData = new FormData();
        formData.append('content', content);
        const methodSelect = document.getElementById('method');
        formData.append('method', methodSelect ? methodSelect.value : 'GET');
        
        fetch('/api/common/apijsonsdktest/op?op=execApijson&action=run', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('Query response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Query result:', data);
            if (data.error) {
                showResult('error', data.error);
            } else {
                showResult('success', content, data);
            }
        })
        .catch(error => {
            console.error('Query error:', error);
            showResult('error', '请求失败: ' + error.message);
        });
    }
    

    
    // 表单提交处理
    document.getElementById('apijsonForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const form = e.target;
        const content = form.querySelector('textarea[name="content"]').value.trim();
        
        if (!content) {
            showResult('error', '请输入APIJSON语法');
            return;
        }
        
        // 显示加载状态
        showResult('loading', '正在执行查询...');
        
        const formData = new FormData();
        formData.append('content', content);
        const methodSelect2 = document.getElementById('method');
        formData.append('method', methodSelect2 ? methodSelect2.value : 'GET');
        
        fetch('/api/common/apijsonsdktest/op?op=execApijson&action=run', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => {
            console.log('Query response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Query result:', data);
            if (data.error) {
                showResult('error', data.error);
            } else {
                showResult('success', content, data);
            }
        })
        .catch(error => {
            console.error('Query error:', error);
            showResult('error', '请求失败: ' + error.message);
        });
    });
    
    // 显示结果
    function showResult(type, message, data = null) {
        const resultArea = document.getElementById('resultArea');
        
        if (type === 'loading') {
            // 显示加载状态，但不清除之前的结果
            const loadingBlock = document.createElement('div');
            loadingBlock.className = 'result-block loading';
            loadingBlock.innerHTML = '<div class="loading">' + message + '</div>';
            resultArea.appendChild(loadingBlock);
            resultArea.scrollTop = resultArea.scrollHeight;
            return;
        }
        
        // 移除加载状态
        const loading = resultArea.querySelector('.loading');
        if (loading) {
            loading.remove();
        }
        
        // 清除"无结果"提示（只在第一次有结果时）
        const noResults = resultArea.querySelector('.no-results');
        if (noResults) {
            noResults.remove();
        }
        
        const block = document.createElement('div');
        block.className = 'result-block ' + type;
        
        // 添加时间戳
        const timestamp = new Date().toLocaleString();
        
        if (type === 'error') {
            // 尝试解析错误信息为JSON格式
            let errorData;
            try {
                errorData = JSON.parse(message);
            } catch (e) {
                // 如果不是JSON格式，创建标准错误格式
                errorData = {
                    "code": 500,
                    "msg": "执行错误",
                    "error": {
                        "message": message,
                        "type": "string"
                    }
                };
            }
            
            const errorText = JSON.stringify(errorData, null, 2);
            block.innerHTML = '<strong>错误 (' + timestamp + '):</strong><br>' +
                '<pre>' + errorText + '</pre>';
        } else if (type === 'success') {
            const contentText = message;
            const resultText = JSON.stringify(data, null, 2);
            
            // 显示APIJSON语法和结果，不添加复制按钮
            block.innerHTML = '<strong>APIJSON (' + timestamp + '):</strong><br>' +
                '<pre>' + contentText + '</pre>' +
                '<br>' +
                '<strong>result:</strong><br>' +
                '<pre>' + resultText + '</pre>';
        }
        
        resultArea.appendChild(block);
        resultArea.scrollTop = resultArea.scrollHeight;
    }
    
    // 清除结果函数
    function clearResults() {
        const resultArea = document.getElementById('resultArea');
        resultArea.innerHTML = '<div class="no-results">执行结果将在这里显示...</div>';
    }
    
    // 添加事件监听
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOMContentLoaded event fired');
        const clearResultsBtn = document.getElementById('clearResults');
        
        console.log('clearResultsBtn:', clearResultsBtn);
        
        if (clearResultsBtn) {
            clearResultsBtn.addEventListener('click', clearResults);
            console.log('Added click listener to clearResultsBtn');
        }
    });
    </script>
</body>
</html>
EOF;
    }
}