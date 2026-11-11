<?php
/*
Name: Produktregister
Description: Register via listandedit
Version: 1.0.0
Type: Module
*/
class products
{
    public function __construct($settings = [])
    {
        $settings['table'] = 'products';
        new listandedit($settings);
    }
}
