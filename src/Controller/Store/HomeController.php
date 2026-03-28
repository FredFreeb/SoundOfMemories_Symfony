<?php

namespace App\Controller\Store;

use App\Repository\ConcertRepository;
use App\Repository\ProductRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'store_home', methods: ['GET'])]
    public function __invoke(ProductRepository $products, ConcertRepository $concerts, SiteSettingsProvider $siteSettings): Response
    {
        return $this->render('store/home.html.twig', [
            'products' => $products->findPublished(6),
            'monthlyOfferProduct' => $products->findCurrentMonthlyOffer(),
            'upcomingConcerts' => $concerts->findUpcomingPublished(4),
            'siteSettings' => $siteSettings->getCurrent(),
        ]);
    }
}
