#!/usr/bin/env php
<?php

/**
 * Rapport qualité code - PIDEV Sprint WEB
 * Affiche dans le terminal les défauts (PHPStan) et les tests (PHPUnit)
 * pour captures "avant optimisation" et "après optimisation".
 *
 * Usage: php scripts/quality-report.php
 *    ou: composer report:quality
 */

$root = dirname(__DIR__);
$separator = str_repeat('=', 72);

echo "\n";
echo $separator . "\n";
echo "  RAPPORT QUALITÉ CODE - ANALYSE STATIQUE (PHPStan)\n";
echo "  Défauts / erreurs détectés dans le code\n";
echo $separator . "\n\n";

$phpstan = $root . '/vendor/bin/phpstan';
if (!is_file($phpstan)) {
    echo "PHPStan non installé. Exécutez: composer install --dev\n\n";
    exit(1);
}

$php = PHP_BINARY ?: 'php';
chdir($root);
// Ne pas passer de chemin : PHPStan utilise phpstan.neon.dist (paths: src, tests uniquement)
passthru(
    sprintf('"%s" "%s/vendor/bin/phpstan" analyse --memory-limit=512M --no-progress 2>&1', $php, $root),
    $phpstanExit
);

echo "\n";
echo $separator . "\n";
echo "  TESTS UNITAIRES (PHPUnit)\n";
echo $separator . "\n\n";

$phpunit = $root . '/vendor/bin/phpunit';
if (!is_file($phpunit)) {
    echo "PHPUnit non installé.\n\n";
    exit(1);
}

chdir($root);
passthru(
    sprintf(
        '"%s" "%s" tests/Unit -c phpunit.dist.xml --testdox 2>&1',
        $php,
        $phpunit
    ),
    $phpunitExit
);

echo "\n";
echo $separator . "\n";
echo "  FIN DU RAPPORT QUALITÉ\n";
echo $separator . "\n\n";

exit($phpstanExit !== 0 ? (int) $phpstanExit : (int) $phpunitExit);
