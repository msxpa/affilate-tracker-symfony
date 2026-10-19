<?php
#affiliate-tracker-symfony/backend/src/Controller/Admin/DashboardController.php
namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard; // <-- Новый use
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
// use App\Entity\Offer;
// use App\Entity\User;
use App\Controller\Admin\OfferCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')] // <-- Атрибут на классе
class DashboardController extends AbstractDashboardController
{
    private $adminUrlGenerator;

    public function __construct( AdminUrlGenerator $adminUrlGenerator ) {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    // Метод index() теперь без атрибута Route
    public function index(): Response
    {
        return $this->redirect(
            $this->adminUrlGenerator
            ->setController(OfferCrudController::class)
            ->generateUrl()
        );
        // return parent::index();
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Affiliate Tracker Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkTo(OfferCrudController::class, 'Offers', 'fa fa-home');
        yield MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-home');

        //без yield
        // return [
        //     MenuItem::linkToDashboard('Dashboard', 'fa fa-home'),
        //     MenuItem::linkTo(OfferCrudController::class, 'Offers', 'fa fa-tags'),
        //     MenuItem::linkTo(UserCrudController::class, 'Users', 'fa fa-users'),
        // ];
    }
}