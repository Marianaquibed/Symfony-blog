<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    //    /**
    //     * @return Post[] Returns an array of Post objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Post
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
* Returns an array of Post objects
*/
   /**
* @return Post[] Returns an array of Post objects
*/
    public function findRecents(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.PublishedAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }
        /**
     * Devuelve un Paginator con los posts de la página indicada.
     * El Paginator es iterable (posts de la página) y count()
     * devuelve el número TOTAL de posts de la consulta.
     * $perPage Es el tamaño de la ventana y $page es la página actual, que empieza en 1
     */
    public function findPaginados(int $page, int $perPage): Paginator
    {
        $query = $this->createQueryBuilder('p')
            ->orderBy('p.PublishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        return new Paginator($query, false);
    }

    /**
     * Página $page de los posts cuyo contenido contiene $searchTerm.
     * count() sobre el resultado devuelve el total de coincidencias.
     */
    public function findBuscadosPaginados(string $searchTerm, int $page, int $perPage): Paginator
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.Content LIKE :val')
            ->setParameter('val', '%'.$searchTerm.'%')
            ->orderBy('p.PublishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        return new Paginator($query, false);
    }
}
