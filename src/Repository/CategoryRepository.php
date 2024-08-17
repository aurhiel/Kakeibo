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

    public function findAllIndexedBy(string $column = 'id'): array
    {
        return $this->createQueryBuilder('c', 'c.' . $column)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAllByUserId(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.is_default = true')
            ->orWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDefault(): ?Category
    {
        return $this->createQueryBuilder('c')
            ->where('c.slug = :misc_slug')
            ->setParameter('misc_slug', self::SLUG_MISC)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS amount')
            ->getQuery()->getSingleScalarResult()
        ;
    }
}
