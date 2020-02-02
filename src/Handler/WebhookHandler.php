<?php

declare(strict_types=1);

namespace App\Handler;

use App\Middleware\ConfigMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Zend\Diactoros\Response\TextResponse;

class WebhookHandler implements RequestHandlerInterface
{
    private $config;
    private $delivery;
    private $event;
    private $payload;
    private $signature;

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->config = $request->getAttribute(ConfigMiddleware::CONFIG_ATTRIBUTE);
        $this->payload = $request->getParsedBody();

        $headers = $request->getHeaders();

        $this->event = $headers['x-github-event'][0] ?? null;
        $this->delivery = $headers['x-github-delivery'][0] ?? null;
        $this->signature = $headers['x-hub-signature'][0] ?? '';

        if (isset($this->config['token']) && !empty($this->config['token'])) {
            $sha1 = hash_hmac('sha1', (string) $request->getBody(), $this->config['token']);

            if (hash_equals('sha1='.$sha1, $this->signature) !== true) {
                return new TextResponse('ERROR: Invalid signature!', 401);
            }
        }

        switch ($this->event) {
            case 'ping':
                return $this->ping();
                break;

            case 'push':
                return $this->push();
                break;

            case 'release':
                return $this->release();
                break;

            default:
                return new TextResponse('ERROR: This webhook only supports PING, PUSH, and RELEASE events!', 501);
                break;
        }
    }

    private function ping()
    {
        return new TextResponse('pong');
    }

    private function push()
    {
        $repository = $this->payload['repository']['full_name'];
        $branch = substr($this->payload['ref'], 11);

        $out = 'DELIVERY: '.$this->delivery.PHP_EOL;
        $out .= 'BY: '.$this->payload['pusher']['name'].PHP_EOL;
        $out .= '--------------------------------------------------'.PHP_EOL;

        foreach ($this->config['endpoints'] as $endpoint) {
            if ($endpoint['repository'] === $repository && $endpoint['branch'] === $branch) {
                $out .= 'REPOSITORY: '.$repository.PHP_EOL;
                $out .= 'BRANCH: '.$branch.PHP_EOL;
                $out .= '--------------------------------------------------'.PHP_EOL;

                if (!is_array($endpoint['run'])) {
                    $endpoint['run'] = [$endpoint['run']];
                }

                $status = 200;
                foreach ($endpoint['run'] as $i => $run) {
                    $process = new Process($run);

                    $out .= '['.($i + 1).'] '.$run.PHP_EOL;

                    try {
                        $process->mustRun();

                        $out .= $process->getOutput().PHP_EOL;
                    } catch (ProcessFailedException $exception) {
                        $status = 500;

                        $out .= $exception->getMessage().PHP_EOL;
                    }

                    $out .= '--------------------------------------------------'.PHP_EOL;
                }

                return new TextResponse($out, $status);
            }
        }

        return new TextResponse(sprintf('WARNING: No endpoint found for "%s" (branch "%s")!', $repository, $branch), 404);
    }

    private function release()
    {
        $repository = $this->payload['repository']['full_name'];

        $out = 'DELIVERY: '.$this->delivery.PHP_EOL;
        $out .= 'BY: '.$this->payload['release']['author']['login'].PHP_EOL;
        $out .= '--------------------------------------------------'.PHP_EOL;

        foreach ($this->config['endpoints'] as $endpoint) {
            if ($endpoint['repository'] === $repository) {
                $out .= 'REPOSITORY: '.$repository.PHP_EOL;
                $out .= 'RELEASE: '.($this->payload['release']['name'] ?? '').' ('.$this->payload['release']['tag_name'].')'.PHP_EOL;
                $out .= '--------------------------------------------------'.PHP_EOL;

                if (!is_array($endpoint['run'])) {
                    $endpoint['run'] = [$endpoint['run']];
                }

                $status = 200;
                foreach ($endpoint['run'] as $i => $run) {
                    $process = new Process($run);

                    $out .= '['.($i + 1).'] '.$run.PHP_EOL;

                    try {
                        $process->mustRun();

                        $out .= $process->getOutput().PHP_EOL;
                    } catch (ProcessFailedException $exception) {
                        $status = 500;

                        $out .= $exception->getMessage().PHP_EOL;
                    }

                    $out .= '--------------------------------------------------'.PHP_EOL;
                }

                return new TextResponse($out, $status);
            }
        }

        return new TextResponse(sprintf('WARNING: No endpoint found for "%s"!', $repository, $branch), 404);
    }
}
