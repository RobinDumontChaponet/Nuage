<?php

interface Decorator
{
    public function __construct($object);

    public function __call($name, $arguments);

    public function exists();
}
