<?php
# A*

return function (array $map, array $config = []): array
{
    $config += ['dirs' => DIRS_4];
    $start = microtime(true);
    ['X' => $initialPosition, 'O' => $goal] = findActors($map);

    $result = $map;

    $frontier = new SplPriorityQueue();
    $frontier->insert($initialPosition, 0);
    $from = [computeKeyName($initialPosition) => false];
    $cost = [computeKeyName($initialPosition) => 0];

    $i = 0; $current = null;
    while (!empty($frontier)) {
        $i++;
        $current = $frontier->extract();
        $currentKey = computeKeyName($current);

        if (TARGET_CHAR === $current['char']) {
            //early exit
            break;
        }
        foreach (neighbors($current, $result, $config['dirs']) as $neighbor) {
            $key = computeKeyName($neighbor);
            $newCost = $cost[$currentKey] + dijkstraCost($current, $neighbor);
            if (!isset($cost[$key]) || $newCost < $cost[$key]) {
                $neighbor += ['p' => $current];
                $cost[$key] = $newCost;
                $c = $newCost + greedyHeuristic($goal, $current);
                ' ' === $result[$neighbor['x']][$neighbor['y']]['char'] && $result[$neighbor['x']][$neighbor['y']]['c'] = $c; //debug
                $frontier->insert($neighbor, - $c); //inverse priority
                $from[$key] = $current;
            }
        }
    }

    return extractPath($from, $lastKey = computeKeyName($current)) + ['map' => $result, 'time' => microtime(true) - $start, 'iterations' => $i];
};