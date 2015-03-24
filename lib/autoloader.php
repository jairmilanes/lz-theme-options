<?php

function lzto_autoload($pClassName) {
    if( substr($pClassName, 0,3) == 'Lib' ){
        $p = explode('\\',$pClassName);
        array_shift($p);
        $filepath = dirname(__FILE__) . "\\" . (implode('\\', $p)).'.php';
        if( file_exists($filepath) ){
            include $filepath;
        }
    }
}
spl_autoload_register("lzto_autoload");