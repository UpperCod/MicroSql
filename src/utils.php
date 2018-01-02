<?php

namespace MicroSql;

function map($callback,$array){
    $next  = [];
    foreach($array as $index => $value){
        array_push($next,$callback($value,$index));
    }
    return $next;
}

function param($index){
    return preg_replace('/[\(\)\n\t\s\'\"]+/',"_",strip_tags(stripslashes($index)));
}
