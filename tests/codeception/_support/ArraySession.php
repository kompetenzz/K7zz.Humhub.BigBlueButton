<?php

namespace bbb;

/**
 * In-memory session for unit tests — avoids calling session_set_save_handler()
 * which fails in CLI when a native PHP session is already active.
 */
class ArraySession extends \yii\web\Session
{
    private array $_data = [];
    private bool $_open  = false;

    public function open(): void
    {
        $this->_open = true;
    }

    public function close(): void
    {
        $this->_open = false;
    }

    public function destroy(): void
    {
        $this->_data = [];
        $this->_open = false;
    }

    public function getIsActive(): bool
    {
        return $this->_open;
    }

    public function get($key, $defaultValue = null): mixed
    {
        $this->open();
        return $this->_data[$key] ?? $defaultValue;
    }

    public function set($key, $value): void
    {
        $this->open();
        $this->_data[$key] = $value;
    }

    public function remove($key): mixed
    {
        $old = $this->_data[$key] ?? null;
        unset($this->_data[$key]);
        return $old;
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->_data);
    }

    public function removeAll(): void
    {
        $this->_data = [];
    }
}
