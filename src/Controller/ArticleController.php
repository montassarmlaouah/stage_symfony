<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Image;
use App\Repository\ArticleRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[IsGranted('ROLE_ADMIN')]
class ArticleController extends AbstractController
{
    #[Route('/article', name: 'article_list')]
    public function list(Request $request, ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy([], ['id' => 'ASC']);

        $sortBy = $request->query->get('sortBy', 'id');
        $order = $request->query->get('order', 'asc');

        $validSortFields = ['id', 'title', 'content', 'price', 'stock'];
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'id';
        }

        if (!in_array($order, ['asc', 'desc'])) {
            $order = 'asc';
        }

        $articles = $articleRepository->findBy([], [$sortBy => $order]);

        return $this->render('article/list.html.twig', [
            'articles' => $articles,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/article/add', name: 'article.add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
      
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
                $article->setImage($newFilename);
            }
        
            $imageFiles = $form->get('images')->getData();
            foreach ($imageFiles as $imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
        
                $image = new Image();
                $image->setFilename($newFilename);
                $image->setArticle($article);
        
                $article->addImage($image);
            }
        
            $entityManager->persist($article);
            $entityManager->flush();

            
            $articleRepository->reassignIds();
        
            $this->addFlash('success', 'Article ajouté avec succès.');
            return $this->redirectToRoute('article_list');
        }

        return $this->render('article/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/article/edit/{id}', name: 'article.edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);
                $article->setImage($newFilename);
            }

         
            $imageFiles = $form->get('images')->getData();
            foreach ($imageFiles as $imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $imageFile->move($this->getParameter('images_directory'), $newFilename);

                $image = new Image();
                $image->setFilename($newFilename);
                $image->setArticle($article);

                $article->addImage($image);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Article modifié avec succès.');
            return $this->redirectToRoute('article_index');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article,
        ]);
    }

    #[Route('/article/{id}/delete-image/{imageId}', name: 'article_delete_image', methods: ['DELETE'])]
public function deleteImage(int $id, int $imageId, EntityManagerInterface $entityManager): JsonResponse
{
    $article = $entityManager->getRepository(Article::class)->find($id);
    $image = $entityManager->getRepository(Image::class)->find($imageId);

    if (!$article || !$image) {
        return new JsonResponse(['error' => 'Article ou image introuvable.'], Response::HTTP_NOT_FOUND);
    }

    if ($article->getImages()->contains($image)) {
        $article->removeImage($image);
        $entityManager->remove($image);
        $entityManager->flush();

        return new JsonResponse(['success' => 'Image supprimée avec succès.']);
    } else {
        return new JsonResponse(['error' => 'Image non associée à cet article.'], Response::HTTP_BAD_REQUEST);
    }
}

#[Route('/article/delete/{id}', name: 'article.delete', methods: ['POST'])]
public function delete(Request $request, int $id, EntityManagerInterface $entityManager, ArticleRepository $articleRepository): Response
{
    $article = $entityManager->getRepository(Article::class)->find($id);

    if (!$article) {
        $this->addFlash('error', 'Article non trouvé.');
        return $this->redirectToRoute('article_list');
    }

    if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
        try {
            $entityManager->remove($article);
            $entityManager->flush();

            
            $articleRepository->reassignIds();

            $this->addFlash('success', 'Article supprimé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }

    return $this->redirectToRoute('article_list');
}


    #[Route('/article/details/{id}', name: 'article.details', methods: ['GET'])]
    public function details(Article $article): Response
    {
        return $this->render('article/details.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/stock', name: 'stock_list')]
    public function listStock(EntityManagerInterface $entityManager): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();

        return $this->render('article/stock.html.twig', [
            'articles' => $articles,
        ]);
    }
    #[Route('/accueil', name: 'app_homepage')]
    public function homepage(EntityManagerInterface $entityManager, CommandeRepository $commandeRepository): Response
    {
        $articles = $entityManager->getRepository(Article::class)->findAll();
        $topProducts = $commandeRepository->getTopProducts();
        $ordersByProduct = $commandeRepository->getOrdersByProduct();
        $commandStats = $commandeRepository->getCommandStats();

        return $this->render('homepage/index.html.twig', [
            'message' => 'Bienvenue sur la page d\'accueil ',
            'articles' => $articles,
            'topProducts' => $topProducts,
            'ordersByProduct' => $ordersByProduct,
            'commandStats' => $commandStats,
        ]);
    }
}
