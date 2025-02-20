<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\User;
use App\Form\ClientRegistrationFormType;
use App\Form\LoginFormType;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
class ClientController extends AbstractController
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    #[Route('client/login', name: 'client_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        EntityManagerInterface $entityManager,
        TokenStorageInterface $tokenStorage
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('page_client');
        }

        $form = $this->createForm(LoginFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            $password = $data['password'];

            $user = $entityManager->getRepository(Client::class)->findOneBy(['email' => $email]);

            if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Identifiants invalides.');
                return $this->redirectToRoute('client_login');
            }

            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);

            $this->addFlash('success', 'Connexion réussie.');

            $redirectTo = $request->get('redirect_to', 'page_client');
            return $this->redirectToRoute($redirectTo);
        }

        return $this->render('client/login.html.twig', [
            'loginForm' => $form->createView(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }
    

    #[Route('client/register', name: 'client_register')]
    public function register(Request $request, EntityManagerInterface $entityManager): Response
    {

        if ($this->getUser()) {
            return $this->redirectToRoute('page_client');
        }

        $client = new Client();
        $form = $this->createForm(ClientRegistrationFormType::class, $client);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $client->setPassword(
                $this->passwordHasher->hashPassword(
                    $client,
                    $plainPassword
                )
            );
        
            $entityManager->persist($client);
            $entityManager->flush();
        
        
            $this->addFlash('success', 'Compte créé avec succès.');

            return $this->redirectToRoute('client_login');
        }

        return $this->render('client/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('client/list', name: 'client_list')]
    public function list(ClientRepository $clientRepository): Response
    {
        $clients = $clientRepository->findAllOrderedById();

        return $this->render('client/list.html.twig', [
            'clients' => $clients,
        ]);
    }
    public function yourControllerAction(TokenStorageInterface $tokenStorage): Response
{
    $token = $tokenStorage->getToken();
    $client = $token ? $token->getUser() : null;

    
    $clientName = $client instanceof Client ? $client->getName() : null;
    return $this->render('base2.html.twig', [
        'name' => $clientName,
    ]);


}

#[Route('client/edit', name: 'client_edit')]
public function edit(Request $request, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage): Response
{
    $token = $tokenStorage->getToken();
    $client = $token ? $token->getUser() : null;
    $session = $request->getSession();
    $panier = $session->get('panier', []);

  

    
    $items = [];
    $total = 0;

    foreach ($panier as $id => $data) {
        if (isset($data['price'], $data['quantity'], $data['title'], $data['image'])) {
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
    if (!$client instanceof Client) {
        throw $this->createAccessDeniedException('Vous devez être connecté pour modifier vos informations.');
    }

    $formedit = $this->createForm(ClientRegistrationFormType::class, $client);
    $formedit->remove('plainPassword'); 
    $formedit->add('currentPassword', PasswordType::class, [
        'label_attr' => ['style' => 'color: blue;'],
        'mapped' => false,
        'required' => true,
        'label' => 'Ancien mot de passe',
    ]);
    $formedit->add('newPassword', PasswordType::class, [
      
        'mapped' => false,
        'required' => true,
        'label_attr' => ['style' => 'color: blue;'],
        'label' => 'Nouveau mot de passe',
    ]);
    $formedit->handleRequest($request);

    if ($formedit->isSubmitted() && $formedit->isValid()) {
        $currentPassword = $formedit->get('currentPassword')->getData();
        $newPassword = $formedit->get('newPassword')->getData();

        if (!$this->passwordHasher->isPasswordValid($client, $currentPassword)) {
            $this->addFlash('error', 'Ancien mot de passe incorrect.');
        } else {
            $client->setPassword(
                $this->passwordHasher->hashPassword(
                    $client,
                    $newPassword
                )
            );
            $entityManager->flush();

            $this->addFlash('success', 'Informations du compte mises à jour avec succès.');
            return $this->redirectToRoute('page_client');
        }
    }

    return $this->render('client/edit.html.twig', [
        'formedit' => $formedit->createView(),
        'items' => $panier,
            'total' => $total,
    ]);
}
#[Route('client/logout', name: 'client_logout')]
public function logout(): void
{
    throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall.');
}
}
