<?php

/**
 * @var $data array
 * @var $dbh PDO
 */

include 'array.php';
include 'dbh.php';

echo '<html><head><title>Simple</title></head><body>';

$dbh->query('truncate table simple restart identity cascade;');

$array = [];

/**
 * Сортировка по ключу по возрастанию
 * для удобного восприятия
 */
$positions = array_column($data, 0);
array_multisort($positions, SORT_ASC, $data);

/**
 * Метод который строит "префиксное" дерево
 * используя поле positions
 *
 * @param array $array
 * @param array $keys
 * @param array $item
 */
function get_tree(array &$array, array &$keys, array $item) {
    $key = array_shift($keys);

    if (empty($keys)) {
        $array[$key]['data'] = [
            'position' => $item[0],
            'title' => $item[1],
            'price' => $item[2],
        ];
    } else {
        if (!isset($array[$key]['sub']))$array[$key]['sub'] = [];
        get_tree($array[$key]['sub'], $keys, $item);
    }
}

foreach ($data as $item) {
    $keys = explode('.', $item[0]);

    get_tree($array, $keys, $item);
}

echo '<h2>Массив в виде "префиксного" дерева</h2>';
echo '<pre>';
print_r($array);
echo '</pre>';

/**
 * Записываем значение из массива в таблицу simple
 *
 * @param array $item
 * @param PDO $dbh
 * @param int|null $parent
 */
function insert_into(array $item, PDO $dbh, int $parent = null) {
    $data = $item['data'];
    $stmt = $dbh->prepare('INSERT INTO simple (position, title, price, parent_id) VALUES (:position, :title, :price, :parent_id)');

    $stmt->bindParam(':position', $data['position']);
    $stmt->bindParam(':title', $data['title']);
    $stmt->bindParam(':price', $data['price']);
    $stmt->bindParam(':parent_id', $parent);
    $stmt->execute();

    $parent = $dbh->lastInsertId();

    if (isset($item['sub'])) {
        $subs = $item['sub'];

        foreach ($subs as $sub) {
            insert_into($sub, $dbh, $parent);
        }
    }
}

foreach ($array as $item) {
    insert_into($item, $dbh);
}

$stmt = $dbh->query('SELECT * FROM simple');

$array = $stmt->fetchAll();

function form_tree($mess)
{
    $tree = [];

    foreach ($mess as $value) {
        $value['parent_id'] = $value['parent_id'] ?? 0;
        $tree[$value['parent_id']][] = $value;
    }

    return $tree;
}

function build_tree_ul($tree, $parent_id)
{
    if (is_array($tree) && isset($tree[$parent_id])) {
        $html = '<ul>';

        foreach ($tree[$parent_id] as $item) {
            $html .= '<li style="list-style-type: none;">' .  $item['position'] . ' - ' . $item['title'] . ' - ' . $item['price'];
            $html .= build_tree_ul($tree, $item['id']);
            $html .= '</li>';
        }

        $html .= '</ul>';
    } else {
        return false;
    }

    return $html;
}

function build_tree_ol($tree, $parent_id)
{
    if (is_array($tree) && isset($tree[$parent_id])) {
        $html = '<ol>';

        foreach ($tree[$parent_id] as $item) {
            $html .= '<li>' . $item['title'] . ' - ' . $item['price'];
            $html .= build_tree_ol($tree, $item['id']);
            $html .= '</li>';
        }

        $html .= '</ol>';
    } else {
        return false;
    }

    return $html;
}

$tree = form_tree($array);

echo '<h2>OL список</h2>';

echo build_tree_ol($tree, 0);

echo '<h2>UL список</h2>';
echo build_tree_ul($tree, 0);

echo '</body></html>';