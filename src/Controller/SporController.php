<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SporController extends AbstractController
{
    #[Route('/spor', name: 'app_spor')]
    public function index(): Response
    {
        return $this->render('spor/index.html.twig', [
            'controller_name' => 'SporController',
        ]);
    }
}
