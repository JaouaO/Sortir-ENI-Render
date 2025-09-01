<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<City>
 */
class CityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, City::class);
    }

    public function findCities(?string $searchTerm): array
    {
        $qb = $this->createQueryBuilder('c');

        // expr() permet de générer des requètes tel que LIKE, AND, OR, BETWEEN

        if ($searchTerm) {
            $qb->where($qb->expr()->like('c.name', ':term'))
                ->orWhere($qb->expr()->like('c.postCode', ':term'))
                ->setParameter('term', '%' . $searchTerm . '%');
        }

        return $qb->getQuery()->getResult();
    }





    //    /**
    //     * @return City[] Returns an array of City objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?City
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
