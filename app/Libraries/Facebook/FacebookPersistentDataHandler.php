<?php
namespace App\Libraries\Facebook;

class FacebookPersistentDataHandler implements \Facebook\PersistentData\PersistentDataInterface
{
    public function get($key)
    {
        return \Session::get('facebook.' . $key);
    }
    public function set($key, $value)
    {
        \Session::put('facebook.' . $key, $value);
    }
}
