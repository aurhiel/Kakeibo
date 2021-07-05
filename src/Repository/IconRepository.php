<?php

namespace App\Repository;

use App\Entity\Icon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Icon|null find($id, $lockMode = null, $lockVersion = null)
 * @method Icon|null findOneBy(array $criteria, array $orderBy = null)
 * @method Icon[]    findAll()
 * @method Icon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IconRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Icon::class);
    }

//    /**
//     * @return Icon[] Returns an array of Icon objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Icon
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
