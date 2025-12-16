#!/bin/bash

# 更新组件库
# Date: 2022-02-02
# Version: 1.0.0

CONFIG="comp.ini"
WORKING_DIR=$(pwd)                # 实际工作目录
AUTOLDFILE="${WORKING_DIR}/comp/autoload.php"
LOADERFILE="${WORKING_DIR}/comp/loader.php"
ROUTERFILE="${WORKING_DIR}/comp/router.php"

if [ "$#" -lt 1 ] || [[ ! "$1" =~ ^(update|delete)$ ]]; then
    echo "参数缺失或不正确，传参：update 安装/更新 或 delete 删除"
    exit 1
fi

ACTION="$1"
MODULE="$2"

function parse_ini {
    # 这里使用 sed 命令替代 awk 来解析 INI 文件，并且直接删除双引号
    local key=$1
    local section=$2
    local value=$(sed -n "/^\[$section\]/,/^\[/ { /^$key=/ s/^$key=['\"]\(.*\)['\"].*$/\1/p;}" "$CONFIG")
    echo "$value"
}

function update_module() {
    local module=$1

    local save_path=$(parse_ini "save_path" "$module")
    local git_remote=$(parse_ini "git_remote" "$module")
    local pull_path=$(parse_ini "pull_path" "$module")
    local version=$(parse_ini "version" "$module")

    local dir="${WORKING_DIR}/${save_path}"
    local version_file="${dir}/version"

    if [ -f "$version_file" ]; then
        local_version=$(cat "$version_file")
    fi

    if [ -n "$version" ] && [ "$version" == "$local_version" ]; then
        echo "版本一致无需更新：$version"
        return 0
    fi

    rm -rf "$dir" && mkdir -p "$dir"

    (
        cd "$dir" || exit
        git init
        git config core.sparsecheckout true

        if [ -n "$pull_path" ]; then
            echo "/${pull_path}/" >> .git/info/sparse-checkout
        fi

        git remote add origin "$git_remote"

        if [ -n "$version" ]; then
            git fetch origin "refs/tags/${version}:refs/tags/${version}"
            git checkout "tags/$version" > /dev/null 2>&1
        else
            git pull --depth 1 origin master
        fi

        rm -rf .git

        if [ -n "$pull_path" ]; then
            mv "${pull_path}"/* ./
            rm -rf "${pull_path}"
        fi

        sync_version "$version"
        autoload_add "$save_path" "$AUTOLDFILE"
    )

    git_commit "$dir" "update 模块 ${module}"
    echo "Module ${module} updated"
}

function autoload_add() {
    local save_path=$1
    local autoload_file=$2

    # 拼接可能存在的 autoload 文件路径
    local autoload_php="$WORKING_DIR/$save_path/helpers.php"
    local loader_php="$WORKING_DIR/$save_path/app/loader.php"
    local routes_php="$WORKING_DIR/$save_path/app/routes.php"

    # 处理 helpers.php
    if [ -f "$autoload_php" ]; then
        local require_statement="require_once ROOT . '/$save_path/helpers.php';"
        if ! grep -Fxq "$require_statement" "$AUTOLDFILE"; then
            echo "$require_statement" >> "$AUTOLDFILE"
        fi
    fi

    # 处理 loader.php
    if [ -f "$loader_php" ]; then
        local require_statement="require_once ROOT . '/$save_path/app/loader.php';"
        if ! grep -Fxq "$require_statement" "$LOADERFILE"; then
            echo "$require_statement" >> "$LOADERFILE"
        fi
    fi

    # 处理 routes.php
    if [ -f "$routes_php" ]; then
        local require_statement="require_once ROOT . '/$save_path/app/routes.php';"
        if ! grep -Fxq "$require_statement" "$ROUTERFILE"; then
            echo "$require_statement" >> "$ROUTERFILE"
        fi
    fi
}

function delete_module() {
    local module=$1

    local save_path=$(parse_ini "save_path" "$module")
    local dir="${WORKING_DIR}/${save_path}"

    echo "Deleting $module ..."
    rm -rf "$dir" && echo "Deleted $save_path"

    # Removing autoload entries
    remove_autoload_entry "$AUTOLDFILE" "$save_path/helpers.php"
    remove_autoload_entry "$LOADERFILE" "$save_path/app/loader.php"
    remove_autoload_entry "$ROUTERFILE" "$save_path/app/routes.php"

    # 获取当前目录($dir)的上一级目录
    local parent_dir=$(dirname "$dir")
    git_commit "$parent_dir" "delete 模块 ${module}"
    echo "Module ${module} deleted"
}

function sync_version() {
    local version=$1
    echo "$version" > "${WORKING_DIR}/$save_path/version"
}

function remove_autoload_entry() {
    local file=$1
    local pattern=$2
    # Ensuring we escape slashes for sed usage and use a more specific pattern to prevent accidental deletion
    escape_pattern=$(printf '%s\n' "$pattern" | sed 's:[\\/&]:\\&:g;$!s/$/\\/')
    sed -i "/^require_once ROOT . '\/${escape_pattern}';$/d" "$file"
}

function git_commit() {
    local dir=$1
    local msg=$2

    (cd "$dir" && git add . && git commit -m "$msg")
    # 此处未执行 git push，根据实际情况考虑是否需要
}

# Check if module exists in the configuration
function check_module_exists() {
    local module=$1
    if ! grep -q "^\[$module\]" "$CONFIG"; then
        echo "该模块未配置：$module"
        exit 1
    fi
}

# 主执行逻辑
if [ "$MODULE" == "all" ]; then
    while IFS= read -r line || [[ -n "$line" ]]; do
        # Capture module name using regex in bash
        if [[ "$line" =~ ^\[([a-zA-Z0-9_-]+)\]$ ]]; then
            MODULE="${BASH_REMATCH[1]}"
            "${ACTION}_module" "$MODULE"
        fi
    done < "$CONFIG"
elif [ -n "$MODULE" ]; then
    check_module_exists "$MODULE"
    "${ACTION}_module" "$MODULE"
else
    echo "单独的模块名称没有提供。"
    exit 1
fi
