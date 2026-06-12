<?php

namespace Backtik\KChat\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Backtik\KChat\KChat
 */
class KChat extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Backtik\KChat\KChat::class;
    }
}
