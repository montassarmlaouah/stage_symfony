<?php


    namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class RedirectController extends AbstractController
{
    #[Route('/redirect-after-logout', name: 'redirect_after_logout')]
    public function redirectAfterLogout(): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('page_client'));
    }
}


