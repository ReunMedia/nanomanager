<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Reun\Nanomanager\Nanomanager;
use Slim\Factory\AppFactory;

$projectRoot = realpath(__DIR__.'/../../../../');

require __DIR__.'/../vendor/autoload.php';

$app = AppFactory::create();

$app->get('/', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    $args
) {
    $response->getBody()->write(
        file_get_contents(__DIR__.'/index.html')
    );

    return $response;
});

$app->any('/admin/nanomanager', function (
    ServerRequestInterface $request,
    ResponseInterface $response,
    $args
) use ($projectRoot) {
    $nanomanager = new Nanomanager(
        "{$projectRoot}/packages/php/tests/Integration/_uploads",
        'http://localhost:8080/uploads',
        'http://localhost:8080/admin/nanomanager',
    );

    $response->getBody()->write($nanomanager->run(
        $request->getMethod(),
        (string) $request->getBody(),
    ));

    return $response;
});

$app->run();
