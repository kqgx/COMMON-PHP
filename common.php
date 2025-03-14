<?php

/**
 * 将字符串转换为数组
 */
function strtoarr($data)
{
    if (is_array($data)) {
        return $data;
    }

    if (!isset($data) || !is_string($data) || !strlen($data)) {
        return [];
    }

    if (strpos($data, 'a:') === 0) {
        return @unserialize(stripslashes($data)) ?: [];
    }

    if (strpos($data, '[') === 0 || strpos($data, '{') === 0) {
        return @json_decode($data, true) ?: [];
    }

    return explode(',', $data);
}

/**
 * 平面数据转换为树状结构
 * 
 * @param array $list 平面数据数组
 * @param string $children_name 子节点的键名，默认为 'children'
 * @param string $id_name 标识每个节点的唯一 ID 的键名，默认为 'id'
 * @param string $pid_name 标识父节点 ID 的键名，默认为 'pid'
 * @return array 树状结构数组
 */
function list_to_tree(array $list, string $children_name = 'children', string $id_name = 'id', string $pid_name = 'pid')
{
    // 将数据转换为以 id 为键的关联数组
    $items = array_column($list, null, $id_name);

    // 构建树状结构
    $tree = [];

    foreach ($items as $item) {
        if (!$item[$pid_name]) {
            $tree[] = &$items[$item[$id_name]];
        } else {
            $items[$item[$pid_name]][$children_name][] = &$items[$item[$id_name]];
        }
    }

    return $tree;
}

/**
 * 递归合并两个多维数组
 * 
 * 合并规则：
 * 1. 如果第二个数组中的值是索引数组（数字键）或空数组，直接替换第一个数组中对应的值
 * 2. 如果第二个数组中的值是关联数组，则递归合并
 * 3. 如果是非数组值，直接用第二个数组的值替换第一个数组的值
 * 4. 第一个数组中存在但第二个数组中不存在的键值对保持不变
 * 
 * @param array $array1 被合并的原数组
 * @param array $array2 用于合并的数组
 * @param int $maxDepth 最大递归深度，默认为10
 * @return array 合并后的数组
 * @throws \InvalidArgumentException 当参数类型错误时
 * @throws \RuntimeException 当递归深度超过限制时
 */
function array_merge_recursive_custom($array1, $array2, $max_depth = 10, $current_depth = 0)
{
    if (!is_array($array1) || !is_array($array2)) {
        throw new \InvalidArgumentException('参数必须是数组类型');
    }

    if ($current_depth > $max_depth) {
        throw new \RuntimeException('数组嵌套深度超过限制');
    }

    foreach ($array2 as $key => $value) {
        // 如果是数组则需要判断是关联数组还是索引数组
        if (is_array($value)) {
            // 如果是空数组或索引数组，直接替换
            if (empty($value) || array_keys($value) === range(0, count($value) - 1)) {
                $array1[$key] = $value;
            } else {
                // 如果是关联数组，进行递归合并
                if (!isset($array1[$key]) || !is_array($array1[$key])) {
                    $array1[$key] = array();
                }
                $array1[$key] = array_merge_recursive_custom(
                    $array1[$key],
                    $value,
                    $max_depth,
                    $current_depth + 1
                );
            }
        }
        // 如果不是数组则直接替换
        else {
            $array1[$key] = $value;
        }
    }

    return $array1;
}
