<?php

namespace App\Controller\Store;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Fred note: Je regroupe ici les pages statiques publiques utiles au site, comme les mentions legales.
final class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'store_legal', methods: ['GET'])]
    public function legal(): Response
    {
        return $this->render('store/legal/index.html.twig');
    }

    #[Route('/conditions-generales-de-vente', name: 'store_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('store/legal/cgv.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'store_privacy', methods: ['GET'])]
    public function privacy(): Response
    {
        return $this->render('store/legal/privacy.html.twig');
    }
}
