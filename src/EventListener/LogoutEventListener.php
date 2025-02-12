<?php
namespace App\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutEventListener
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $event->getToken();

        if ($token && in_array('ROLE_CLIENT', $token->getRoleNames())) {
            $response = new RedirectResponse($this->router->generate('client_login'));
        } else {
            $response = new RedirectResponse($this->router->generate('user_login'));
        }

        $event->setResponse($response);
    }
}
