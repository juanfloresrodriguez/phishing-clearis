<?php

/**
 * Bridge para Plesk cuando el Document Root no apunta a public/.
 * Si puedes cambiar el Document Root en Plesk a httpdocs/public/,
 * este fichero no es necesario y puedes eliminarlo.
 */
define('LARAVEL_START', microtime(true));

require __DIR__ . '/public/index.php';
