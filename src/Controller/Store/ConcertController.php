<?php

namespace App\Controller\Store;

use App\Entity\EditorialModule;
use App\Repository\ConcertRepository;
use App\Repository\EditorialModuleRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/concerts')]
final class ConcertController extends AbstractController
{
    #[Route('', name: 'store_concerts', methods: ['GET'])]
    public function index(
        ConcertRepository $concerts,
        EditorialModuleRepository $editorialModules,
        SiteSettingsProvider $siteSettings
    ): Response
    {
        return $this->render('store/concert/index.html.twig', [
            'concerts' => $concerts->findPublishedChronological(),
            'editorial' => $editorialModules->findPublishedMapForPage(EditorialModule::PAGE_CONCERTS),
            'siteSettings' => $siteSettings->getCurrent(),
        ]);
    }
}
