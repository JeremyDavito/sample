<?php

namespace App\Usecase\AuthenticatorUsecase\Ad;

use Exception;
use DateTimeImmutable;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

use App\Entity\Chest;
use App\Usecase\Usecase;
use App\Entity\LogUsage;
use App\Inversify\Inversify;
use App\Usecase\GetAdsUsecase;
use App\Repository\UserRepository;
use App\Service\Ldap\LdapService;
use App\Repository\ChestRepository;
use App\Usecase\CreateChestUsecase;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\OptionsUsecaseDto;
use App\Usecase\Dto\CreateUserUsecaseDto;
use App\Usecase\Dto\CreateChestUsecaseDto;
use App\Usecase\Dto\AuthGetUserUsecaseDto;
use App\Usecase\CreateUserUsecase\CreateUserUsecase;

class AuthGetUserAdUsecase extends Usecase {
  /**
   * @var LdapService
   */
  private LdapService $ldapService;

  /**
   * @var UserRepository
   */
  private UserRepository $userRepository;

  /**
   * @var GetAdsUsecase
   */
  private GetAdsUsecase $getAdsUsecase;

  /**
   * @var ChestRepository
   */
  private ChestRepository $chestRepository;

  /**
   * @var CreateUserUsecase
   */
  private CreateUserUsecase $createUserUsecase;

  /**
   * @var CreateChestUsecase
   */
  private CreateChestUsecase $createChestUsecase;

  public function __construct(
    Security $security,
    Inversify $inversify,
    GetAdsUsecase $getAdsUsecase,
    UserRepository $userRepository,
    ChestRepository $chestRepository,
    CreateChestUsecase $createChestUsecase,
    LogUsageRepository $logUsageRepository
  ) {
    parent::__construct(
      $security,
      $logUsageRepository
    );
    $this->getAdsUsecase = $getAdsUsecase;
    $this->userRepository = $userRepository;
    $this->chestRepository = $chestRepository;
    $this->ldapService = $inversify->ldapService;
    $this->createChestUsecase = $createChestUsecase;
    $this->createUserUsecase = $inversify->createUserUsecase;
  }

  /**
   * @param AuthGetUserUsecaseDto $dto
   * @return UserInterface|null
   * @throws Exception
   */
  public function _execute(AuthGetUserUsecaseDto $dto): ?UserInterface {
    if($dto->username === null || $dto->password === "") {
      return null;
    }

    $login = strtolower(trim($dto->username));
    $user = $this->userRepository->findOneBy(['login' => $login]);

    if (isset($_ENV['THOMYRIS_USER']) && $_ENV['THOMYRIS_USER'] === $login) {
      return $user;
    }

    if ($user) {
      try {
        $this->ldapService->connectionAd($user->getAd());
        $entry = $this->ldapService->getEntryBySamAccount($user->getSamAccount(), $user->getAdDn());
        if($entry === null) {
          return null;
        }
        try{
          // On se connecte à l'AD avec le SamAccount et le mot de passe
          $this->ldapService->connectionUser($entry->getDN(), $dto->password);
          return $user;
        } catch (Exception $ex) {
          return null;
        }
      } catch(ConnectionException $ex) {
        // L'administrateur de l'AD ne se connecte pas, on n'authentifie pas
        throw new CustomUserMessageAuthenticationException(
          "AD not available", ["error_code" => "Blocked"]
        );
      }
    } else {
      $ads = $this->getAdsUsecase->execute(new OptionsUsecaseDto())->data;

      if (count($ads) === 0) {
        throw new CustomUserMessageAuthenticationException(
          "Default AD not available", ["error_code" => "Blocked"]
        );
      }

      try {
        $this->ldapService->connectionAd($ads[0]);
        $entry = $this->ldapService->getEntryBySamAccount($login);
        if($entry === null) {
          return null;
        }
        try{
          // On se connecte à l'AD avec le SamAccount et le mot de passe
          $this->ldapService->connectionUser($entry->getDN(), $dto->password);

          $dto = new CreateUserUsecaseDto();
          $dto->login = $login;
          $dto->role = 'ROLE_USER';
          $dto->adId = $ads[0]->getId();
          $dto->adDn = $_ENV['AD_DEFAULT_DN']??null;
          $dto->adSamAccount = $login;

          $user = $this->createUserUsecase->execute($dto);

          if (isset($entry->getAttribute('company')[0])) {
            $chestName = $entry->getAttribute('company')[0];

            /* @var Chest $chest */
            $chest = $this->chestRepository->findOneByLowerName($chestName);

            if (!isset($chest)) {
              $dtoChest = new CreateChestUsecaseDto();
              $dtoChest->user = $user;
              $dtoChest->name = $chestName;
              $dtoChest->type = 'company';
              $chest = $this->createChestUsecase->execute($dtoChest);
            }

            $chest->addUser($user);
            $this->chestRepository->push($chest);
          }

          return $user;
        } catch (Exception $ex) {
          $this->logUsage = new LogUsage();
          $this->logUsage->setName(get_class($this));
          $this->logUsage->setOut(new DateTimeImmutable('now'));
          $this->logUsage->setState(LogUsage::$STATE_ERROR . ':' . $ex->getMessage());
          $this->logUsageRepository->push($this->logUsage);
          return null;
        }
      } catch(ConnectionException | Exception | BadCredentialsException $ex) {
        $this->logUsage = new LogUsage();
        $this->logUsage->setName(get_class($this));
        $this->logUsage->setOut(new DateTimeImmutable('now'));
        $this->logUsage->setState(LogUsage::$STATE_ERROR . ':' . $ex->getMessage());
        $this->logUsageRepository->push($this->logUsage);
        // L'administrateur de l'AD ne se connecte pas, on n'authentifie pas
        throw new CustomUserMessageAuthenticationException(
          "AD not available", ["error_code" => "Blocked"]
        );
      }
    }
  }
}