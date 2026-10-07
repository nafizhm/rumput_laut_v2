<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait HandlesTransaction
{
    /**
     * Execute a callback within a database transaction.
     *
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        return DB::transaction($callback, 3);
    }
}
