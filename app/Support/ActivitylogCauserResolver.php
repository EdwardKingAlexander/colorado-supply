<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivitylogCauserResolver
{
    public function __invoke(mixed $subject = null): ?Model
    {
        return Auth::guard('admin')->user() ?? Auth::guard('web')->user();
    }
}
