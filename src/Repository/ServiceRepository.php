<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Service|null find($id, $lockMode = null, $lockVersion = null)
 * @method Service|null findOneBy(array $criteria, array $orderBy = null)
 * @method Service[]    findAll()
 * @method Service[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }  

    public function findOneById($value): ?Service
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findSearch($query, $category, $isDiscovery){
        $q = $this->createQueryBuilder('s');

        if ($query){
            $q
                ->andWhere('s.title LIKE :query')
                ->orWhere('s.introduction LIKE :query')
                ->orWhere('s.description LIKE :query')
                ->setParameter('query', '%' .$query . '%')
            ;
        }

        if ($category)
            $q
                ->andWhere('s.category = :category')
                ->setParameter('category', $category)
        ;

        if ($isDiscovery)
            $q
                ->andWhere('s.isDiscovery = :isDiscovery')
                ->setParameter('isDiscovery', $isDiscovery)
        ;


        $q->orderBy('s.createdAt', 'DESC');

        return $q
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return Service[] Returns an array of Service objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Service
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
