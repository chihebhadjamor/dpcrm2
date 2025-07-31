<?php

namespace App\Repository;

use App\Entity\CronLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CronLog>
 *
 * @method CronLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method CronLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method CronLog[]    findAll()
 * @method CronLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CronLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronLog::class);
    }

    /**
     * Find all logs ordered by execution date (newest first)
     *
     * @return CronLog[]
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.executedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find logs for a specific command ordered by execution date (newest first)
     *
     * @param string $command Command name
     * @return CronLog[]
     */
    public function findByCommandOrderedByDate(string $command): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.command = :command')
            ->setParameter('command', $command)
            ->orderBy('c.executedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
