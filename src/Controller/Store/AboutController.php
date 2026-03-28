<?php

namespace App\Controller\Store;

use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/le-groupe', name: 'store_about', methods: ['GET'])]
    public function __invoke(SiteSettingsProvider $siteSettings): Response
    {
        return $this->render('store/about/index.html.twig', [
            'siteSettings' => $siteSettings->getCurrent(),
        ]);
    }
}
