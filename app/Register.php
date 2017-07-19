<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    /**
     * State when closed
     */
    const STATE_CLOSED = 0;
    /**
     * State when opened
     */
    const STATE_OPENED = 1;
}
