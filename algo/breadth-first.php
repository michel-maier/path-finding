<?php
# Breadth first search

return function (array $map, array $config = []): array {
    $config += ['dirs' => DIRS_4];
    $start = microtime(true);
    ['X' => $initialPosition] = findActors($map);

    $result = $map;

    $frontier = new SplQueue();
    $frontier[] = $initialPosition;
    $from = [computeKeyName($initialPosition) => false];

    $i = 0; $current = null;
    while (!$frontier->isEmpty()) {
        $i++;
        $current = $frontier->shift();
        if (TARGET_CHAR === $current['char']) {
            //early exit
            break;
        }

        foreach (neighbors($current, $result, $config['dirs']) as $neighbor) {
            $key = computeKeyName($neighbor);
            if (!isset($from[$key])) {
                ' ' === $result[$neighbor['x']][$neighbor['y']]['char'] && $result[$neighbor['x']][$neighbor['y']]['d'] = arrow($neighbor, $current); //result
                $frontier[] = $neighbor;
                $from[$key] = $current;
            }
        }
    }

    return extractPath($from, $lastKey = computeKeyName($current)) + ['map' => $result, 'time' => microtime(true) - $start, 'iterations' => $i];
};