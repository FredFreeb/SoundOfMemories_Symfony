<?php

namespace App\Controller\Store;

use App\Entity\EditorialModule;
use App\Entity\PressMention;
use App\Repository\ConcertRepository;
use App\Repository\EditorialModuleRepository;
use App\Repository\PressMentionRepository;
use App\Repository\ProductRepository;
use App\Service\SiteSettingsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'store_home', methods: ['GET'])]
    public function __invoke(
        ProductRepository $products,
        ConcertRepository $concerts,
        PressMentionRepository $pressMentions,
        EditorialModuleRepository $editorialModules,
        SiteSettingsProvider $siteSettings,
    ): Response
    {
        $pressEntries = array_map(
            static function (PressMention $mention): array {
                return [
                    'title' => $mention->getAuthorName() ?? 'Article',
                    'source' => $mention->getSourceLabel() ?: 'Rock magazine',
                    'lead' => $mention->getQuoteSecondary(),
                    'excerpt' => $mention->getQuotePrimary(),
                    'url' => $mention->getLinkUrl(),
                    'linkLabel' => $mention->getLinkLabel() ?: 'Lire l’article',
                    'photo' => $mention->getPhoto(),
                ];
            },
            $pressMentions->findPublishedOrdered()
        );

        return $this->render('store/home.html.twig', [
            'products' => $products->findPublished(6),
            'monthlyOfferProduct' => $products->findCurrentMonthlyOffer(),
            'upcomingConcerts' => $concerts->findUpcomingPublished(4),
            'pressEntries' => $pressEntries,
            'editorial' => $editorialModules->findPublishedMapForPage(EditorialModule::PAGE_HOME),
            'siteSettings' => $siteSettings->getCurrent(),
        ]);
    }
}
