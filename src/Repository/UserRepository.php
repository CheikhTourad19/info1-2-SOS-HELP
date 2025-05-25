<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }
    
    /**
     * Récupère tous les utilisateurs disponibles pour une conversation
     * Exclut l'utilisateur courant
     */
    public function findAvailableContacts(User $currentUser, string $role = null)
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->orderBy('u.lastName', 'ASC');
            
        // Filtrer par rôle si spécifié
        if ($role) {
            $qb->andWhere('u.role = :role')
               ->setParameter('role', $role);
        }
        
        return $qb->getQuery()->getResult();
    }
}