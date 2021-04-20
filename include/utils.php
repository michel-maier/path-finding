<?php

const X_LENGTH_EMPTY = 100;
const Y_LENGTH_EMPTY = 20;
const INITIAL_POSITION_CHAR = 'X';
const TARGET_CHAR = 'O';
const WALL_CHAR = '#';
const ANALYSED_CHAR = '.';
const MAP_ELEMENTS = [' ', WALL_CHAR, TARGET_CHAR, INITIAL_POSITION_CHAR];
const ACTORS = [TARGET_CHAR, INITIAL_POSITION_CHAR];
const DIRS_4 = [[1, 0], [-1, 0], [0, -1], [0, 1]];
const DIRS_8 = [[-1, -1], [-1, 1], [1, -1], [1, 1], [1, 0], [0, -1], [-1, 0], [0, 1]];

function computeKeyName(array $cell): string
{
    return sprintf('%sX%s', $cell['x'], $cell['y']);
}

function neighbors(array $node, array $map, $dirs): array
{
    $dirs = ($node['x'] + $node['y']) % 2 ? array_reverse($dirs) : $dirs;
    $neighbors = [];
    foreach ($dirs as $dir) {
        $x = $node['x'] + $dir[0];
        $y = $node['y'] + $dir[1];
        if (isset($map[$x][$y]) && $map[$x][$y]['traversable'])
            $neighbors[] = $map[$x][$y];
    }

    return $neighbors;
}

function findActors(array $map): array
{
    $toFind = ACTORS;
    $founds = [];
    foreach ($map as $x) {
        foreach ($x as $y) {
            foreach ($toFind as $f) {
                if ($y['char'] === $f) {
                    $founds[$f] = $y;
                    unset($toFind[array_search($f, $toFind)]);
                }
                if (empty($toFind)) {
                    return $founds;
                }
            }
        }
    }

    die(sprintf('Cannot find all positions. Missing %s', implode(', ', array_diff($toFind, $founds))));
}

function displayMap(array $map, array $layer = []): void
{
    riffle2dimension($map, function ($map, $cpt) use ($layer) {
        echo $layer[$cpt['x']][$cpt['y']]['char'] ?? $map[$cpt['x']][$cpt['y']]['char'];
    });
}

function debugMap(array $map, string $prop = 'd', int $cellWidth = 4): void
{
    riffle2dimension($map, function ($map, $cpt) use ($prop, $cellWidth) {
        if(isset($map[$cpt['x']][$cpt['y']][$prop])) {
            echo str_pad(PHP_INT_MAX === $map[$cpt['x']][$cpt['y']][$prop] ? 'INF' : $map[$cpt['x']][$cpt['y']][$prop], $cellWidth);
            return;
        }
        echo str_pad($map[$cpt['x']][$cpt['y']]['char'], $cellWidth);
    });
}

function riffle2dimension(array $array, closure $command): void
{
    $cx = count($array);
    $cy = count($array[0]);
    for ($y = 0; $y < $cy; $y++) {
        for ($x = 0; $x < $cx; $x++) {
            $command($array, ['x' => $x, 'y' => $y]);
        }
        echo PHP_EOL;
    }
}

function emptyMap(): array
{
    $map = [];
    for ($x = 0; $x < X_LENGTH_EMPTY; $x++) {
        $map[$x] = [];
        for ($y = 0; $y < Y_LENGTH_EMPTY; $y++) {
            $map[$x][$y] = ['x' => $x, 'y' => $y, 'char' => ' '];
        };
    }

    return $map;
}

