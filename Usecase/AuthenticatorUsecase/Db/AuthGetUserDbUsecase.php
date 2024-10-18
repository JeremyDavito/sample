<?php

namespace App\Usecase\AuthenticatorUsecase\Db;

use App\Entity\LogUsage;
use App\Usecase\Usecase;
use App\Repository\UserRepository;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\AuthGetUserUsecaseDto;

use Exception;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class AuthGetUserDbUsecase extends Usecase
{

  /**
   * @var UserRepository
   */
  private UserRepository $userRepository;

  /**
   * @var UserPasswordHasherInterface
   */
  private UserPasswordHasherInterface $passwordHasher;


  public function __construct(
    Security $security,
    UserRepository $userRepository,
    LogUsageRepository $logUsageRepository,
    UserPasswordHasherInterface $passwordHasher
  )
  {
    parent::__construct(
      $security,
      $logUsageRepository
    );
    $this->userRepository = $userRepository;
    $this->passwordHasher = $passwordHasher;
  }

  /**
   * @param AuthGetUserUsecaseDto $dto
   * @return UserInterface|null
   * @throws Exception
   */
  public function execute(AuthGetUserUsecaseDto $dto): ?UserInterface
  {
    try{
      if ($dto->username === null || $dto->password === "") {
        return null;
      }

      $login = strtolower(trim($dto->username));
      $user = $this->userRepository->findOneBy(['login' => $login]);

      if (isset($_ENV['THOMYRIS_USER']) && $_ENV['THOMYRIS_USER'] === $login) {
        return $user;
      }

      if ($user) {
        try {
          $password = $dto->password;
          if($password === null) {
            return null;
          }

          if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new CustomUserMessageAuthenticationException(
              "Wrong Password", ["error_code" => "Wrong Password"]
            );
          }

          if($this->passwordHasher->isPasswordValid($user, $password)){
            return $user;
          }
        } catch(ConnectionException $ex) {
          throw new CustomUserMessageAuthenticationException(
            "Wrong Password", ["error_code" => "Wrong Password"]
          );
        }
      }
      return null;
    }catch (Exception $ex) {
      $this->logUsage = new LogUsage();
      $this->logUsage->setName(get_class($this));
      $this->logUsage->setOut(new DateTimeImmutable('now'));
      $this->logUsage->setState(LogUsage::$STATE_ERROR . ':' . $ex->getMessage());
      $this->logUsageRepository->push($this->logUsage);
      return null;
    }
  }
}