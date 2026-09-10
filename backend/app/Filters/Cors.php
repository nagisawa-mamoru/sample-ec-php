<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Vue3(Vite dev server)からのAPIアクセスを許可するためのCORSフィルタ。
 *
 * 許可オリジンは app/Config/Cors.php で管理する。
 */
class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config         = config('Cors');
        $allowedOrigins = $config->allowedOrigins;
        $origin         = $request->getHeaderLine('Origin');

        $response = service('response');

        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        // プリフライトリクエストはこの時点で応答を返す。
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $response->setStatusCode(204);
        }

        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $config         = config('Cors');
        $allowedOrigins = $config->allowedOrigins;
        $origin         = $request->getHeaderLine('Origin');

        if ($origin !== '' && in_array($origin, $allowedOrigins, true)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin);
            $response->setHeader('Vary', 'Origin');
        }

        return $response;
    }
}
