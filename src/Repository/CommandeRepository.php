<?php

namespace App\Repository;

use App\Entity\Commande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function getTopProducts(): array
    {
        return $this->createQueryBuilder('c')
            ->select('a.title AS produit, COUNT(c.id) AS nombreCommandes')
            ->join('c.articles', 'a')
            ->groupBy('a.id')
            ->orderBy('nombreCommandes', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function getOrdersByProduct(): array
    {
        $conn = $this->getEntityManager()->getConnection();
    
        $sql = "
            SELECT 
                article.title AS product_name, 
                SUM(commande.quantite) AS total_quantity
            FROM article
            JOIN commande_articles ON article.id = commande_articles.article_id
            JOIN commande ON commande_articles.commande_id = commande.id
            GROUP BY article.title
            ORDER BY total_quantity DESC;
        ";
    
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
    
        return $resultSet->fetchAllAssociative();
    }

public function getCommandStats(): array
{
    $conn = $this->getEntityManager()->getConnection();

    $sql = "
        SELECT 
            COUNT(c.id) AS total_commandes,
            SUM(c.total) AS total_commandes_value
        FROM commande c
    ";

    $stmt = $conn->prepare($sql);
    $resultSet = $stmt->executeQuery();

    return $resultSet->fetchAssociative(); // Renvoie un tableau avec 'total_commandes' et 'total_commandes_value'
}

    
}
