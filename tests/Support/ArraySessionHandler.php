<?php

namespace airmoi\FileMaker\Tests\Support;

/**
 * In-memory session handler compatible with FileMaker::setSessionHandler()
 */
class ArraySessionHandler
{
    public array $data = [];

    public function get($key)
    {
        return $this->data[$key] ?? null;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return true;
    }
}
