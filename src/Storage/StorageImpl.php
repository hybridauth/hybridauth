<?php

namespace Hybridauth\Storage;

class StorageImpl implements StorageInterface {
    private $data;

    public function __construct()
    {
        $this->data = [];
    }

    public function get($key) {
        return $this->data[$key] ?? null;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function delete($key) {
        if (array_key_exists($key, $this->data)){
            unset($this->data[$key]);
        }
    }

    public function deleteMatch($key) {
        foreach ($this->data as $k => $v) {
            if (strstr($k, $key)) {
                unset($this->data[$k]);
            }
        }
    }

    public function clear() {
        $this->data = [];
    }
}
