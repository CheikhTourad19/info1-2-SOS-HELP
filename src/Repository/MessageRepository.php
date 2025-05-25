<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupère les derniers messages pour chaque conversation d'un utilisateur
     */
    public function findLatestConversations(User $user)
    {
        $qb = $this->createQueryBuilder('m1');

        return $qb->select('m1')
            ->leftJoin(
                Message::class,
                'm2',
                Join::WITH,
                $qb->expr()->andX(
                    $qb->expr()->eq('IDENTITY(m1.sender)', 'IDENTITY(m2.sender)'),
                    $qb->expr()->eq('IDENTITY(m1.receiver)', 'IDENTITY(m2.receiver)'),
                    $qb->expr()->gt('m2.sentAt', 'm1.sentAt')
                )
            )
            ->where($qb->expr()->isNull('m2.id'))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('m1.sender', ':user'),
                $qb->expr()->eq('m1.receiver', ':user')
            ))
            ->setParameter('user', $user)
            ->orderBy('m1.sentAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}