<?php

namespace App\Repository;

use App\Entity\Article;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;


class ArticleRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Article::class);
        $this->entityManager = $entityManager;
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

   
    public function reassignIds(): void
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Désactiver les contraintes de clés étrangères
        $conn->executeQuery('SET FOREIGN_KEY_CHECKS=0');
        
        try {
            // Créer une table temporaire pour stocker les anciens et nouveaux IDs
            $conn->executeQuery('CREATE TEMPORARY TABLE temp_ids AS 
                SELECT id as old_id, ROW_NUMBER() OVER (ORDER BY id) as new_id 
                FROM article');

            // Mettre à jour les références dans la table image
            $conn->executeQuery('UPDATE image i 
                JOIN temp_ids t ON i.article_id = t.old_id 
                SET i.article_id = t.new_id');

            // Mettre à jour les IDs dans la table article
            $conn->executeQuery('UPDATE article a 
                JOIN temp_ids t ON a.id = t.old_id 
                SET a.id = t.new_id');

            // Réinitialiser l'auto-increment
            $conn->executeQuery('ALTER TABLE article AUTO_INCREMENT = 1');
            
            // Supprimer la table temporaire
            $conn->executeQuery('DROP TEMPORARY TABLE temp_ids');
        } finally {
            // Réactiver les contraintes de clés étrangères
            $conn->executeQuery('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
