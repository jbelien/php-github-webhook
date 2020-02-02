<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\ConfigAggregator\ConfigAggregator;
use Zend\ConfigAggregator\ZendConfigProvider;

class ConfigMiddleware implements MiddlewareInterface
{
    public const CONFIG_ATTRIBUTE = 'config';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cacheConfig = [
            'config_cache_path' => 'data/cache/app-config-cache.php',
        ];

        $config = new ConfigAggregator([
            new ZendConfigProvider(realpath(dirname(dirname(__DIR__))).'/composer.json'),
            new ZendConfigProvider(realpath(dirname(dirname(__DIR__))).'/config/application/*.{php,ini,xml,json,yaml}'),
        ], $cacheConfig['config_cache_path']);

        return $handler->handle($request->withAttribute(self::CONFIG_ATTRIBUTE, $config->getMergedConfig()));
    }
}
