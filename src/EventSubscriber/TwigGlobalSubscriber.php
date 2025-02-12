<?php
namespace App\EventSubscriber;

use App\Repository\CategorieRepository;
use App\Repository\InfoRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface
{
    private Environment $twig;
    private CategorieRepository $categorieRepository;
    private InfoRepository $infoRepository;
    private TokenStorageInterface $tokenStorage;
    private RequestStack $requestStack;

    public function __construct(
        Environment $twig,
        CategorieRepository $categorieRepository,
        InfoRepository $infoRepository,
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $this->twig = $twig;
        $this->categorieRepository = $categorieRepository;
        $this->infoRepository = $infoRepository;
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;

    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $session = $this->requestStack->getSession();

        $categories = $this->categorieRepository->findAll();
        $infos = $this->infoRepository->findAll();

        // Vérifier l'utilisateur connecté
        $token = $this->tokenStorage->getToken();
        $client = $token ? $token->getUser() : null;
        $user= $token ? $token->getUser() : null;


        // Récupérer le panier de la session
        $panier = $session->get('panier', []);
        
        
        $items = [];
        $total = 0;
        foreach ($panier as $id => $data) {
            if (!empty($data['price']) && !empty($data['quantity']) && !empty($data['title']) && !empty($data['image'])) {
                $items[] = [
                    'id' => $id,
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'quantity' => $data['quantity'],
                    'image' => $data['image']
                ];
                $total += $data['price'] * $data['quantity'];
            }
        }

        // Ajouter les variables globales pour Twig
        $this->twig->addGlobal('categories', $categories);
        $this->twig->addGlobal('infos', $infos);
        $this->twig->addGlobal('client', $client);
        $this->twig->addGlobal('items', $items);
        $this->twig->addGlobal('total', $total);
        $this->twig->addGlobal('user', $user);
       
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}