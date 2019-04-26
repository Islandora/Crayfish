<?php

namespace Islandora\Crayfish\Commons\Syn;

use Namshi\JOSE\SimpleJWS;

class JwtFactory
{
    public function load($jwt)
    {
        return SimpleJWS::load($jwt);
    }
}
