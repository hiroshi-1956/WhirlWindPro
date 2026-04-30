<?php

// Framework/Core/functions.php
function url($path = '') {
    // 開発者が url('develop/Console/Initial') と書いたものをそのまま結合
    return BASE_URL . '/' . ltrim($path, '/');
}
