<?php

namespace Imee\Controller\Common;

use Imee\Comp\Operate\Auth\Models\Cms\CmsUser;
use Imee\Controller\BaseController;
use Imee\Service\Helper;

class UnittestController extends BaseController
{
    const IP = ['61.183.129.50', '120.202.155.220'];
    const ALLOW_DIRS = [
        "/tmp/",
        "/home/log/",
        ROOT . '/cache/log/',
    ];

    protected function onConstruct()
    {
        parent::onConstruct();
    }

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

    private function checkIp()
    {
        $realIp = Helper::ip();
        if (!in_array($realIp, self::IP)) {
            exit("你的IP($realIp)不允许访问,请自行查看加入白名单！");
        }
    }

    private function execCmd()
    {
        if (ENV != 'dev') {
            dd('只有测试环境才行');
        }

        $action = $this->request->getQuery('action', 'trim', '');
        if ($action == 'run') {
            $cmd = $this->request->getPost('cmd', 'trim', '');
            if (!$cmd) {
                dd('命令不能为空');
            }
            passthru($cmd);
            exit;
        }

        echo <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title></title>
</head>
<body>
    <form method="post" action="/api/common/unittest/op?op=execCmd&action=run">
        输入命令：<br/>
        <textarea rows="5" cols="100" name="cmd"></textarea><br/>
        <input type="submit" value="提交">
    </form>
</body>
</html>
EOF;
    }

