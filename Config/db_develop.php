<?php

// MySQL版の例
return [
    'dsn'      => 'mysql:host=localhost;dbname=db_develop;charset=utf8mb4',
    'username' => 'root',
    'password' => '',
    'options'  => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        // MySQL特有の設定（バッファクエリの有効化など）
        \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]
];