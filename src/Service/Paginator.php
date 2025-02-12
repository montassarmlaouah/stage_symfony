<?php   
namespace App\Service;

use Doctrine\ORM\Query;

class Paginator
{
    public function paginate(Query $query, int $page = 1, int $limit = 10): array
    {
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query);
        $paginator->getQuery()
                  ->setFirstResult($limit * ($page - 1))
                  ->setMaxResults($limit);

        return [
            'data' => $paginator->getIterator(),
            'total' => count($paginator),
        ];
    }
}
