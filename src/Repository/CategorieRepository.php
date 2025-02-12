<?php
namespace App\Repository;

use App\Entity\Categorie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Categorie>
 */
class CategorieRepository extends ServiceEntityRepository
{
    private $entityManager;

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Categorie::class);
        $this->entityManager = $entityManager;
    }

    /**
     * Supprime une catégorie par ID et réattribue les IDs de manière séquentielle.
     *
     * @param int $id L'ID de la catégorie à supprimer.
     */
    public function deleteAndReassignIds(int $id): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // Delete the category with the specified ID
            $this->createQueryBuilder('c')
                ->delete()
                ->where('c.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->execute();

            // Reassign IDs to ensure they are sequential starting from 1
            $this->reassignIds();

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * Réattribue les IDs de manière séquentielle après l'ajout d'une catégorie.
     */
    public function reassignIds(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->beginTransaction();

        try {
            // Fetch all categories ordered by ID
            $categories = $this->createQueryBuilder('c')
                ->orderBy('c.id', 'ASC')
                ->getQuery()
                ->getResult();

            // Reassign IDs to ensure they are sequential starting from 1
            $newId = 1;
            foreach ($categories as $category) {
                $conn->executeStatement('UPDATE categorie SET id = ? WHERE id = ?', [$newId, $category->getId()]);
                $newId++;
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    }
    public function searchByTerm(string $term)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :term OR c.description LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }
}
