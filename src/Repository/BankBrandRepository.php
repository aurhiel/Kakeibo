<?php

namespace App\Repository;

use App\Entity\BankBrand;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BankBrand|null find($id, $lockMode = null, $lockVersion = null)
 * @method BankBrand|null findOneBy(array $criteria, array $orderBy = null)
 * @method BankBrand[]    findAll()
 * @method BankBrand[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BankBrandRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BankBrand::class);
    }

//    /**
//     * @return BankBrand[] Returns an array of BankBrand objects
//     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BankBrand
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
