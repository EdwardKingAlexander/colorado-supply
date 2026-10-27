<?php

namespace App\Services\Dibbs;

use RuntimeException;

/**
 * DIBBS could not be reached or refused access (blocked, consent flow changed, unexpected response).
 */
class DibbsAccessException extends RuntimeException {}
