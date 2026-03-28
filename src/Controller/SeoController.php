<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Fred note: Je sers ici les fichiers SEO techniques au format attendu par les moteurs.
final class SeoController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'seo_sitemap', methods: ['GET'])]
    public function sitemap(ProductRepository $products, SiteSettingsProvider $siteSettings): Response
    {
        $xml = $this->renderView('seo/sitemap.xml.twig', [
            'products' => $products->findPublished(),
            'siteSettings' => $siteSettings->getCurrent(),
        ]);

        return new Response($xml, Response::HTTP_OK, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    #[Route('/robots.txt', name: 'seo_robots', methods: ['GET'])]
    public function robots(): Response
    {
        $content = $this->renderView('seo/robots.txt.twig');

        return new Response($content, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
