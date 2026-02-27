<?php
/**
 * Script de déploiement pour hébergement sans SSH (o2switch lune).
 * Appelé automatiquement par GitHub Actions après l'upload FTP.
 *
 * SÉCURITÉ : Protégé par un token secret défini en variable d'environnement.
 * Ne jamais exposer ce fichier sans token.
 */

// ── Vérification du token de sécurité ────────────────────────────────────────
$expectedToken = getenv('DEPLOY_TOKEN') ?: '';

if (empty($expectedToken)) {
    http_response_code(500);
    die("DEPLOY_TOKEN non configuré dans .env");
}

$providedToken = $_GET['token'] ?? $_SERVER['HTTP_X_DEPLOY_TOKEN'] ?? '';

if (! hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    die("Token invalide");
}

// ── Configuration ─────────────────────────────────────────────────────────────
$appPath  = dirname(__DIR__); // remonte d'un cran depuis public/
$phpBin   = PHP_BINARY;       // chemin PHP automatique
$artisan  = $appPath . '/artisan';
$log      = [];
$hasError = false;

// ── Fonction d'exécution de commande ─────────────────────────────────────────
function runCmd(string $cmd): array
{
    $output    = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    return [
        'cmd'    => $cmd,
        'output' => implode("\n", $output),
        'ok'     => $returnCode === 0,
    ];
}

// ── Séquence de déploiement ───────────────────────────────────────────────────
$commands = [
    "Composer install"   => "{$phpBin} -d memory_limit=512M $(which composer) install --no-dev --no-interaction --optimize-autoloader --working-dir={$appPath}",
    "Migrations"         => "{$phpBin} {$artisan} migrate --force",
    "Config cache"       => "{$phpBin} {$artisan} config:cache",
    "Route cache"        => "{$phpBin} {$artisan} route:cache",
    "View cache"         => "{$phpBin} {$artisan} view:cache",
    "Event cache"        => "{$phpBin} {$artisan} event:cache",
    "Queue restart"      => "{$phpBin} {$artisan} queue:restart",
];

foreach ($commands as $label => $cmd) {
    $result = runCmd($cmd);
    $log[]  = [
        'step'   => $label,
        'ok'     => $result['ok'],
        'output' => $result['output'],
    ];
    if (! $result['ok']) {
        $hasError = true;
    }
}

// ── Réponse ───────────────────────────────────────────────────────────────────
http_response_code($hasError ? 500 : 200);
header('Content-Type: application/json');

echo json_encode([
    'success'   => ! $hasError,
    'timestamp' => date('Y-m-d H:i:s'),
    'steps'     => $log,
], JSON_PRETTY_PRINT);
