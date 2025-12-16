<?php

namespace App\Repository;

use App\Entity\Cart;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    //    /**
    //     * @return Cart[] Returns an array of Cart objects
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

    public function findCartWithItems($user): ?Cart
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.cartItems', 'ci')
            ->leftJoin('ci.product', 'p')
            ->leftJoin('p.productImages', 'pi')
            ->leftJoin('p.category', 'cat')
            ->where('c.full_name = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCartCountByUser($user): int
    {
        $result = $this->createQueryBuilder('c')
            ->select('SUM(ci.quantity) as total')
            ->leftJoin('c.cartItems', 'ci')
            ->where('c.full_name = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
