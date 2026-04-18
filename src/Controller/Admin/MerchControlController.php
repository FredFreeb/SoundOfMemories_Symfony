<?php

namespace App\Controller\Admin;

use App\Entity\MailingCampaign;
use App\Entity\Order;
use App\Entity\Product;
use App\Service\OrderWorkflowManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/backstage/merch')]
final class MerchControlController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly OrderWorkflowManager $orderWorkflowManager,
    ) {
    }

    #[Route('/pilotage', name: 'admin_merch_operations', methods: ['GET'])]
    public function operations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orders = $this->entityManager->getRepository(Order::class)->findBy([], ['createdAt' => 'DESC']);
        $products = $this->entityManager->getRepository(Product::class)->findBy(['isPublished' => true], ['sortPosition' => 'ASC', 'updatedAt' => 'DESC']);

        $lanes = [
            'toPrepare' => [],
            'preparing' => [],
            'shipped' => [],
            'issues' => [],
        ];

        foreach ($orders as $order) {
            if (!$order instanceof Order) {
                continue;
            }

            if ($this->canPrepare($order)) {
                $lanes['toPrepare'][] = $this->normalizeOrder($order);
                continue;
            }

            if ($this->canShip($order)) {
                $lanes['preparing'][] = $this->normalizeOrder($order);
                continue;
            }

            if ($this->canClose($order)) {
                $lanes['shipped'][] = $this->normalizeOrder($order);
                continue;
            }

            if ($this->canFlagIssue($order) && Order::DELIVERY_STATUS_ISSUE === $order->getDeliveryStatus()) {
                $lanes['issues'][] = $this->normalizeOrder($order);
            }
        }

        $lowStockProducts = [];
        $outOfStockProducts = [];

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            if ($product->getDisplayStock() <= 0) {
                $outOfStockProducts[] = $this->normalizeProduct($product);
                continue;
            }

            if ($product->getDisplayStock() <= 5) {
                $lowStockProducts[] = $this->normalizeProduct($product);
            }
        }

        return $this->render('admin/merch_operations.html.twig', [
            'lanes' => [
                [
                    'key' => 'toPrepare',
                    'title' => 'À préparer',
                    'description' => 'Paiement validé, prêt à passer en préparation.',
                    'orders' => $lanes['toPrepare'],
                ],
                [
                    'key' => 'preparing',
                    'title' => 'En préparation',
                    'description' => 'Colis en cours de préparation ou étiquette créée.',
                    'orders' => $lanes['preparing'],
                ],
                [
                    'key' => 'shipped',
                    'title' => 'Expédiées',
                    'description' => 'Suivis partis, à clôturer après confirmation.',
                    'orders' => $lanes['shipped'],
                ],
                [
                    'key' => 'issues',
                    'title' => 'Incidents',
                    'description' => 'Cas à reprendre vite côté transport ou service fan.',
                    'orders' => $lanes['issues'],
                ],
            ],
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
        ]);
    }

    #[Route('/promotions', name: 'admin_merch_promotions', methods: ['GET'])]
    public function promotions(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $products = $this->entityManager->getRepository(Product::class)->findBy(['isPublished' => true], ['sortPosition' => 'ASC', 'updatedAt' => 'DESC']);
        $mailings = $this->entityManager->getRepository(MailingCampaign::class)->findBy([], ['scheduledAt' => 'ASC', 'createdAt' => 'DESC']);

        $activePromotions = [];
        $upcomingPromotions = [];
        $expiredPromotions = [];
        $alwaysOnPromotions = [];

        foreach ($products as $product) {
            if (!$product instanceof Product || !$product->hasPromotionPricing()) {
                continue;
            }

            $normalized = $this->normalizeProduct($product);

            if ($product->isPromotionUpcoming()) {
                $upcomingPromotions[] = $normalized;
                continue;
            }

            if ($product->isPromotionExpired()) {
                $expiredPromotions[] = $normalized;
                continue;
            }

            if ($product->hasPromotionSchedule()) {
                $activePromotions[] = $normalized;
                continue;
            }

            $alwaysOnPromotions[] = $normalized;
        }

        $mailingStats = [
            'draft' => 0,
            'scheduled' => 0,
            'ready' => 0,
            'sent' => 0,
        ];

        foreach ($mailings as $mailing) {
            if ($mailing instanceof MailingCampaign && isset($mailingStats[$mailing->getStatus()])) {
                ++$mailingStats[$mailing->getStatus()];
            }
        }

        return $this->render('admin/merch_promotions.html.twig', [
            'activePromotions' => $activePromotions,
            'upcomingPromotions' => $upcomingPromotions,
            'expiredPromotions' => $expiredPromotions,
            'alwaysOnPromotions' => $alwaysOnPromotions,
            'mailingStats' => $mailingStats,
            'mailingsUrl' => $this->buildAdminCrudUrl(MailingCampaignCrudController::class, Action::INDEX),
            'productsUrl' => $this->buildAdminCrudUrl(ProductCrudController::class, Action::INDEX),
        ]);
    }

    #[Route('/orders/{id}/prepare', name: 'admin_merch_order_prepare', methods: ['POST'])]
    public function prepareOrder(Order $order, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_order_prepare_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        if (!$this->canPrepare($order)) {
            $this->addFlash('warning', 'Cette commande ne peut pas encore passer en préparation.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        $order
            ->setStatus(Order::STATUS_PROCESSING)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_PREPARING);

        $this->flushOrder($order);
        $this->addFlash('success', 'Commande passée en préparation.');

        return $this->redirectBack($request, 'admin_merch_operations');
    }

    #[Route('/orders/{id}/ship', name: 'admin_merch_order_ship', methods: ['POST'])]
    public function shipOrder(Order $order, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_order_ship_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        if (!$this->canShip($order)) {
            $this->addFlash('warning', 'Cette commande ne peut pas encore passer en expédition.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        $order
            ->setStatus(Order::STATUS_SHIPPED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_IN_TRANSIT);

        $this->flushOrder($order);
        $this->addFlash('success', 'Commande marquée comme expédiée.');

        return $this->redirectBack($request, 'admin_merch_operations');
    }

    #[Route('/orders/{id}/receive', name: 'admin_merch_order_receive', methods: ['POST'])]
    public function receiveOrder(Order $order, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_order_receive_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        if (!$this->canClose($order)) {
            $this->addFlash('warning', 'Cette commande ne peut pas encore être clôturée.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        $order
            ->setStatus(Order::STATUS_CLOSED)
            ->setDeliveryStatus(Order::DELIVERY_STATUS_RECEIVED);

        $this->flushOrder($order);
        $this->addFlash('success', 'Commande clôturée.');

        return $this->redirectBack($request, 'admin_merch_operations');
    }

    #[Route('/orders/{id}/issue', name: 'admin_merch_order_issue', methods: ['POST'])]
    public function issueOrder(Order $order, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_order_issue_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        if (!$this->canFlagIssue($order)) {
            $this->addFlash('warning', 'Cette commande ne peut pas être basculée en incident.');

            return $this->redirectBack($request, 'admin_merch_operations');
        }

        $order->setDeliveryStatus(Order::DELIVERY_STATUS_ISSUE);

        $this->flushOrder($order);
        $this->addFlash('success', 'Incident de livraison signalé.');

        return $this->redirectBack($request, 'admin_merch_operations');
    }

    #[Route('/promotions/{id}/start-now', name: 'admin_merch_promotion_start', methods: ['POST'])]
    public function startPromotion(Product $product, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_promotion_start_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_promotions');
        }

        $now = new \DateTimeImmutable();
        $product->setPromotionStartsAt($now);

        if ($product->getPromotionEndsAt() instanceof \DateTimeImmutable && $product->getPromotionEndsAt() < $now) {
            $product->setPromotionEndsAt(null);
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'La promotion démarre maintenant.');

        return $this->redirectBack($request, 'admin_merch_promotions');
    }

    #[Route('/promotions/{id}/stop', name: 'admin_merch_promotion_stop', methods: ['POST'])]
    public function stopPromotion(Product $product, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_promotion_stop_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_promotions');
        }

        $product
            ->setPromotionEndsAt(new \DateTimeImmutable())
            ->setIsMonthlyOffer(false);

        $this->entityManager->flush();
        $this->addFlash('success', 'La promotion a été arrêtée.');

        return $this->redirectBack($request, 'admin_merch_promotions');
    }

    #[Route('/promotions/{id}/highlight', name: 'admin_merch_promotion_highlight', methods: ['POST'])]
    public function highlightPromotion(Product $product, Request $request): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('admin_merch_promotion_highlight_' . $product->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action refusée, le jeton de sécurité est invalide.');

            return $this->redirectBack($request, 'admin_merch_promotions');
        }

        foreach ($this->entityManager->getRepository(Product::class)->findBy(['isMonthlyOffer' => true]) as $highlightedProduct) {
            if ($highlightedProduct instanceof Product && $highlightedProduct !== $product) {
                $highlightedProduct->setIsMonthlyOffer(false);
            }
        }

        $product->setIsMonthlyOffer(true);

        if ($product->hasPromotionPricing() && $product->isPromotionUpcoming()) {
            $product->setPromotionStartsAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Ce produit est maintenant mis en avant comme offre du mois.');

        return $this->redirectBack($request, 'admin_merch_promotions');
    }

    private function flushOrder(Order $order): void
    {
        foreach ($this->orderWorkflowManager->synchronize($order) as $message) {
            $this->addFlash('info', $message);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeOrder(Order $order): array
    {
        return [
            'id' => $order->getId(),
            'reference' => $order->getReferenceLabel(),
            'customer' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
            'items' => $order->getItemsSummary(),
            'itemsCount' => $order->getItemsCount(),
            'destination' => $order->getShippingSummary(),
            'total' => $order->getFormattedTotal(),
            'createdAt' => $order->getCreatedAt(),
            'status' => $order->getStatusLabel(),
            'statusTone' => $this->mapOrderStatusTone($order->getStatus()),
            'payment' => $order->getPaymentStatusLabel(),
            'paymentTone' => $this->mapPaymentStatusTone($order->getPaymentStatus()),
            'delivery' => $order->getDeliveryStatusLabel(),
            'deliveryTone' => $this->mapDeliveryStatusTone($order->getDeliveryStatus()),
            'editUrl' => $this->buildAdminCrudUrl(OrderCrudController::class, Action::EDIT, $order->getId()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeProduct(Product $product): array
    {
        $defaultVariant = $product->getDefaultVariant();
        $comparePrice = $defaultVariant?->getFormattedCompareAtPrice();
        $windowLabel = 'Sans fenêtre';

        if ($product->hasPromotionSchedule()) {
            $parts = [];

            if ($product->getPromotionStartsAt() instanceof \DateTimeImmutable) {
                $parts[] = 'du ' . $product->getPromotionStartsAt()->format('d/m');
            }

            if ($product->getPromotionEndsAt() instanceof \DateTimeImmutable) {
                $parts[] = 'au ' . $product->getPromotionEndsAt()->format('d/m');
            }

            $windowLabel = implode(' ', $parts);
        }

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'category' => $product->getCategory()?->getName() ?: 'Merch',
            'badge' => $product->getMerchBadge(),
            'stock' => $product->getDisplayStock(),
            'price' => $product->getFormattedStartingPrice(),
            'comparePrice' => $comparePrice,
            'promotionState' => $product->getPromotionStateLabel(),
            'promotionTone' => $this->mapPromotionTone($product->getPromotionStateLabel()),
            'windowLabel' => $windowLabel,
            'isMonthlyOffer' => $product->isMonthlyOffer(),
            'frontUrl' => $this->urlGenerator->generate('store_shop_show', ['slug' => $product->getSlug()]),
            'editUrl' => $this->buildAdminCrudUrl(ProductCrudController::class, Action::EDIT, $product->getId()),
        ];
    }

    private function buildAdminCrudUrl(string $controller, string $action, ?int $entityId = null): string
    {
        $urlGenerator = (clone $this->adminUrlGenerator)
            ->unsetAll()
            ->setDashboard(DashboardController::class)
            ->setController($controller)
            ->setAction($action);

        if (null !== $entityId) {
            $urlGenerator->setEntityId($entityId);
        }

        return $urlGenerator->generateUrl();
    }

    private function redirectBack(Request $request, string $fallbackRoute): RedirectResponse
    {
        $referer = $request->headers->get('referer');

        if (\is_string($referer) && '' !== $referer) {
            return new RedirectResponse($referer);
        }

        return $this->redirectToRoute($fallbackRoute);
    }

    private function isPaymentValidated(Order $order): bool
    {
        return \in_array($order->getPaymentStatus(), [Order::PAYMENT_STATUS_PAID, Order::PAYMENT_STATUS_AUTHORIZED], true);
    }

    private function canPrepare(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_PENDING, Order::DELIVERY_STATUS_ISSUE], true);
    }

    private function canShip(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_PREPARING, Order::DELIVERY_STATUS_LABEL_CREATED], true);
    }

    private function canClose(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true)
            && \in_array($order->getDeliveryStatus(), [Order::DELIVERY_STATUS_IN_TRANSIT, Order::DELIVERY_STATUS_ISSUE], true);
    }

    private function canFlagIssue(Order $order): bool
    {
        return $this->isPaymentValidated($order)
            && !\in_array($order->getStatus(), [Order::STATUS_CANCELLED, Order::STATUS_REFUNDED, Order::STATUS_CLOSED], true);
    }

    private function mapOrderStatusTone(string $status): string
    {
        return match ($status) {
            Order::STATUS_PAID, Order::STATUS_CLOSED => 'success',
            Order::STATUS_PROCESSING => 'info',
            Order::STATUS_SHIPPED => 'primary',
            Order::STATUS_CANCELLED, Order::STATUS_REFUNDED => 'danger',
            default => 'warning',
        };
    }

    private function mapPaymentStatusTone(?string $status): string
    {
        return match ($status) {
            Order::PAYMENT_STATUS_PAID => 'success',
            Order::PAYMENT_STATUS_AUTHORIZED => 'info',
            Order::PAYMENT_STATUS_CANCELLED, Order::PAYMENT_STATUS_FAILED, Order::PAYMENT_STATUS_REFUNDED => 'danger',
            default => 'warning',
        };
    }

    private function mapDeliveryStatusTone(string $status): string
    {
        return match ($status) {
            Order::DELIVERY_STATUS_PREPARING, Order::DELIVERY_STATUS_LABEL_CREATED => 'info',
            Order::DELIVERY_STATUS_IN_TRANSIT => 'primary',
            Order::DELIVERY_STATUS_RECEIVED => 'success',
            Order::DELIVERY_STATUS_ISSUE => 'danger',
            default => 'secondary',
        };
    }

    private function mapPromotionTone(string $state): string
    {
        return match ($state) {
            'Active', 'Toujours active' => 'success',
            'Programmée' => 'warning',
            'Terminée' => 'secondary',
            default => 'light',
        };
    }
}
