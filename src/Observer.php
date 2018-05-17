<?php

namespace Nuage\Core;

interface Observer
{
    public function process($user, $input);
}
