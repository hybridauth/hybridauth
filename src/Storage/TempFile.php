<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2019 Hybridauth authors | https://hybridauth.github.io/license.html
*/
namespace Hybridauth\Storage;

/**
 * HybridAuth storage by temp file.
 */
class TempFile implements StorageInterface
{
    /**
     * @var string Key prefix
     */
    protected $keyPrefix = '';

    /**
     * @var string Path to storage file
     */
    protected $path;

    /**
     * @var array Storaged data
     */
    protected $data = [];

    /**
     * Sets storage file and load data.
     *
     * @return void
     */
    public function __construct()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'HybridauthStorage' . md5(get_current_user()) . '.tmp';
        if (file_exists($this->path)) {
            $this->data = unserialize(file_get_contents($this->path));
        }
    }

    /**
     * @param string $key
     * @return string
     */
    protected function normalizeKey($key)
    {
        return $this->keyPrefix . strtolower($key);
    }
    
    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->normalizeKey($key);
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $key = $this->normalizeKey($key);
        $this->data[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->normalizeKey($key);
        unset($this->data[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMatch($key)
    {
        $key = $this->normalizeKey($key);
        foreach ($this->data as $k => $v) {
            if (strstr($k, $key)) {
                unset($this->data[$k]);
            }
        }
    }

    /**
     * Writes data to storage file.
     * 
     * @return void
     */
    public function __destruct()
    {
        file_put_contents($this->path, serialize($this->data));
    }
}
