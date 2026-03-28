<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_dev/boite-mail')]
final class DevMailboxController extends AbstractController
{
    #[Route('', name: 'app_dev_mailbox', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyIfNotDev();

        $mails = [];

        foreach (glob($this->getMailboxDir().'/*.json') ?: [] as $file) {
            $mail = $this->readMailMetadata($file);

            if ($mail !== null) {
                $mails[] = $mail;
            }
        }

        usort($mails, static fn (array $left, array $right): int => strcmp($right['created_at'] ?? '', $left['created_at'] ?? ''));

        return $this->render('dev_mailbox/index.html.twig', [
            'mails' => $mails,
        ]);
    }

    #[Route('/vider', name: 'app_dev_mailbox_clear', methods: ['POST'])]
    public function clear(Request $request): RedirectResponse
    {
        $this->denyIfNotDev();

        if (!$this->isCsrfTokenValid('clear_dev_mailbox', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton invalide.');
        }

        foreach (glob($this->getMailboxDir().'/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $this->addFlash('success', 'La boîte mail locale a été vidée.');

        return $this->redirectToRoute('app_dev_mailbox');
    }

    #[Route('/{id}', name: 'app_dev_mailbox_show', methods: ['GET'], requirements: ['id' => '[A-Za-z0-9\\-]+'])]
    public function show(string $id): Response
    {
        $this->denyIfNotDev();

        $mail = $this->readMailMetadata($this->getMailboxDir().'/'.$id.'.json');

        if ($mail === null) {
            throw $this->createNotFoundException('Email local introuvable.');
        }

        $rawSourcePath = $this->getMailboxDir().'/'.$id.'.eml';
        $rawSource = is_file($rawSourcePath) ? (string) file_get_contents($rawSourcePath) : '';

        return $this->render('dev_mailbox/show.html.twig', [
            'mail' => $mail,
            'rawSource' => $rawSource,
        ]);
    }

    private function denyIfNotDev(): void
    {
        if ($this->getParameter('kernel.environment') !== 'dev') {
            throw $this->createNotFoundException();
        }
    }

    private function getMailboxDir(): string
    {
        return $this->getParameter('kernel.project_dir').'/var/dev-mailbox';
    }

    private function readMailMetadata(string $file): ?array
    {
        if (!is_file($file)) {
            return null;
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : null;
    }
}
