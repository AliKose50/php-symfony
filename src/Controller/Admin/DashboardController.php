<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\ProductImage;
use App\Entity\User;
use App\Entity\Cart;
use App\Entity\CartItem;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\CategoryCrudController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        // Admin dashboard sayfasını göster (güvenlik attribute'u kullanıcıyı otomatik kontrol eder)
        return $this->render('admin/dashboard.html.twig');
    }



    public function configureMenuItems(): iterable
    {
        // Ana Dashboard Linki
        yield MenuItem::linkToDashboard('Yönetim Paneli', 'fa fa-home');

        // --- Mağaza Yönetimi Grubu ---
        yield MenuItem::section('Mağaza Yönetimi', 'fa fa-store');
        yield MenuItem::linkToCrud('Kategoriler', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Ürünler', 'fa fa-boxes', Product::class);
        yield MenuItem::linkToCrud('Ürün Resimleri', 'fa fa-images', ProductImage::class);

        // --- Sipariş & Sepet Yönetimi Grubu ---
        yield MenuItem::section('Sipariş ve Sepetler', 'fa fa-truck');
        yield MenuItem::linkToCrud('Sepetler', 'fa fa-shopping-cart', Cart::class);
        yield MenuItem::linkToCrud('Sepet Öğeleri', 'fa fa-list', CartItem::class);

        // --- Kullanıcı Yönetimi Grubu ---
        yield MenuItem::section('Kullanıcılar', 'fa fa-users');
        yield MenuItem::linkToCrud('Kullanıcılar', 'fa fa-user', User::class);

        // Çıkış (Logout) Linki
        yield MenuItem::linkToLogout('Çıkış', 'fa fa-sign-out');
    }
}
