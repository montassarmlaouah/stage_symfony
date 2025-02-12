<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionManager
{
    private SessionInterface $session;

    public function __construct(RequestStack $requestStack)
    {
        $this->session = $requestStack->getSession();
    }

    public function getPanier(): array
    {
        return $this->session->get('panier', []);
    }

    public function setPanier(array $panier): void
    {
        $this->session->set('panier', $panier);
        $this->session->save(); 
    }
}
