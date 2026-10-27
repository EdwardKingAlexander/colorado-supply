<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A DLA-approved source (CAGE + part number) for an NSN, from a DIBBS as*.txt file.
 */
class DibbsApprovedSource extends Model
{
    protected $guarded = [];
}
