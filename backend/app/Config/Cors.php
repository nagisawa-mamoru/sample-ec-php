<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    /**
     * APIへのアクセスを許可するオリジン一覧。
     * 開発時はVite dev serverのオリジンを許可する。
     */
    public array $allowedOrigins = [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ];
}
