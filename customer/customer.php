<?php
/*
Name: Kundregister
Description: Register via listandedit
Version: 1.0.0
Type: Module
*/
class customer
{
    public function __construct($settings = [])
    {
        $settings['table'] = 'customer';
        new listandedit($settings);
    }
}
