<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Chest;
use App\Usecase\Dto\OptionsUsecaseDto;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Helper\RepositoryHelper;

/**
 * @method User[] findAll()
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @method User push(User $user)
 * @method User remove(User $user)
 * @method User[] findByOption(OptionsUsecaseDto $optionsUsecaseDto)
 */
class UserRepository extends RepositoryHelper {
  public function __construct(
    ManagerRegistry $registry
  ) {
    parent::__construct($registry, User::class);
  }

  public function findOneWithAD(string $login) {
    return $this->createQueryBuilder("u")
      ->innerJoin("u.chest", "a")
      ->addSelect("a")
      ->innerJoin("a.ad", "ad")
      ->addSelect("ad")
      ->innerJoin("u.state", "s")
      ->addSelect("s")
      ->where('u.login = :login')
      ->setParameter('login', $login)
      ->getQuery()->getOneOrNullResult();
  }

  public function isLoginUsed(string $login) {
    $qb = $this->createQueryBuilder("u");
    $qb->select('COUNT(u.id)')
      ->where("u.login = :login")
      ->setParameter("login", $login);
    return $qb->getQuery()->getSingleScalarResult();
  }

  public function findAllNotInGroup(array $userIds, Chest $chest = null) {
    if (empty($userIds))
      $userIds = 0;
    $qb = $this->createQueryBuilder('u')
      ->where('u.id NOT IN (:ids)')
      ->setParameter('ids', $userIds);
    if ($chest != null)
      $qb->andWhere('u.chest = :uChest')
        ->setParameter('uChest', $chest);
    return $qb->getQuery()->getResult();
  }

  /**
   * Retrouve tous les users qui sont dans l'entité du user envoyé
   */
  public function findAllBySameEntity(User $user, string $search = "") {
    return $this->createQueryBuilder('u')
      ->where('u.chest = :chestId')
      ->andWhere('u.login LIKE :search')
      ->setParameter('chestId', $user->getChests()[0]->getId())
      ->setParameter('search', '%' . $search . '%')
      ->getQuery()->getResult();
  }

  public function findAllBySearch(string $search) {
    return $this->createQueryBuilder('u')
      ->where('u.login LIKE :search')
      ->setParameter('search', '%' . $search . '%')
      ->getQuery()->getResult();
  }

  public function getNextUsers($lastId = 0, $chest = null, $term = null) {
    $qb = $this->createQueryBuilder('u')
      ->where('u.id > :id')
      ->setParameter('id', $lastId);
      $qb->setMaxResults(25)
      ->orderBy('u.id');
      return $qb->getQuery()->getResult();
  }
}