function getMapFromFile(string $name): array
{
    $handle = fopen(__DIR__ . "/../map/$name.map", "r");
    if (!$handle) {
        throw new Exception('Error on file opening !');
    }

    $map = [];
    $y = 0;
    $length = null;
    while (($line = fgets($handle)) !== false) {
        $line = preg_replace(sprintf('/[^%s]/', implode('', array_map(function ($e) {
            return preg_quote($e);
        }, MAP_ELEMENTS))), '', $line);
        !$length && $length = strlen($line);
        for ($x = 0; $x < $length; $x++) {
            if (in_array($line[$x], MAP_ELEMENTS)) {
                $map += [$x => []];
                $map[$x][$y] = ['x' => $x, 'y' => $y, 'char' => $line[$x], 'traversable' => WALL_CHAR !== $line[$x]];
            }
        }
        $y++;
    }
    fclose($handle);

    return $map;
}

function extractPath(array $nodes, $lastKey): array
{
    $target = $nodes[$lastKey];
    $path = [$target];
    $layer = [];
    do {
        $path[] = $target;
        $layer[$target['x']][$target['y']] = ['char' => ' ' === $target['char'] ? ANALYSED_CHAR : $target['char']] + $target;
    } while($target = $nodes[computeKeyName($target)]);

    return ['path' => $path, 'layer' => $layer];
}

function choicesFromDirectory(string $directory): array
{
    return array_map(function ($f) {
        return pathinfo($f)['filename'];
    }, array_diff(scandir($directory), ['.', '..']));
}

function displayChoices(array $choices): void
{
    foreach ($choices as $choice) {
        printf('- %s', $choice . PHP_EOL);
    }
}

function ask(array $choices): string
{
    do {
        printf('> ');
        fscanf(STDIN, '%s', $input);
    } while (!in_array($input, $choices));

    return $input;
}

function askClosed(string $label): bool
{
    printf(sprintf('%s (y/n)', $label));

    return ask(['y', 'n']) === 'y';
}

function compareAll(array $map, bool $dryRun = false): bool {
    if(!$dryRun) echo str_pad('ALGO NAME', 30) . str_pad('TIME', 20) . str_pad('NB PATHS', 20) . str_pad('ITERATIONS', 20) . PHP_EOL;
    foreach(choicesFromDirectory(__DIR__ . '/../algo') as $algoName) {
        $algo = require(__DIR__ . "/../algo/$algoName.php");
        $result = $algo($map, ['dirs' => DIRS_4]);

        if(!$dryRun) echo str_pad($algoName, 30) . str_pad($result['time'] * 1000, 20) . str_pad(count($result['path']), 20) . str_pad($result['iterations'], 20) . PHP_EOL;
    }
    return true;
}

function arrow(array $target, array $ancestor): string
{
    $char = 'U';
    ($target['x'] > $ancestor['x']) && ($target['y'] > $ancestor['y']) && $char = '↘';
    ($target['x'] < $ancestor['x']) && ($target['y'] < $ancestor['y']) && $char = '↖';
    ($target['x'] > $ancestor['x']) && ($target['y'] < $ancestor['y']) && $char = '↗';
    ($target['x'] < $ancestor['x']) && ($target['y'] > $ancestor['y']) && $char = '↙';
    ($target['x'] > $ancestor['x']) && ($target['y'] === $ancestor['y']) && $char = '→';
    ($target['x'] < $ancestor['x']) && ($target['y'] === $ancestor['y']) && $char = '←';
    ($target['x'] === $ancestor['x']) && ($target['y'] < $ancestor['y']) && $char = '↑';
    ($target['x'] === $ancestor['x']) && ($target['y'] > $ancestor['y']) && $char = '↓';

    return $char;
}

function dijkstraCost(array $current, array $next): int
{
    return isDiagonalMove($current, $next) ? 2 : 1;
}

function isDiagonalMove(array $current, array $next)
{
    return in_array([$current['x'] - $next['x'], $current['y'] - $next['y']], [[-1, -1], [-1, 1], [1, -1], [1, 1]]);
}

function greedyHeuristic(array $goal, array $current): int
{
    // Manhattan distance use
    return abs($goal['x'] - $current['x']) + abs($goal['y'] - $current['y']);
}