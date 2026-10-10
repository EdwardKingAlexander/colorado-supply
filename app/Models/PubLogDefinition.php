<?php

namespace App\Models;

use App\Models\Concerns\UsesPubLogConnection;
use Illuminate\Database\Eloquent\Model;

class PubLogDefinition extends Model
{
    use UsesPubLogConnection;

    public $timestamps = false;

    protected $guarded = [];
}
