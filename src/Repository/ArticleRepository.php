<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Article>
 */
class ArticleRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Article::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Reassign IDs to ensure they are sequential starting from 1.
     */
    public function reassignIds(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // Fetch all articles ordered by ID
            $articles = $this->createQueryBuilder('a')
                ->orderBy('a.id', 'ASC')
                ->getQuery()
                ->getResult();

            // Reassign IDs to ensure they are sequential starting from 1
            $newId = 1;
            foreach ($articles as $article) {
                $conn->executeStatement('UPDATE article SET id = ? WHERE id = ?', [$newId, $article->getId()]);
                $newId++;
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.title LIKE :query OR a.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function searchByTerm(string $term): array
{
    return $this->createQueryBuilder('a')
        ->where('a.title LIKE :term OR a.content LIKE :term')
        ->setParameter('term', '%' . $term . '%')
        ->getQuery()
        ->getResult();
}
}