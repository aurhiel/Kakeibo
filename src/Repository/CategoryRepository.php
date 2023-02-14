<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public const SLUG_MISC = 'misc';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllIndexedBy($column = 'id')
    {
        return $this->createQueryBuilder('c', 'c.' . $column)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllIndexedByAndForUser(string $column = 'id', int $userId)
    {
        return $this->createQueryBuilder('c', 'c.' . $column)
            ->where('c.is_default = true')
            ->orWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDefault()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.slug = :misc_slug')
            ->setParameter('misc_slug', self::SLUG_MISC)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countAll()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS amount')
            ->getQuery()->getSingleScalarResult()
        ;
    }

//    /**
//     * @return Category[] Returns an array of Category objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
