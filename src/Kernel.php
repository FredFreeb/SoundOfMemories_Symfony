<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

// Fred note: Le kernel est le point d'entree principal de Symfony pour charger le projet.
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
