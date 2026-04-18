<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\CustomerDataLifecycleManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/la-porte-secrete/clients')]
final class CustomerLifecycleController extends AbstractController
{
    #[Route('/{id}/traiter-rgpd', name: 'admin_customer_apply_data_lifecycle', methods: ['GET'])]
    public function __invoke(
        User $user,
        CustomerDataLifecycleManager $customerDataLifecycleManager,
        Request $request,
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        try {
            $result = $customerDataLifecycleManager->processErasureRequest($user);

            if ('deleted' === $result) {
                $this->addFlash('success', 'Le compte fan a été supprimé, ainsi que ses données marketing et ses échanges sans commande.');
            } else {
                $this->addFlash('success', 'Le compte fan a été clôturé. Les données marketing ont été retirées et les données de commande conservées au titre des obligations légales.');
            }
        } catch (\LogicException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        } catch (\Throwable) {
            $this->addFlash('error', 'Impossible de traiter cette demande fan pour le moment.');
        }

        $referer = $request->headers->get('referer');

        if (\is_string($referer) && '' !== $referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute('admin_clients_index');
    }
}
