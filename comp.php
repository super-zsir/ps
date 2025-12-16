<?php
/**
 * 更新组件库
 * Date: 2022-02-02
 * Version: 1.0.0
 */

$autoloadFile = "./comp/autoload.php";
$loaderFile = "./comp/loader.php";
$routerFile = "./comp/router.php";
$config = "./comp.ini";
$configData = parse_ini_file($config, true);

if (empty($argv[1]) || !in_array($argv[1], ['update', 'delete'])) {
    echo '参数缺失,传参：update 安装/更新 delete 删除' . PHP_EOL;
    exit;
}

$action = $argv[1];
$module = $argv[2] ?? 'all';
if ($module == 'all') {
    $handleData = $configData;
} else {
    if (empty($configData[$module])) {
        echo '该模块未配置：' . $module . PHP_EOL;
        exit;
    }
    $handleData = [$module => $configData[$module]];
}

$action($handleData);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

//支持删除
function delete($handleData)
{
    foreach ($handleData as $module => $item) {
        echo 'delete start:' . $module . PHP_EOL;

        //删除模块目录
        $dir = dirname(__FILE__) . '/' . $item['save_path'];
        passthru("rm -rf $dir");
        //如果有加载文件
        //清除autoload.php里该文件的加载
        autoloadCancel($item);
        //提交git
        gitCommit(dirname($dir), 'delete 模块' . $module);

        echo 'delete done:' . $module . PHP_EOL;
    }

    exit;
}

//安装模块
//支持全量更新 判断当前版本号是否一致一样的不更新
//支持按模块名称更新 判断当前版本号是否一致一样的不更新
//如果模块有autoload_file配置需要更新到comp_autoload.php文件里
//根据pull_path下载指定目录，如果pull_path为空，就全部下载
//根据version下载指定tag或者分支代码，为空就从master下载
//更新完成后生成一个version文件记录当前版本号，如果版本号为空的始终允许更新
function update($handleData)
{
    foreach ($handleData as $module => $item) {
        echo 'update start:' . $module . PHP_EOL;

        $version = $item['version'] ?? '';
        $savePath = $item['save_path'];
        $pullPath = $item['pull_path'] ?? '';
        $gitRemote = $item['git_remote'];

        //先判断当前版本
        $versionFile = dirname(__FILE__) . '/' . $savePath . '/version';
        $localVersion = '';
        if (is_file($versionFile)) {
            $localVersion = file_get_contents($versionFile);
        }
        if ($localVersion && $localVersion == $version) {
            echo '版本一致无需更新' . PHP_EOL;
            continue;
        }

        //下载模块
        $dir = dirname(__FILE__) . '/' . $savePath;
        passthru("rm -rf $dir");
        passthru("mkdir -p $dir");
        $commandStr = "cd $dir";
        $commandStr .= " && git init";
        if ($pullPath) {
            $commandStr .= " && git config core.sparsecheckout true";
            $commandStr .= " && echo '/{$pullPath}/' >> .git/info/sparse-checkout";
        }
        $commandStr .= " && git remote add origin $gitRemote";
        if ($version) {
            $commandStr .= " && git fetch origin tag $version";
            $commandStr .= " && git tag";
            $commandStr .= " && git checkout $version > /dev/null 2>&1";
        } else {
            $commandStr .= " && git pull origin master";
        }
        $commandStr .= " && rm -rf .git";
        if ($pullPath) {
            $commandStr .= " && mv {$pullPath}/* ./";
            $commandStr .= " && rm -rf {$pullPath}/";
        }
        echo $commandStr . PHP_EOL;
        passthru($commandStr);

        //更新自动加载文件
        autoloadAdd($item);
        //同步版本号
        syncVersion($item['save_path'], $item['version']);
        //提交git
        gitCommit($dir, 'update 模块' . $module);
        echo 'update done:' . $module . PHP_EOL;
    }

    exit;
}

function syncVersion($savePath, $version)
{
    $file = dirname(__FILE__) . '/' . $savePath . '/version';
    file_put_contents($file, $version);
}

function autoloadCancel($item)
{
    global $autoloadFile;
    global $loaderFile;
    global $routerFile;

    //删除autoload.php里该文件的加载
    $file = $item['save_path'] . '/helpers.php';
    $file = "require_once ROOT . '/{$file}';";
    //判断是否已经加载
    $content = file_get_contents($autoloadFile);
    if (strstr($content, $file)) {
        $content = str_replace($file, '', $content);
        file_put_contents($autoloadFile, $content);
    }

    //删除loader.php里该文件的加载
    $file = $item['save_path'] . '/app/loader.php';
    $file = "require_once ROOT . '/{$file}';";
    //判断是否已经加载
    $content = file_get_contents($loaderFile);
    if (strstr($content, $file)) {
        $content = str_replace($file, '', $content);
        file_put_contents($loaderFile, $content);
    }

    //删除routes.php里该文件的加载
    $file = $item['save_path'] . '/app/routes.php';
    $file = "require_once ROOT . '/{$file}';";
    //判断是否已经加载
    $content = file_get_contents($routerFile);
    if (strstr($content, $file)) {
        $content = str_replace($file, '', $content);
        file_put_contents($routerFile, $content);
    }
}

function autoloadAdd($item)
{
    global $autoloadFile;
    global $loaderFile;
    global $routerFile;

    //检测包目录下面是否有helper.php文件
    //如果有加载文件
    //添加autoload.php里该文件的加载
    $file = $item['save_path'] . '/helpers.php';
    if (is_file('./' . $file)) {
        $file = "require_once ROOT . '/{$file}';";
        //判断是否已经加载
        $content = file_get_contents($autoloadFile);
        if (!strstr($content, $file)) {
            file_put_contents($autoloadFile, $file . PHP_EOL, FILE_APPEND);
        }
    }

    //检测包目录下面是否有app/loader.php文件
    //如果有加载文件
    //添加loader.php里该文件的加载
    $file = $item['save_path'] . '/app/loader.php';
    if (is_file('./' . $file)) {
        $file = "require_once ROOT . '/{$file}';";
        //判断是否已经加载
        $content = file_get_contents($loaderFile);
        if (!strstr($content, $file)) {
            file_put_contents($loaderFile, $file . PHP_EOL, FILE_APPEND);
        }
    }

    //检测包目录下面是否有app/routes.php文件
    //如果有加载文件
    //添加routes.php里该文件的加载
    $file = $item['save_path'] . '/app/routes.php';
    if (is_file('./' . $file)) {
        $file = "require_once ROOT . '/{$file}';";
        //判断是否已经加载
        $content = file_get_contents($routerFile);
        if (!strstr($content, $file)) {
            file_put_contents($routerFile, $file . PHP_EOL, FILE_APPEND);
        }
    }
}

function gitCommit($dir, $msg)
{
    passthru("cd $dir && git add .");
    passthru("git commit -m '" . $msg . "'");
    //passthru("git push");
}