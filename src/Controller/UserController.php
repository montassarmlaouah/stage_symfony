<?php

// se partie est de admin
namespace App\Controller;

use App\Form\ConnexionFormType;
use App\Form\UserType;
use App\Form\InscriptionFormType;
use App\Entity\User;
use App\Entity\Commande;
use App\Entity\PanierItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use App\Repository\ArticleRepository;
use App\Repository\CategorieRepository;
use App\Entity\Info;
use App\Entity\Contact;
use App\Form\InfoType;
use App\Repository\InfoRepository;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
  
    #[Route('/commandes', name: 'liste_commandes')]
    public function listCommandes(Request $request, EntityManagerInterface $entityManager): Response
    {
        $search = $request->query->get('q', ''); 
        $repository = $entityManager->getRepository(Commande::class);

        // Filtrer les commandes selon le champ de recherche
        $queryBuilder = $repository->createQueryBuilder('c');

        if (!empty($search)) {
            $queryBuilder
                ->where('c.client.email LIKE :search')
                ->orWhere('c.status LIKE :search')
                ->orWhere('c.id LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $commandes = $queryBuilder->getQuery()->getResult();

        return $this->render('user/liste_commandes.html.twig', [
            'commandes' => $commandes,
        ]);
    }
    #[Route('/update-status/{id}', name: 'update_status', methods: ['POST'])]
    public function updateStatus(Request $request, EntityManagerInterface $entityManager, $id): Response
    {
        $commande = $entityManager->getRepository(Commande::class)->find($id);
        if (!$commande) {
            $this->addFlash('error', 'Commande introuvable.');
            return $this->redirectToRoute('liste_commandes');
        }
    
        $newStatus = $request->request->get('status');
        $commande->setStatus($newStatus);
        $entityManager->flush();
    
        $this->addFlash('success', 'Le statut de la commande a été mis à jour.');
        return $this->redirectToRoute('liste_commandes');
    }
    
    #[Route('/search', name: 'app_search')]
    public function search(Request $request, ArticleRepository $articleRepo, ?CategorieRepository $categorieRepo = null): Response
    {
        $query = $request->query->get('q');

        $articles = $articleRepo->searchByTerm($query);
        $categories = $categorieRepo ? $categorieRepo->searchByTerm($query) : [];

        return $this->render('user/search.html.twig', [
            'query' => $query,
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

   
    #[Route('/info/add', name: 'info_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $info = new Info();
        $form = $this->createForm(InfoType::class, $info);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($info);
            $entityManager->flush();

            return $this->redirectToRoute('info_list');
        }

        return $this->render('info/add-info.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/info/edit/{id}', name: 'info_edit')]
    public function edit(Request $request, Info $info, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InfoType::class, $info);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('info_list');
        }

        $this->addFlash('success', 'Information mise à jour avec succès.');

        return $this->render('info/edit-info.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/info/delete/{id}', name: 'info_delete', methods: ['POST'])]
    public function delete(Request $request, int $id, EntityManagerInterface $entityManager, InfoRepository $infoRepository): Response
    {
        $info = $entityManager->getRepository(Info::class)->find($id);

        if (!$info) {
            $this->addFlash('error', 'Article not found.');
            return $this->redirectToRoute('info_list');
        }

        if ($this->isCsrfTokenValid('delete' . $info->getId(), $request->request->get('_token'))) {
            $entityManager->remove($info);
            $entityManager->flush();

            $this->addFlash('success', 'Article deleted successfully.');
        }

        return $this->redirectToRoute('info_list');
    }

    #[Route('/information', name: 'info_list')]
    public function listInfo(InfoRepository $infoRepository): Response
    {
        $infos = $infoRepository->findAll();
    
        return $this->render('info/list.html.twig', [
            'infos' => $infos,
        ]);
    }
    #[Route('/contact-list', name: 'contact_list')]
public function list(EntityManagerInterface $entityManager): Response
{
    $contacts = $entityManager->getRepository(Contact::class)->findAll();

    return $this->render('user/list-contact.html.twig', [
        'contacts' => $contacts,
    ]);
}

}