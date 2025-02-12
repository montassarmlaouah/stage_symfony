<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\UploadFileService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class CategorieController extends AbstractController
{
    public function __construct(private UploadFileService $uploadFileService)
    {}

    #[Route('/categorie', name: 'categorie_list')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sortBy = $request->query->get('sortBy', 'id');
        $order = $request->query->get('order', 'asc');

        $validSortFields = ['id', 'name', 'description', 'produit'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'id';
        }

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        $categories = $entityManager->getRepository(Categorie::class)
            ->findBy([], [$sortBy => $order]);

        return $this->render('categorie/list.html.twig', [
            'categories' => $categories,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/categorie/add', name: 'categorie.add', methods: ['GET', 'POST'])]
    public function addCategorie(Request $request, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
    {
        $categorie = new Categorie();
        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $categorie->setImage($this->uploadFileService->upload($imageFile));
            }

            $entityManager->persist($categorie);
            $entityManager->flush();

            $categorieRepository->reassignIds();

            $this->addFlash('success', 'Catégorie ajoutée avec succès.');

            return $this->redirectToRoute('categorie_list');
        }

        return $this->render('categorie/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categorie/edit/{id}', name: 'categorie.edit', methods: ['GET', 'POST'])]
    public function editCategorie(Request $request, Categorie $categorie, EntityManagerInterface $entityManager, CategorieRepository $categorieRepository): Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $categorie->setImage($this->uploadFileService->upload($imageFile));
            }

            $entityManager->flush();

            $categorieRepository->reassignIds();

            $this->addFlash('success', 'Catégorie modifiée avec succès.');

            return $this->redirectToRoute('categorie_list');
        }

        return $this->render('categorie/edit.html.twig', [
            'form' => $form->createView(),
            'categorie' => $categorie,
        ]);
    }

    #[Route('/categorie/delete/{id}', name: 'categorie.delete', methods: ['POST'])]
    public function deleteCategorie(int $id, Request $request, CategorieRepository $categorieRepository): Response
    {
        $categorie = $categorieRepository->find($id);

        if (!$categorie) {
            throw $this->createNotFoundException('Catégorie non trouvée.');
        }

        if ($this->isCsrfTokenValid('delete' . $categorie->getId(), $request->request->get('_token'))) {
            $categorieRepository->deleteAndReassignIds($id);
            $this->addFlash('success', 'Catégorie supprimée avec succès.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('categorie_list');
    }

    #[Route('/categorie/details/{id}', name: 'categorie.details', methods: ['GET'])]
    public function details(Categorie $categorie): Response
    {
        return $this->render('categorie/details.html.twig', [
            'categorie' => $categorie,
        ]);
    }
    
}
