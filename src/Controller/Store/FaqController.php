<?php

namespace App\Controller\Store;

use App\Repository\FaqEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Fred note: La FAQ publique ne montre que les questions publiees et triees.
final class FaqController extends AbstractController
{
    #[Route('/faq', name: 'store_faq', methods: ['GET'])]
    public function __invoke(FaqEntryRepository $faqEntries): Response
    {
        return $this->render('store/faq/index.html.twig', [
            'faqEntries' => $faqEntries->findPublishedOrdered(),
        ]);
    }
}
