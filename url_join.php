<?php
function url_join($arr): string {
    foreach ( $arr as $path ) $url[] = rtrim ( $path, '/' );
    return implode('/', $url);
}
