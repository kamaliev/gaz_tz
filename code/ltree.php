<?php

/**
 * @var $data array
 * @var $dbh PDO
 */

include 'array.php';
include 'dbh.php';

echo '<html><head><title>Simple</title></head><body>';

$dbh->query('truncate table tree restart identity;');

foreach ($data as $item) {
    $stmt = $dbh->prepare('INSERT INTO tree (path, title, price) VALUES (:path, :title, :price)');
    $stmt->bindParam(':path', $item[0]);
    $stmt->bindParam(':title', $item[1]);
    $stmt->bindParam(':price', $item[2]);
    $stmt->execute();
}

/**
 * Рекурсивное построение дерева
 */
$stmt = $dbh->query('
WITH RECURSIVE nodes(path, title, price, nlevel) AS (
    SELECT t1.path::text, t1.title, trim(to_char(t1.price, \'99999D99\')), nlevel(t1.path)
    FROM tree t1
    UNION
    SELECT t2.path::text, t2.title, trim(to_char(t2.price, \'99999D99\')), nlevel(t2.path)
    FROM tree t2, tree t1 WHERE t1.path @> t2.path
)
SELECT lpad(path || \' - \' || title || \' - \' || price, length(path) + length(title) + length(price) + nlevel::integer + 5, \' \') FROM nodes
ORDER BY path ASC;
');

echo '<h2>LTREE + рекурсия + форматирование</h2>';

echo '<pre>';

while ($row = $stmt->fetch()) {
    echo $row['lpad'] . PHP_EOL;
}

echo '</pre>';

$stmt = $dbh->query('
WITH RECURSIVE nodes(id, path, title, price, parent_id) AS (
    SELECT t1.id, t1.path, t1.title, t1.price, 0 AS parent_id
    FROM tree t1 WHERE nlevel(t1.path) = 1
    UNION
    SELECT t2.id, t2.path, t2.title, t2.price, t1.id AS parent_id
    FROM tree t2, tree t1 WHERE t1.path @> t2.path AND t1.id <> t2.id AND subpath(t2.path, 0, nlevel(t2.path) - 1) @> t1.path
)
SELECT * FROM nodes
ORDER BY path ASC;
');

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
            $html .= '<li style="list-style-type: none;">' .  $item['path'] . ' - ' . $item['title'] . ' - ' . $item['price'];
            $html .= build_tree_ul($tree, $item['id']);
            $html .= '</li>';
        }

        $html .= '</ul>';
    } else {
        return false;
    }

    return $html;
}

$tree = form_tree($array);

echo '<h2>UL список</h2>';
echo build_tree_ul($tree, 0);

echo '</body></html>';