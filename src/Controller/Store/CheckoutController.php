<?php

namespace App\Controller\Store;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Form\CheckoutType;
use App\Service\CartService;
use App\Service\MollieCheckoutService;
use App\Service\OrderFulfillmentService;
use App\Service\BoxtalShippingService;
use App\Service\StripeCheckoutService;
use App\Service\WelcomeOfferService;
use Doctrine\ORM\EntityManagerInterface;
use Mollie\Api\Exceptions\ApiException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande')]
final class CheckoutController extends AbstractController
{
    public function __construct(
        private readonly string $stripeWebhookSecret,
    ) {
    }

    #[Route('', name: 'store_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cart,
        MollieCheckoutService $mollieCheckout,
        StripeCheckoutService $stripeCheckout,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        WelcomeOfferService $welcomeOfferService,
        BoxtalShippingService $boxtalShipping,
    ): Response {
        if ($cart->isEmpty()) {
            $this->addFlash('warning', 'Ton panier est vide.');

            return $this->redirectToRoute('store_shop_index');
        }

        $order = new Order();

        if ($this->getUser() instanceof User) {
            /** @var User $account */
            $account = $this->getUser();
            $latestOrder = $entityManager->getRepository(Order::class)->findOneBy(
                ['customerAccount' => $account],
                ['createdAt' => 'DESC']
            );

            // Fred note: Je pre-remplis la commande depuis le compte fan pour accelerer l'achat.
            $order
                ->setCustomerAccount($account)
                ->setCustomerName($account->getFullName() ?? '')
                ->setCustomerEmail($account->getEmail() ?? '')
                ->setCustomerPhone($account->getPhone() ?: ($latestOrder instanceof Order ? $latestOrder->getCustomerPhone() : null))
                ->setShippingAddress($this->buildShippingAddress($account) ?: ($latestOrder instanceof Order ? $latestOrder->getShippingAddress() : null))
                ->setPostalCode($account->getPostalCode() ?: ($latestOrder instanceof Order ? $latestOrder->getPostalCode() : null))
                ->setCity($account->getCity() ?: ($latestOrder instanceof Order ? $latestOrder->getCity() : null))
                ->setNote($this->resolveAccountShippingNote($account, $latestOrder))
                ->setShippingCountryCode($account->getCountryCode() ?: ($latestOrder instanceof Order ? $latestOrder->getShippingCountryCode() : null))
                ->setPaymentProvider('stripe');
        }

        if (null === $order->getPaymentProvider()) {
            $order->setPaymentProvider('stripe');
        }

        if (null === $order->getShippingCountryCode()) {
            $order->setShippingCountryCode('FR');
        }

        $submittedCheckout = $request->request->all('checkout');

        if (\is_array($submittedCheckout)) {
            $this->hydrateShippingPreview($order, $submittedCheckout);
        }

        $shippingChoices = $boxtalShipping->quoteCheckoutOptions(
            $order->getShippingCountryCode(),
            $order->getPostalCode(),
            $cart->getDetailedItems(),
        );
        $defaultShippingChoice = $boxtalShipping->findCheapestOption($shippingChoices);

        if (!$request->isMethod('POST') && null === $order->getShippingMethodCode() && null !== $defaultShippingChoice) {
            $order->setShippingMethodCode($defaultShippingChoice['code']);
        }

        $form = $this->createForm(CheckoutType::class, $order, [
            'shipping_country_choices' => $boxtalShipping->getDestinationCountryChoices(),
            'shipping_choices' => $shippingChoices,
            'shipping_help' => $boxtalShipping->getCheckoutHint($order->getShippingCountryCode(), $shippingChoices),
            'shipping_method_required' => [] !== $shippingChoices,
        ]);
        $form->handleRequest($request);

        $selectedShippingChoice = $boxtalShipping->findOption(
            $form->isSubmitted() ? $form->get('shippingMethodCode')->getData() : $order->getShippingMethodCode(),
            $shippingChoices,
        );

        if ($form->isSubmitted()) {
            if ([] === $shippingChoices) {
                $form->addError(new FormError('Aucun mode de livraison n’a pu être calculé pour cette destination.'));
            } elseif (null === $selectedShippingChoice) {
                $form->get('shippingMethodCode')->addError(new FormError('Choisissez un mode de livraison pour continuer.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Fred note: Je force temporairement Stripe cote checkout tant que je ne veux pas exposer le choix public.
            $selectedProvider = 'stripe';
            $order->setPaymentProvider($selectedProvider);

            if ('stripe' === $selectedProvider && !$stripeCheckout->isConfigured()) {
                $this->addFlash('warning', 'Configure STRIPE_SECRET_KEY pour activer le paiement Stripe.');

                return $this->redirectToRoute('store_checkout');
            }

            if ('mollie' === $selectedProvider && !$mollieCheckout->isConfigured()) {
                $this->addFlash('warning', 'Configure MOLLIE_API_KEY pour activer le paiement Mollie.');

                return $this->redirectToRoute('store_checkout');
            }

            foreach ($cart->getDetailedItems() as $item) {
                $orderItem = (new OrderItem())
                    ->setProductName($item['product']->getName() ?? 'Produit')
                    ->setProductIdSnapshot($item['product']->getId())
                    ->setProductVariantIdSnapshot($item['variant']?->getId())
                    ->setVariantLabel($item['variant']?->getLabel())
                    ->setVariantSku($item['variant']?->getSku())
                    ->setQuantity($item['quantity'])
                    ->setUnitPriceCents($item['unitPriceCents']);

                $order->addItem($orderItem);
            }

            $order
                ->setStatus(Order::STATUS_PENDING)
                ->setPaymentStatus(Order::PAYMENT_STATUS_PENDING)
                ->setDeliveryStatus(Order::DELIVERY_STATUS_PENDING)
                ->setPaymentProvider($selectedProvider)
                ->setShippingProvider($selectedShippingChoice['provider'])
                ->setShippingMethodCode($selectedShippingChoice['code'])
                ->setShippingMethodLabel($selectedShippingChoice['label'])
                ->setShippingCarrier($selectedShippingChoice['carrier'])
                ->setShippingRateCents($selectedShippingChoice['priceCents'])
                ->setSubtotalCents($cart->getTotalCents())
                ->setDiscountCents(0)
                ->setDiscountLabel(null)
                ->setTotalCents($cart->getTotalCents() + $selectedShippingChoice['priceCents']);

            if ($this->getUser() instanceof User) {
                /** @var User $customerAccount */
                $customerAccount = $this->getUser();

                $customerAccount
                    ->setFullName($order->getCustomerName() !== '' ? $order->getCustomerName() : ($customerAccount->getFullName() ?? 'Fan Sound Of Memories'))
                    ->setPhone($order->getCustomerPhone() ?: $customerAccount->getPhone())
                    ->setCountryCode($order->getShippingCountryCode() ?: $customerAccount->getCountryCode())
                    ->setDefaultAddress($this->extractStreetFromShippingAddress($order->getShippingAddress()) ?: $customerAccount->getDefaultAddress())
                    ->setAddressBuilding($this->extractStreetNumberFromShippingAddress($order->getShippingAddress()) ?: $customerAccount->getAddressBuilding())
                    ->setAddressExtra($order->getNote() ?: $customerAccount->getAddressExtra())
                    ->setPostalCode($order->getPostalCode() ?: $customerAccount->getPostalCode())
                    ->setCity($order->getCity() ?: $customerAccount->getCity());

                $order->setCustomerAccount($customerAccount);
            } else {
                // Fred note: Je relie aussi les commandes guest a un vrai compte fan pour que l'admin voie tous les acheteurs au meme endroit.
                $customerAccount = $entityManager->getRepository(User::class)->findOneBy([
                    'email' => mb_strtolower($order->getCustomerEmail()),
                ]);

                if (!$customerAccount instanceof User) {
                    $customerAccount = (new User())
                        ->setEmail($order->getCustomerEmail())
                        ->setFullName($order->getCustomerName() !== '' ? $order->getCustomerName() : 'Fan Sound Of Memories')
                        ->setRoles([]);
                    $customerAccount->setPassword($passwordHasher->hashPassword($customerAccount, bin2hex(random_bytes(24))));
                    $entityManager->persist($customerAccount);
                }

                $customerAccount
                    ->setFullName($order->getCustomerName() !== '' ? $order->getCustomerName() : ($customerAccount->getFullName() ?? 'Fan Sound Of Memories'))
                    ->setPhone($order->getCustomerPhone() ?: $customerAccount->getPhone())
                    ->setCountryCode($order->getShippingCountryCode() ?: $customerAccount->getCountryCode())
                    ->setDefaultAddress($this->extractStreetFromShippingAddress($order->getShippingAddress()) ?: $customerAccount->getDefaultAddress())
                    ->setAddressBuilding($this->extractStreetNumberFromShippingAddress($order->getShippingAddress()) ?: $customerAccount->getAddressBuilding())
                    ->setAddressExtra($order->getNote() ?: $customerAccount->getAddressExtra())
                    ->setPostalCode($order->getPostalCode() ?: $customerAccount->getPostalCode())
                    ->setCity($order->getCity() ?: $customerAccount->getCity());

                $order->setCustomerAccount($customerAccount);
            }

            $customerAccount = $order->getCustomerAccount();

            if ($customerAccount instanceof User && $welcomeOfferService->isEligible($customerAccount, $order->getCustomerEmail())) {
                $welcomeOfferService->applyWelcomeDiscount($order);
            }

            $order->setTotalCents(max(0, $order->getSubtotalCents() - $order->getDiscountCents()) + $order->getShippingRateCents());

            $entityManager->persist($order);
            $entityManager->flush();

            if ('stripe' === $selectedProvider) {
                $session = $stripeCheckout->createCheckoutSession($order);
                $order->setStripeCheckoutSessionId($session->id);
                $entityManager->flush();

                return $this->redirect((string) $session->url);
            }

            try {
                $payment = $mollieCheckout->createPayment($order);
            } catch (ApiException $exception) {
                $this->addFlash('warning', 'Le paiement Mollie n a pas pu être initialise pour le moment.');

                return $this->redirectToRoute('store_checkout');
            }

            $order->setMolliePaymentId($payment->id);
            $entityManager->flush();

            return $this->redirect((string) $payment->getCheckoutUrl());
        }

        $previewDiscountCents = 0;
        $currentAccount = $order->getCustomerAccount();
        $checkoutEmail = trim((string) ($form->get('customerEmail')->getData() ?: $order->getCustomerEmail()));
        $wantsWelcomeOffer = $currentAccount instanceof User && $currentAccount->isMarketingOptIn();

        $welcomeOfferEligible = $welcomeOfferService->isEligibleForPreview(
            $currentAccount,
            $checkoutEmail,
            $wantsWelcomeOffer,
        );

        if ($welcomeOfferEligible) {
            $previewDiscountCents = $welcomeOfferService->calculateDiscountCents($cart->getTotalCents());
        }

        $previewShippingCents = $selectedShippingChoice['priceCents'] ?? ($defaultShippingChoice['priceCents'] ?? 0);
        $previewShippingLabel = $selectedShippingChoice['label'] ?? ($defaultShippingChoice['label'] ?? null);
        $previewShippingEstimated = $selectedShippingChoice['estimated'] ?? ($defaultShippingChoice['estimated'] ?? false);
        $previewTotalCents = max(0, $cart->getTotalCents() - $previewDiscountCents) + $previewShippingCents;

        return $this->render('store/checkout/index.html.twig', [
            'form' => $form->createView(),
            'items' => $cart->getDetailedItems(),
            'totalCents' => $cart->getTotalCents(),
            'previewDiscountCents' => $previewDiscountCents,
            'previewShippingCents' => $previewShippingCents,
            'previewShippingLabel' => $previewShippingLabel,
            'previewShippingEstimated' => $previewShippingEstimated,
            'previewTotalCents' => $previewTotalCents,
            'mollieConfigured' => $mollieCheckout->isConfigured(),
            'stripeConfigured' => $stripeCheckout->isConfigured(),
            'boxtalConfigured' => $boxtalShipping->isLiveConfigured(),
            'shippingChoices' => $shippingChoices,
        ]);
    }

    #[Route('/succes', name: 'store_checkout_success', methods: ['GET'])]
    public function success(
        Request $request,
        EntityManagerInterface $entityManager,
        MollieCheckoutService $mollieCheckout,
        StripeCheckoutService $stripeCheckout,
        OrderFulfillmentService $fulfillment,
        CartService $cart,
    ): Response {
        $orderId = (int) $request->query->get('order');
        $sessionId = (string) $request->query->get('session_id');
        $accessToken = trim((string) $request->query->get('token'));
        $order = $entityManager->getRepository(Order::class)->find($orderId);

        if (!$order instanceof Order || $orderId <= 0) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        if (!$this->canAccessSuccessPage($order, $accessToken)) {
            throw $this->createAccessDeniedException('Cette confirmation de commande n’est pas accessible.');
        }

        if ('stripe' === $order->getPaymentProvider() && $stripeCheckout->isConfigured() && null !== $order->getStripeCheckoutSessionId()) {
            $checkoutSessionId = $sessionId !== '' ? $sessionId : $order->getStripeCheckoutSessionId();
            $session = $stripeCheckout->retrieveCheckoutSession($checkoutSessionId);

            if ('paid' === $session->payment_status) {
                $fulfillment->markPaid($order, $session->payment_intent);
                $cart->clear();
            } elseif (\in_array($session->payment_status, ['unpaid', 'no_payment_required'], true) === false) {
                $fulfillment->markFailed($order, $session->payment_intent);
            }
        }

        if ('mollie' === $order->getPaymentProvider() && $mollieCheckout->isConfigured() && null !== $order->getMolliePaymentId()) {
            try {
                $payment = $mollieCheckout->retrievePayment($order->getMolliePaymentId());
            } catch (ApiException) {
                $payment = null;
            }

            if ($payment !== null && ($payment->isPaid() || $payment->isAuthorized())) {
                $fulfillment->markPaid($order, $payment->id);
                $cart->clear();
            } elseif ($payment !== null && ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired())) {
                $fulfillment->markFailed($order, $payment->id);
            }
        }

        return $this->render('store/checkout/success.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/annulee', name: 'store_checkout_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a ete annule. Ton panier est toujours disponible.');

        return $this->redirectToRoute('store_checkout');
    }

    #[Route('/webhook/mollie', name: 'store_checkout_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        EntityManagerInterface $entityManager,
        OrderFulfillmentService $fulfillment,
        MollieCheckoutService $mollieCheckout,
    ): JsonResponse {
        $paymentId = (string) $request->request->get('id');

        if ('' === $paymentId || !$mollieCheckout->isConfigured()) {
            return new JsonResponse(['error' => 'invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $payment = $mollieCheckout->retrievePayment($paymentId);
        } catch (ApiException) {
            return new JsonResponse(['error' => 'payment lookup failed'], Response::HTTP_BAD_REQUEST);
        }

        $order = $entityManager->getRepository(Order::class)->findOneBy([
            'molliePaymentId' => $payment->id,
        ]);

        if (!$order instanceof Order) {
            return new JsonResponse(['status' => 'ignored']);
        }

        if ($payment->isPaid() || $payment->isAuthorized()) {
            $fulfillment->markPaid($order, $payment->id);
        } elseif ($payment->isFailed() || $payment->isCanceled() || $payment->isExpired()) {
            $fulfillment->markFailed($order, $payment->id);
        }

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/webhook/stripe', name: 'store_checkout_webhook_stripe', methods: ['POST'])]
    public function stripeWebhook(
        Request $request,
        EntityManagerInterface $entityManager,
        OrderFulfillmentService $fulfillment,
    ): JsonResponse {
        $payload = $request->getContent();
        $signature = (string) $request->headers->get('stripe-signature');

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return new JsonResponse(['error' => 'invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        if (\in_array($event->type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            $session = $event->data->object;
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'stripeCheckoutSessionId' => $session->id,
            ]);

            if ($order instanceof Order) {
                $fulfillment->markPaid($order, $session->payment_intent ?? null);
            }
        }

        if ('checkout.session.async_payment_failed' === $event->type) {
            $session = $event->data->object;
            $order = $entityManager->getRepository(Order::class)->findOneBy([
                'stripeCheckoutSessionId' => $session->id,
            ]);

            if ($order instanceof Order) {
                $fulfillment->markFailed($order, $session->payment_intent ?? null);
            }
        }

        return new JsonResponse(['status' => 'ok']);
    }

    #[Route('/webhook/boxtal', name: 'store_checkout_webhook_boxtal', methods: ['POST'])]
    public function boxtalWebhook(): JsonResponse
    {
        // Fred note: Je pose deja ce point d'entree pour Boxtal afin d'avoir une URL stable a renseigner, meme avant le vrai traitement des statuts.
        return new JsonResponse(['status' => 'received']);
    }

    private function buildShippingAddress(User $user): ?string
    {
        $parts = array_filter([
            $user->getDefaultAddress(),
            $user->getAddressBuilding(),
        ], static fn (?string $value): bool => null !== $value && '' !== trim($value));

        if ($parts === []) {
            return null;
        }

        return implode(' ', array_map(static fn (string $value): string => trim($value), $parts));
    }

    private function buildShippingNote(User $user): ?string
    {
        $parts = array_filter([
            $user->getAddressExtra(),
        ], static fn (?string $value): bool => null !== $value && '' !== trim($value));

        if ($parts === []) {
            return null;
        }

        return implode(', ', array_map(static fn (string $value): string => trim($value), $parts));
    }

    private function resolveAccountShippingNote(User $user, ?Order $latestOrder): ?string
    {
        return $this->buildShippingNote($user) ?: ($latestOrder instanceof Order ? $latestOrder->getNote() : null);
    }

    /**
     * @param array<string, mixed> $submittedCheckout
     */
    private function hydrateShippingPreview(Order $order, array $submittedCheckout): void
    {
        $order
            ->setShippingCountryCode(isset($submittedCheckout['shippingCountryCode']) ? (string) $submittedCheckout['shippingCountryCode'] : $order->getShippingCountryCode())
            ->setPostalCode(isset($submittedCheckout['postalCode']) ? (string) $submittedCheckout['postalCode'] : $order->getPostalCode())
            ->setCity(isset($submittedCheckout['city']) ? (string) $submittedCheckout['city'] : $order->getCity())
            ->setShippingAddress($this->buildSubmittedShippingAddress($submittedCheckout, $order->getShippingAddress()))
            ->setShippingMethodCode(isset($submittedCheckout['shippingMethodCode']) ? (string) $submittedCheckout['shippingMethodCode'] : $order->getShippingMethodCode());
    }

    private function buildSubmittedShippingAddress(array $submittedCheckout, ?string $fallbackAddress): ?string
    {
        $street = trim((string) ($submittedCheckout['shippingAddress'] ?? ''));
        $number = trim((string) ($submittedCheckout['shippingStreetNumber'] ?? ''));

        $parts = array_filter([$street, $number], static fn (string $value): bool => '' !== $value);

        if ($parts === []) {
            return $fallbackAddress;
        }

        return implode(' ', $parts);
    }

    private function extractStreetFromShippingAddress(?string $shippingAddress): ?string
    {
        [$street] = $this->splitShippingAddress($shippingAddress);

        return $street;
    }

    private function extractStreetNumberFromShippingAddress(?string $shippingAddress): ?string
    {
        [, $number] = $this->splitShippingAddress($shippingAddress);

        return $number;
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function splitShippingAddress(?string $shippingAddress): array
    {
        $value = trim((string) $shippingAddress);

        if ('' === $value) {
            return [null, null];
        }

        if (preg_match('/^(\d+[^\s,]*)\s+(.*)$/u', $value, $matches)) {
            return [trim($matches[2]), trim($matches[1])];
        }

        if (preg_match('/^(.*?)(?:\s+)(\d+[^\s,]*)$/u', $value, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return [$value, null];
    }

    private function canAccessSuccessPage(Order $order, string $accessToken): bool
    {
        if ('' !== $accessToken && hash_equals((string) $order->getCheckoutAccessToken(), $accessToken)) {
            return true;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return false;
        }

        return $order->getCustomerAccount()?->getId() === $user->getId()
            || $order->getCustomerEmail() === $user->getEmail();
    }
}
