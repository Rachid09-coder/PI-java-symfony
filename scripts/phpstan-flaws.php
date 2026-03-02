#!/usr/bin/env php
<?php

/**
 * Affiche uniquement les défauts PHPStan dans le terminal.
 * Pour capture "avant optimisation" / "après optimisation".
 * Usage: composer report:flaws
 */

$root = dirname(__DIR__);
$sep = str_repeat('=', 72);

echo "\n" . $sep . "\n";
echo "  DÉFAUTS DÉTECTÉS PAR L'ANALYSE STATIQUE (PHPStan)\n";
echo "  Niveau 6 - Rapport PIDEV Sprint WEB\n";
echo $sep . "\n\n";

$phpstan = $root . '/vendor/bin/phpstan';
if (!is_file($phpstan)) {
    echo "Exécutez: composer install\n\n";
    exit(1);
}

$php = PHP_BINARY ?: 'php';
chdir($root);
// Ne pas passer de chemin : PHPStan utilise phpstan.neon.dist (paths: src, tests uniquement)
passthru(sprintf('"%s" "%s" analyse --memory-limit=512M --no-progress 2>&1', $php, $phpstan), $code);
exit((int) $code);