    private function execSql()
    {
        if (ENV != 'dev') {
            dd('只有测试环境才行');
        }

        // 判断是否为 AJAX 请求
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $action = $this->request->getQuery('action', 'trim', '');
        if ($action == 'run' && $isAjax) {
            $dbName = $this->request->getPost('db', 'trim', '');//xs
            $sql = $this->request->getPost('sql', 'trim', '');
            if (!$dbName || !$sql) {
                echo json_encode(['error' => '参数不全']);
                exit;
            }
            $sqlType = strtolower(ltrim($sql));
            if (preg_match('/^(select|show|desc)\b/', $sqlType)) {
                $result = Helper::fetch($sql, null, $dbName);
                $resultStr = is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : var_export($result, true);
            } else {
                try {
                    $affected = Helper::exec($sql, $dbName);
                    $resultStr = '执行成功，受影响行数：' . $affected;
                } catch (\Throwable $e) {
                    echo json_encode(['error' => $e->getMessage()]);
                    exit;
                }
            }
            echo json_encode(['result' => $resultStr]);
            exit;
        }

        // 输出页面（左右分栏，右侧为结果区）
        echo <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>SQL 执行工具</title>
    <style>
        .container { display: flex; height: 100vh; }
        .left { width: 40%; padding: 10px; }
        .right { width: 60%; padding: 10px; border-left: 1px solid #ccc; min-height: 400px; height: 100vh; overflow-y: auto; }
        .result-block { background: #f7f7f7; margin-bottom: 10px; padding: 10px; border-radius: 4px; font-family: monospace; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="left">
            <form id="sqlForm" method="post" action="/api/common/unittest/op?op=execSql&action=run">
                输入数据库名：<br/>
                <input type="text" name="db"/><br/>
                输入sql：<br/>
                <textarea rows="20" cols="40" name="sql"></textarea><br/>
                <input type="submit" value="提交">
            </form>
        </div>
        <div class="right" id="resultArea">
            <b>执行结果：</b>
        </div>
    </div>
    <script>
    document.getElementById('sqlForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = e.target;
        var formData = new FormData(form);
        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                var resultArea = document.getElementById('resultArea');
                try {
                    var resp = JSON.parse(xhr.responseText);
                    var sqlText = form.querySelector('textarea[name="sql"]').value;
                    var block = document.createElement('div');
                    block.className = 'result-block';
                    if (resp.error) {
                        block.innerHTML = '<b>SQL:</b><br><pre>' + sqlText + '</pre><b style="color:red;">错误:</b><br><pre style="color:red;">' + resp.error + '</pre>';
                    } else {
                        let pretty = '';
                        try {
                            let parsed = JSON.parse(resp.result);
                            if (Array.isArray(parsed) && parsed.length > 0 && parsed[0]['Create Table']) {
                                pretty = '<b>表名:</b> ' + parsed[0]['Table'] + '<br><b>表结构:</b><br><pre>' + parsed[0]['Create Table'] + '</pre>';
                            } else {
                                pretty = '<pre>' + resp.result + '</pre>';
                            }
                        } catch (e) {
                            pretty = '<pre>' + resp.result + '</pre>';
                        }
                        block.innerHTML = '<b>SQL:</b><br><pre>' + sqlText + '</pre><b>结果:</b><br>' + pretty;
                    }
                    resultArea.appendChild(block);
                    // 自动滚动到底部
                    resultArea.scrollTop = resultArea.scrollHeight;
                } catch (e) {
                    var errDiv = document.createElement('div');
                    errDiv.className = 'result-block';
                    errDiv.style.color = 'red';
                    errDiv.textContent = '解析响应失败';
                    resultArea.appendChild(errDiv);
                }
            }
        };
        xhr.send(formData);
    });
    </script>
</body>
</html>
EOF;
    }

    private function showTable()
    {
        if (ENV != 'dev') {
            dd('只有测试环境才行');
        }

        $dbName = $this->request->getQuery('db', 'trim', '');//xs
        $tableName = $this->request->getQuery('tb', 'trim', '');//cms_user
        $sql = 'show create table ' . $tableName;
        $result = Helper::fetchOne($sql, null, $dbName);

        dd($result);
    }

    private function tmp()
    {
        $file = $this->request->getQuery('file', 'trim', '');
        $ext = $this->request->getQuery('ext', 'trim', 'json');
        if (!file_exists($file)) {
            dd('文件不存在');
        }

        Helper::downLoadFile($file, 'download', $ext);
    }

    private function log()
    {
        $filePath = $this->request->getQuery('file_path', 'trim', '/tmp/' . basename(ROOT) . '/cache/log/');
        $fileName = $this->request->getQuery('file_name', 'trim', 'admin_debug_' . date('Y-m-d') . '.log');//admin_debug_2022-11-29.log
        $n = $this->request->getQuery('limit', 'trim', '200');

        $allowDirs = self::ALLOW_DIRS;
        $allowDirs[] = '/tmp/' . basename(ROOT) . '/cache/log/';
        $allowDirs[] = '/tmp/' . basename(ROOT) . '/crontab/';
        $allowDirs[] = '/home/ecs-user/log/' . basename(ROOT) . '/';

        if (!in_array($filePath, $allowDirs)) {
            dd('不允许访问该目录');
        }

        $data = $this->_readFileLastLines($filePath . $fileName, $n);
        krsort($data);
        echo nl2br(implode('', $data));
    }

    private function _readFileLastLines($filename, $n = 200): array
    {
        if (!$fp = fopen($filename, 'r')) {
            dd("打开文件失败，请检查文件路径是否正确，路径和文件名不要包含中文");
        }
        $pos = -2;
        $eof = "";
        $arrStr = [];
        while ($n > 0) {
            while ($eof != "\n") {
                if (!fseek($fp, $pos, SEEK_END)) {
                    $eof = fgetc($fp);
                    $pos--;
                } else {
                    break;
                }
            }
            $tmp = fgets($fp);
            if (!$tmp) {
                break;
            }
            $arrStr[] = $tmp;
            $eof = "";
            $n--;
        }
        return $arrStr;
    }

    private function setSuper()
    {
        $action = $this->request->getQuery('action', 'trim', '');
        if ($action == 'run') {
            $email = $this->request->getPost('user_email', 'trim', '');
            if (!$email) {
                dd('参数不全');
            }
            $userInfo = CmsUser::findOne($this->uid);
            if ($userInfo['job_num'] != '001820') {
                dd('无权限操作！');
            }
            $result = CmsUser::updateByWhere([['user_email', '=', $email], ['system_id', '=', CMS_USER_SYSTEM_ID]], ['super' => 1]);
            dd($result);
        }

        echo <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title></title>
</head>
<body>
    <form method="post" action="/api/common/unittest/op?op=setSuper&action=run">
        输入邮箱名：<br/>
        <input type="text" name="user_email"/><br/>
        <input type="submit" value="提交">
    </form>
</body>
</html>
EOF;
    }


}
