<?php
# Greedy Best First Search

return function (array $map, array $config = []): array
{
    $config += ['dirs' => DIRS_4];
    $start = microtime(true);
    ['X' => $initialPosition, 'O' => $goal] = findActors($map);

    $result = $map;

    $frontier = new SplPriorityQueue();
    $frontier->insert($initialPosition, 0);
    $from = [computeKeyName($initialPosition) => false];

    $i = 0; $current = null;
    while (!$frontier->isEmpty()) {
        $i++;
        $current = $frontier->extract();
        if (TARGET_CHAR === $current['char']) {
            //early exit
            break;
        }

        foreach (neighbors($current, $result, $config['dirs']) as $neighbor) {
            $key = computeKeyName($neighbor);
            $h = greedyHeuristic($goal, $current);
            if(!isset($from[$key])) {
                ' ' === $result[$neighbor['x']][$neighbor['y']]['char'] && $result[$neighbor['x']][$neighbor['y']]['h'] = $h; //debug
                $frontier->insert($neighbor, - $h); //inverse priority
                $from[$key] = $current;
            }
        }
    }

    return extractPath($from, $lastKey = computeKeyName($current)) + ['map' => $result, 'time' => microtime(true) - $start, 'iterations' => $i];
};