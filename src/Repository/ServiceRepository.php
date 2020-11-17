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

    public function findSearch($query, $category, $isDiscovery, $allCompanies){
        $q = $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('u.company', 'c');

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

        $q->andWhere('c.id in (:companies)')
            ->andWhere('c.isValid = 1')
          ->setParameter('companies', $allCompanies);
        $q->orderBy('s.createdAt', 'DESC');

        return $q
            ->getQuery()
            ->getResult()
            ;
    }



    public function findByLocalServices($allCompanies){

        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('u.company', 'c')
            ->Where('c.id IN  (:companies)')
            ->andWhere('c.isValid = 1')
            ->orderBy('s.createdAt', 'DESC')
            ->addOrderBy('s.isDiscovery', 'DESC')
            ->setParameters(array('companies'=>$allCompanies))
            ->getQuery()
            ->getResult() ;
    }

    public function findByIsDiscovery( $allCompanies, $store){

        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('u.company', 'c')
            ->andWhere('s.isDiscovery = 1')
            ->andWhere('c.id in (:companies)')
            ->andWhere('c.isValid = 1')
            ->orWhere('u.company is NULL AND u.store = :store')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameters(array('companies'=>$allCompanies, 'store'=>$store))
            ->getQuery()
            ->getResult() ;
    }

    public function findOneByIsDiscovery( $allCompanies, $store){

        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('u.company', 'c')
            ->andWhere('s.isDiscovery = 1')
            ->andWhere('c.id in (:companies)')
            ->andWhere('c.isValid = 1')
            ->orWhere('u.company is NULL AND u.store = :store')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameters(array('companies'=>$allCompanies, 'store'=>$store))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByType($type){

        return $this->createQueryBuilder('s')
                ->leftJoin('s.user', 'u')
                ->leftJoin('u.company', 'c')
                ->andWhere('s.type = :type')
                ->andWhere('c.isValid = 1')
                ->orderBy('s.createdAt', 'DESC')
                ->setParameters(array('type'=> $type))
                ->getQuery()
                ->getResult() ;

        }

    public function findByCategory($category, $allCompanies, $serviceID){

        return $this->createQueryBuilder('s')
            ->leftJoin('s.user', 'u')
            ->leftJoin('u.company', 'c')
            ->Where('s.category = :category')
            ->andWhere('c.id in (:companies)')
            ->andWhere('c.isValid = 1')
            ->andWhere('s.id != (:serviceID)')
            ->orderBy('s.createdAt', 'DESC')
            ->setParameters(array('category'=> $category, 'companies'=>$allCompanies, 'serviceID'=>$serviceID))
            ->setMaxResults(3)
            ->getQuery()
            ->getResult() ;

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
