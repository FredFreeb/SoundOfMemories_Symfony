<?php

namespace App\Controller;

use App\Entity\CustomerConversation;
use App\Entity\CustomerConversationMessage;
use App\Entity\Order;
use App\Entity\User;
use App\Form\AccountProfileType;
use App\Form\SupportConversationType;
use App\Form\SupportReplyType;
use App\Service\CustomerDataLifecycleManager;
use App\Service\PhoneNumberService;
use App\Service\WelcomeOfferService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/mon-compte')]
final class AccountController extends AbstractController
{
    #[Route('', name: 'app_account', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $userAvatarsDir,
        CustomerDataLifecycleManager $customerDataLifecycleManager,
        PhoneNumberService $phoneNumberService,
        WelcomeOfferService $welcomeOfferService,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getCurrentUser();
        $profileForm = $this->createForm(AccountProfileType::class, $user);
        $profileForm->handleRequest($request);

        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $avatarFile = $profileForm->get('avatarFile')->getData();

            if ($avatarFile instanceof UploadedFile) {
                $this->replaceAvatar($user, $avatarFile, $slugger, $userAvatarsDir);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Les informations du compte ont été mises à jour.');

            return $this->redirectToRoute('app_account');
        }

        if ($profileForm->isSubmitted() && !$profileForm->isValid()) {
            $phoneErrors = count($profileForm->get('phone')->getErrors(true));
            $countryErrors = count($profileForm->get('phoneCountryCode')->getErrors(true));

            if ($phoneErrors > 0 || $countryErrors > 0) {
                $this->addFlash('warning', 'Le numéro de téléphone n’a pas pu être validé. Corrige-le avant de réenregistrer le profil.');
            } else {
                $this->addFlash('warning', 'Certaines informations n’ont pas pu être enregistrées. Vérifie les champs signalés puis réessaie.');
            }
        }

        $orders = $entityManager->createQuery(
            'SELECT DISTINCT o
             FROM App\Entity\Order o
             LEFT JOIN o.customerAccount customer
             WHERE customer = :user OR o.customerEmail = :email
             ORDER BY o.createdAt DESC'
        )
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getResult();

        $conversations = $entityManager->createQuery(
            'SELECT DISTINCT c
             FROM App\Entity\CustomerConversation c
             LEFT JOIN c.customerAccount customer
             WHERE customer = :user OR c.customerEmail = :email
             ORDER BY c.lastMessageAt DESC'
        )
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getResult();

