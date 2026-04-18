<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Concert;
use App\Entity\Product;
use App\Entity\SiteSettings;
use App\Entity\User;
use App\Service\SiteOwnerManager;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[AdminDashboard(routePath: '/backstage', routeName: 'admin_dashboard')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AdminUrlGeneratorInterface $adminUrlGenerator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly SiteOwnerManager $siteOwnerManager,
    ) {
    }

    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/dashboard.html.twig', [
            'productCount' => $this->entityManager->getRepository(Product::class)->count([]),
            'publishedProductCount' => $this->entityManager->getRepository(Product::class)->count(['isPublished' => true]),
            'categoryCount' => $this->entityManager->getRepository(Category::class)->count([]),
            'concertCount' => $this->entityManager->getRepository(Concert::class)->count([]),
            'publishedConcertCount' => $this->entityManager->getRepository(Concert::class)->count(['isPublished' => true]),
            'visualPresetCount' => $this->entityManager->getRepository(SiteSettings::class)->count([]),
            'adminCount' => (int) $this->entityManager->createQuery('SELECT COUNT(u.id) FROM App\Entity\User u WHERE u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%')
                ->getSingleScalarResult(),
            'productsUrl' => $this->buildAdminIndexUrl(ProductCrudController::class),
            'categoriesUrl' => $this->buildAdminIndexUrl(CategoryCrudController::class),
            'concertsUrl' => $this->buildAdminIndexUrl(ConcertCrudController::class),
            'siteSettingsUrl' => $this->buildAdminIndexUrl(SiteSettingsCrudController::class),
            'usersUrl' => $this->buildAdminIndexUrl(UserCrudController::class),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle(sprintf('Backstage %s', $this->siteOwnerManager->getOwnerName()));
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('styles/admin-easyadmin.css');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return UserMenu::new()
            ->setName(method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : null)
            ->displayUserAvatar(false)
            ->setMenuItems([
                MenuItem::linkToRoute('Voir le site', 'fas fa-globe', 'store_home'),
                MenuItem::linkToRoute('Déconnexion', 'fas fa-right-from-bracket', 'app_logout'),
            ]);
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Vue d’ensemble', 'fas fa-gauge-high');

        yield MenuItem::subMenu('Merch', 'fas fa-boxes-stacked')->setSubItems([
            MenuItem::linkTo(ProductCrudController::class, 'Produits', 'fas fa-shirt'),
            MenuItem::linkTo(CategoryCrudController::class, 'Catégories', 'fas fa-tags'),
        ]);

        yield MenuItem::subMenu('Concerts', 'fas fa-music')->setSubItems([
            MenuItem::linkTo(ConcertCrudController::class, 'Dates de concert', 'fas fa-calendar-days'),
        ]);

        yield MenuItem::subMenu('Fans', 'fas fa-users')->setSubItems([
            MenuItem::linkTo(CustomerCrudController::class, 'Fans', 'fas fa-user-group'),
            MenuItem::linkTo(CustomerConversationCrudController::class, 'Messages fans', 'fas fa-comments'),
            MenuItem::linkTo(OrderConversationCrudController::class, 'Support commandes', 'fas fa-headset'),
        ]);

        yield MenuItem::subMenu('Identité', 'fas fa-bolt')->setSubItems([
            MenuItem::linkTo(GalleryPhotoCrudController::class, 'Photos galerie', 'fas fa-images'),
            MenuItem::linkTo(SiteSettingsCrudController::class, 'Visuels du site', 'fas fa-image'),
        ]);

        yield MenuItem::subMenu('Administration', 'fas fa-user-shield')->setSubItems([
            MenuItem::linkTo(UserCrudController::class, 'Administrateurs', 'fas fa-user-shield'),
            MenuItem::linkToRoute('Voir le site', 'fas fa-globe', 'store_home'),
        ]);
    }

    private function buildAdminIndexUrl(string $controllerFqcn): string
    {
        return (clone $this->adminUrlGenerator)
            ->unsetAll()
            ->setDashboard(self::class)
            ->setController($controllerFqcn)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }
}
