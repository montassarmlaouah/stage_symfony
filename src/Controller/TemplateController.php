<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Panier;
use App\Entity\Contact;
use App\Form\ContactType;
use App\Entity\Info;

class TemplateController extends AbstractController
{

    #[Route('/pageclient', name: 'page_client')]
    public function pageClient(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied for administrators.');
            $session->set('panier', $panier);
            return $this->redirectToRoute('panier');
        }


        $info = $entityManager->getRepository(Info::class)->findOneBy([]) ?? new Info();

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        $items = [];
        $total = 0;

        foreach ($panier as $id => $data) {
            if (isset($data['price'], $data['quantity'], $data['title'], $data['image'], $data['sizes'], $data['colors'])) {
                $items[] = [
                    'id' => $id,
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'quantity' => $data['quantity'],
                    'image' => $data['image'],
                    'sizes' => $data['sizes'],
                    'colors' => $data['colors']

                ];
                $total += $data['price'] * $data['quantity'];
            }
        }

        $categories = $entityManager->getRepository(Categorie::class)->findAll();

        return $this->render('homepage/homepage.html.twig', [
            'categories' => $categories,
            'items' => $panier,
            'total' => $total,
            'info' => $info
        ]);
    }

    #[Route('/pagearticle/{id}', name: 'page_article')]
    public function pageArticle(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        $categorie = $entityManager->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée');
        }
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
                    'sizes' => $data['sizes'],
                    'colors' => $data['colors'],
                    'image' => $data['image']
                ];
                $total += $data['price'] * $data['quantity'];
            }
        }

        return $this->render('homepage/pagearticle.html.twig', [
            'categorie' => $categorie,
            'articles' => $categorie->getArticles(),
            'items' => $panier,
            'total' => $total,

        ]);
    }



    #[Route('/detailarticle/{id}', name: 'detail_article')]
    public function detailArticle(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }
        $session = $request->getSession();
        $panier = $session->get('panier', []);


        $items = [];
        $total = 0;

        foreach ($panier as $id => $data) {
            if (isset($data['price'], $data['quantity'], $data['title'], $data['image'], $data['sizes'], $data['colors'])) {
                $items[] = [
                    'id' => $id,
                    'title' => $data['title'],
                    'price' => $data['price'],
                    'quantity' => $data['quantity'],
                    'sizes' => $data['sizes'],
                    'colors' => $data['colors'],
                    'image' => $data['image']
                ];
                $total += $data['price'] * $data['quantity'];
            }
        }

        return $this->render('homepage/detailarticle.html.twig', [
            'article' => $article,
            'items' => $panier,
            'total' => $total,
            'sizes' => $article->getSizes(),
            'colors' => $article->getColors(),
        ]);
    }
    #[Route('/panier', name: 'panier')]
    public function viewPanier(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            return $this->render('homepage/panier_vide.html.twig');
        }

        return $this->render('homepage/panier.html.twig', [
            'items' => $panier,

            'total' => array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $panier)),
        ]);
    }


    #[Route('/panier/add/{id}', name: 'panier_add', methods: ['POST'])]
    public function addToPanier(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        $article = $entityManager->getRepository(Article::class)->find($id);

        if (!$article) {
            $this->addFlash('error', 'Article introuvable.');
            return $this->redirectToRoute('panier');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        $quantite = (int) $request->request->get('quantite', 1);
        $size = $request->request->get('size');
        $color = $request->request->get('color');

        $cartKey = $id . '-' . $size . '-' . $color;

        if (isset($panier[$cartKey])) {
            $panier[$cartKey]['quantity'] += $quantite;
        } else {
            $panier[$cartKey] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'price' => $article->getPrice(),
                'quantity' => $quantite,
                'image' => $article->getImage(),
                'size' => $size,
                'color' => $color
            ];
        }

        $session->set('panier', $panier);
        $this->addFlash('success', 'Article ajouté au panier.');

        return $this->redirectToRoute('panier');
    }

    #[Route('/panier/update/{id}', name: 'panier_update', methods: ['POST'])]
    public function updatePanier(Request $request, string $id): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);
        $quantity = (int) $request->request->get('quantity', 1);

        if (isset($panier[$id])) {
            if ($quantity > 0) {
                $panier[$id]['quantity'] = $quantity;
                $this->addFlash('success', 'Quantité mise à jour.');
            } else {
                unset($panier[$id]);
                $this->addFlash('success', 'Article retiré du panier.');
            }
            $session->set('panier', $panier);
        }

        return $this->redirectToRoute('panier');
    }



    #[Route('/panier/remove/{id}', name: 'panier_remove', methods: ['POST', 'DELETE'])]
    public function removeFromPanier(Request $request, string $id): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }

        if (!$this->isCsrfTokenValid('delete-item', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('panier');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        if (isset($panier[$id])) {
            unset($panier[$id]);
            $session->set('panier', $panier);
            $this->addFlash('success', 'Article supprimé du panier.');
        }

        return $this->redirectToRoute('panier');
    }
    #[Route('/commande/create', name: 'commande_create')]
    public function createCommande(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {

        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Accès refusé pour les administrateurs.');
        }


        $session = $request->getSession();
        $panier = $session->get('panier', []);
        if (empty($panier)) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier');
        }


        $client = $this->getUser();
        if (!$client || !$client instanceof Client) {
            $this->addFlash('error', 'Vous devez être connecté pour valider une commande.');
            return $this->redirectToRoute('client_login');
        }


        $adresse = $request->request->get('adresse');
        if (empty($adresse)) {
            $this->addFlash('error', 'Veuillez fournir une adresse de livraison.');
            return $this->redirectToRoute('commande_validate');
        }
        $livraison = $request->request->get('livraison', 'standard');
        $paiement = $request->request->get('paiement', 'cash');


        $commande = new Commande();
        $commande->setDate(new \DateTime());
        $commande->setStatus('en attente');
        $commande->setClient($client);
        $commande->setAdresse($adresse);
        $commande->setLivraison($livraison);
        $commande->setPaiement($paiement);

        $sousTotal = 0;
        $totalQuantity = 0;
        $sizes = [];
        $colors = [];

        foreach ($panier as $item) {
            $article = $entityManager->getRepository(Article::class)->find($item['id']);
            if ($article) {
                $newStock = $article->getStock() - $item['quantity'];
                if ($newStock >= 0) {
                    $article->setStock($newStock);
                } else {
                    $this->addFlash('error', 'Stock insuffisant pour l\'article ' . $article->getTitle());
                    return $this->redirectToRoute('panier');
                }
                $entityManager->persist($article);
                $commande->addArticle($article);
                $sousTotal += $item['price'] * $item['quantity'];
                $totalQuantity += $item['quantity'];


                $sizes[] = $item['size'] ?? 'Non spécifié';
                $colors[] = $item['color'] ?? 'Non spécifié';
            }
        }


        $commande->setSizes(implode(',', $sizes));
        $commande->setColors(implode(',', $colors));


        $livraisonPrix = $livraison === 'express' ? 10.000 : 0.000;
        $total = $sousTotal + $livraisonPrix;
        $commande->setTotal($total);
        $commande->setQuantite($totalQuantity);


        $entityManager->persist($commande);
        $entityManager->flush();


        $session->remove('panier');
        $this->addFlash('success', 'Commande validée avec succès.');





        return $this->redirectToRoute('page_client');
    }


    #[Route('/commande/validate', name: 'commande_validate')]
    public function validateCommande(Request $request): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('Access denied for administrators.');
        }

        $session = $request->getSession();
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            $this->addFlash('error', 'Your cart is empty.');
            return $this->redirectToRoute('panier');
        }

        $client = $this->getUser();
        if (!$client || !$client instanceof Client) {
            $this->addFlash('error', 'You must be logged in to place an order.');
            return $this->redirectToRoute('client_login');
        }

        $sousTotal = 0;
        foreach ($panier as $item) {
            $sousTotal += $item['price'] * $item['quantity'];
        }

        $timbreFiscal = 1.000;
        $total = $sousTotal + $timbreFiscal;

        return $this->render('homepage/validate.html.twig', [
            'client' => $client,
            'items' => $panier,
            'sous_total' => $sousTotal,
            'timbre_fiscal' => $timbreFiscal,
            'total' => $total,
        ]);
    }
    #[Route('/mescommande', name: 'mes_commande')]
    public function mesCommandes(Request $request, EntityManagerInterface $entityManager): Response
    {
        $client = $this->getUser();

        if (!$client || !$client instanceof Client) {
            throw new AccessDeniedException('Vous devez être connecté pour accéder à vos commandes.');
        }
        $items = [];
        $total = 0;
        $session = $request->getSession();
        $panier = $session->get('panier', []);

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
        $commandes = $entityManager->getRepository(Commande::class)->findBy(['client' => $client]);

        return $this->render('homepage/mes_commandes.html.twig', [
            'commandes' => $commandes,
            'items' => $panier,
            'total' => $total,
        ]);
    }
    #[Route('/contact', name: 'contact')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($contact);
            $entityManager->flush();

            $this->addFlash('success', 'Votre message a été envoyé avec succès.');
            return $this->redirectToRoute('page_client');
        }
        $items = [];
        $total = 0;
        $session = $request->getSession();
        $panier = $session->get('panier', []);

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
        return $this->render('homepage/contact.html.twig', [
            'form' => $form->createView(),
            'items' => $panier,
            'total' => $total,
        ]);
    }
    #[Route('/aprops', name: 'aprops')]
    public function about(Request $request, EntityManagerInterface $entityManager): Response
    {
        $items = [];
        $total = 0;
        $session = $request->getSession();
        $panier = $session->get('panier', []);
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

        $apropos = $entityManager->getRepository(Info::class)->find(1);

        if (!$apropos) {
            throw $this->createNotFoundException('Info not found');
        }

        return $this->render('homepage/about.html.twig', [
            'items' => $panier,
            'total' => $total,
            'apropos' => $apropos->getApropos(),
        ]);
    }
}
