<?php

namespace App\Services\Dibbs;

use RuntimeException;

/**
 * A DIBBS file does not match the documented layout (likely a format change by DLA).
 */
class DibbsLayoutException extends RuntimeException {}
