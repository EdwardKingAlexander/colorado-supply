<?php

namespace App\Models\Concerns;

use App\Services\PubLog\PubLogDb;

trait UsesPubLogConnection
{
    public function getConnectionName()
    {
        return PubLogDb::name() ?? parent::getConnectionName();
    }
}
