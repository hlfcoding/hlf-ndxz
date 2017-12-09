<?php
// ini_set('display_errors', 1);
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

const DEV = 'dev';
const PROD = 'prod';
$app['env'] = getenv('APP_ENV') ?: DEV;
$app['root'] = __DIR__.'/../';
$app['config'] = require $app['root'].'config/'.$app['env'].'.php';

$app->register(new Silexhibit\DataBaseServiceProvider());

$app->register(new Silexhibit\DataAdapterServiceProvider());

$app->register(new Silexhibit\ThemeServiceProvider());

$app->mount('/', new Silexhibit\SiteControllerProvider());

return $app;
