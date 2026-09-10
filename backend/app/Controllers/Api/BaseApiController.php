<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

abstract class BaseApiController extends BaseController
{
    use ResponseTrait;
}