        return $this->render('account/index.html.twig', [
            'account' => $user,
            'orders' => $orders,
            'conversations' => $conversations,
            'profileForm' => $profileForm->createView(),
            'profileEditorOpen' => $profileForm->isSubmitted(),
            'accountDisplayPhone' => $phoneNumberService->formatForDisplay($user->getPhone()),
            'canHardDelete' => !$customerDataLifecycleManager->hasRetainedOrderData($user),
            'welcomeOfferEligible' => $welcomeOfferService->isEligible($user, (string) $user->getEmail()),
            'welcomeOfferHasCommercialOrder' => $welcomeOfferService->hasCommercialOrder($user, (string) $user->getEmail()),
        ]);
    }

    #[Route('/mailing/desinscription', name: 'app_account_marketing_unsubscribe', methods: ['POST'])]
    public function unsubscribeFromMarketing(
        Request $request,
        CustomerDataLifecycleManager $customerDataLifecycleManager,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('account_marketing_unsubscribe', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $customerDataLifecycleManager->revokeMarketing($this->getCurrentUser());
        $this->addFlash('success', 'La désinscription au mailing a bien été enregistrée.');

        return $this->redirectToRoute('app_account');
    }

    #[Route('/donnees/suppression', name: 'app_account_delete', methods: ['POST'])]
    public function deleteAccount(
        Request $request,
        CustomerDataLifecycleManager $customerDataLifecycleManager,
        TokenStorageInterface $tokenStorage,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('account_delete', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user = $this->getCurrentUser();

        if ($user->isAdmin()) {
            throw $this->createAccessDeniedException('Le compte administrateur ne peut pas être supprimé depuis cet espace.');
        }

        $result = $customerDataLifecycleManager->processErasureRequest($user);

        $tokenStorage->setToken(null);
        $request->getSession()->remove('_security_main');

        if ('deleted' === $result) {
            $this->addFlash('success', 'Votre compte fan a été supprimé. Les données marketing et les échanges sans commande ont été effacés.');
        } else {
            $this->addFlash('success', 'Votre compte a été clôturé. Les données marketing ont été retirées, tandis que les données de commande restent conservées pendant les durées légales applicables.');
        }

        return $this->redirectToRoute('store_home');
    }

    #[Route('/conversations/nouvelle', name: 'app_account_conversation_new', methods: ['GET', 'POST'])]
    public function newConversation(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getCurrentUser();
        $form = $this->createForm(SupportConversationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $conversation = (new CustomerConversation())
                ->setType('pre_sale')
                ->setSubject((string) $form->get('subject')->getData())
                ->setCustomerAccount($user)
                ->setCustomerName((string) $user->getFullName())
                ->setCustomerEmail((string) $user->getEmail())
                ->setStatus('open');

            $message = (new CustomerConversationMessage())
                ->setAuthorType('client')
                ->setAuthorName((string) $user->getFullName())
                ->setBody((string) $form->get('body')->getData());

            $conversation->addMessage($message);

            $entityManager->persist($conversation);
            $entityManager->flush();

            $this->addFlash('success', 'La conversation a bien été ouverte.');

            return $this->redirectToRoute('app_account_conversation_show', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('account/conversation_new.html.twig', [
            'conversationForm' => $form->createView(),
        ]);
    }

    #[Route('/conversations/{id}', name: 'app_account_conversation_show', methods: ['GET', 'POST'])]
    public function conversationShow(CustomerConversation $conversation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getCurrentUser();

        if (!$this->canAccessConversation($conversation, $user)) {
            throw $this->createAccessDeniedException('Cette conversation ne vous appartient pas.');
        }

        $replyForm = $this->createForm(SupportReplyType::class);
        $replyForm->handleRequest($request);

        if ($replyForm->isSubmitted() && $replyForm->isValid()) {
            $message = (new CustomerConversationMessage())
                ->setAuthorType('client')
                ->setAuthorName((string) $user->getFullName())
                ->setBody((string) $replyForm->get('body')->getData());

            $conversation
                ->addMessage($message)
                ->setStatus('open')
                ->setHasUnreadForAdmin(true)
                ->setLastMessageAt($message->getCreatedAt());

            $entityManager->flush();
            $this->addFlash('success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('app_account_conversation_show', [
                'id' => $conversation->getId(),
            ]);
        }

        return $this->render('account/conversation_show.html.twig', [
            'conversation' => $conversation,
            'replyForm' => $replyForm->createView(),
        ]);
    }

    #[Route('/telephone/deviner', name: 'app_account_phone_guess', methods: ['GET'])]
    public function guessPhoneRegion(Request $request, PhoneNumberService $phoneNumberService): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $value = trim((string) $request->query->get('value', ''));
        $preferred = strtoupper((string) $request->query->get('preferred', 'FR'));

        if ('' === $value) {
            return new JsonResponse(['region' => null]);
        }

        $matches = $phoneNumberService->findMatchingRegions($value);
        $region = $phoneNumberService->guessRegionForInput($value, $preferred !== '' ? $preferred : 'FR');

        return new JsonResponse([
            'region' => $region,
            'ambiguous' => count($matches) > 1,
            'matches' => array_map(
                fn (string $match): string => sprintf(
                    '%s %s (+%d)',
                    $phoneNumberService->getFlagEmoji($match),
                    $phoneNumberService->getRegionLabel($match),
                    $phoneNumberService->getDialCode($match),
                ),
                array_slice($matches, 0, 4)
            ),
            'label' => null !== $region ? sprintf(
                '%s %s (+%d)',
                $phoneNumberService->getFlagEmoji($region),
                $phoneNumberService->getRegionLabel($region),
                $phoneNumberService->getDialCode($region),
            ) : null,
        ]);
    }

    #[Route('/commandes/{id}', name: 'app_account_order_show', methods: ['GET'])]
    public function orderShow(Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getCurrentUser();

        if (!$this->canAccessOrder($order, $user)) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        $orderConversation = $entityManager->getRepository(CustomerConversation::class)->findOneBy([
            'type' => 'order_support',
            'orderRef' => $order,
        ]);

        return $this->render('account/order_show.html.twig', [
            'order' => $order,
            'orderConversation' => $orderConversation,
        ]);
    }

    #[Route('/commandes/{id}/support', name: 'app_account_order_support', methods: ['GET'])]
    public function orderSupport(Order $order, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getCurrentUser();

        if (!$this->canAccessOrder($order, $user)) {
            throw $this->createAccessDeniedException('Cette commande ne vous appartient pas.');
        }

        $conversation = $entityManager->getRepository(CustomerConversation::class)->findOneBy([
            'type' => 'order_support',
            'orderRef' => $order,
        ]);

        if (!$conversation instanceof CustomerConversation) {
            $conversation = (new CustomerConversation())
                ->setType('order_support')
                ->setSubject(sprintf('Suivi de la commande #%d', $order->getId()))
                ->setCustomerAccount($user)
                ->setCustomerName((string) $user->getFullName())
                ->setCustomerEmail((string) $user->getEmail())
                ->setOrderRef($order)
                ->setStatus('open')
                ->setHasUnreadForAdmin(false);

            $entityManager->persist($conversation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_account_conversation_show', [
            'id' => $conversation->getId(),
        ]);
    }

    private function getCurrentUser(): User
    {
        $user = $this->getUser();
        \assert($user instanceof User);

        return $user;
    }

    private function canAccessOrder(Order $order, User $user): bool
    {
        return $order->getCustomerAccount()?->getId() === $user->getId() || $order->getCustomerEmail() === $user->getEmail();
    }

    private function canAccessConversation(CustomerConversation $conversation, User $user): bool
    {
        return $conversation->getCustomerAccount()?->getId() === $user->getId() || $conversation->getCustomerEmail() === $user->getEmail();
    }

    private function replaceAvatar(User $user, UploadedFile $avatarFile, SluggerInterface $slugger, string $userAvatarsDir): void
    {
        $allowedExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $mimeType = (string) $avatarFile->getMimeType();

        if (!isset($allowedExtensions[$mimeType])) {
            throw new BadRequestHttpException('Le format d’avatar envoyé n’est pas autorisé.');
        }

        $originalName = pathinfo($avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = (string) $slugger->slug($originalName !== '' ? $originalName : 'avatar');
        $extension = $allowedExtensions[$mimeType];
        $newFilename = sprintf('%s-%s.%s', $safeName, time(), $extension);

        $avatarFile->move($userAvatarsDir, $newFilename);

        $oldAvatar = $user->getAvatarPath();

        if (null !== $oldAvatar && '' !== $oldAvatar) {
            $oldPath = rtrim($userAvatarsDir, '/').'/'.basename($oldAvatar);

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        $user->setAvatarPath($newFilename);
    }
}
