<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RegistrationBoard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RegistrationBoard|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegistrationBoard|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegistrationBoard[]    findAll()
 * @method RegistrationBoard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationBoardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegistrationBoard::class);
    }

    /**
     * @return RegistrationBoard[] Returns an array of RegistrationBoard objects
     */
    public function findByMonth($year, $month)
    {
        $from = new \DateTime("$year-$month");
        $to = clone $from;
        $to->modify('first day of next month');

        $today = new \DateTime("00:00:00");
        if ($today > $from)
            $from = $today;

        return $this->createQueryBuilder('rb')
            ->andWhere('rb.date >= :from')
            ->andWhere('rb.date < :to')
            ->orderBy('rb.date')
            ->setParameter('to', $to)
            ->setParameter('from', $from)
            ->getQuery()
            ->getResult();
    }

    public function findOneByDate($year, $month, $day)
    {
        $date = new \DateTime("$year-$month-$day");
        return $this->createQueryBuilder('rb')
            ->andWhere('rb.date = :date')
            ->orderBy('rb.date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDateOrdered()
    {
        $from = new \DateTime("00:00:00");
        return $this->createQueryBuilder('rb')
            ->andWhere('rb.date >= :from')
            ->orderBy('rb.date')
            ->setParameter('from', $from)
            ->getQuery()
            ->getResult();
    }
}
