<?php

spl_autoload_register(function($className) 
{ 
    require('src/' . str_replace('\\', '/', ltrim($className, '\\')) . '.php'); 
});
