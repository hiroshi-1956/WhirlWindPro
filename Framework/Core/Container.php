<?php

namespace Framework\Core;

class Container {
    
    private static $db_develop = null;
    private static $db_product = null;
    
    // 外からセットする（注入）
    public static function setDb_develop($dbInstance) {
        self::$db_develop = $dbInstance;
    }
    
    // 必要な場所で取り出す
    public static function getDb_develop() {
        return self::$db_develop;
    }

    // 外からセットする（注入）
    public static function setDb_product($dbInstance) {
        self::$db_product = $dbInstance;
    }
    
    // 必要な場所で取り出す
    public static function getDb_product() {
        return self::$db_product;
    }
    
    // 道具を保管しておく棚
    private static $instances = [];
    
    /**
     * 道具を箱に入れる (登録)
     * @param string $key 道具の名前 (例: 'db', 'config')
     * @param mixed $instance 道具の実体 (オブジェクトや配列)
     */
    public static function set($key, $instance) {
        self::$instances[$key] = $instance;
    }
    
    /**
     * 道具を箱から出す (取得)
     * @param string $key 道具の名前
     * @return mixed 道具の実体
     */
    public static function get($key) {
        if (!isset(self::$instances[$key])) {
            return null;
        }
        return self::$instances[$key];
    }
}
