<?php

namespace App\Usecase\AuthenticatorUsecase\Ad;

use Error;
use DateTime;
use Exception;
use DateTimeImmutable;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Google\GoogleAuthenticatorInterface;

use App\Entity\LogUsage;
use App\Usecase\Usecase;
use App\Repository\UserRepository;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;

class AuthCheckCredentialsAdUsecase extends Usecase {

  /**
   * @var GoogleAuthenticatorInterface
   */
  private GoogleAuthenticatorInterface $googleService;

  /**
   * @var UserRepository
   */
  private UserRepository $userRepository;

  public function __construct(
    Security $security,
    UserRepository $userRepository,
    GoogleAuthenticatorInterface $googleService,
    LogUsageRepository $logUsageRepository
  ) {
    parent::__construct(
      $security,
      $logUsageRepository
    );
    $this->googleService = $googleService;
    $this->userRepository = $userRepository;
  }

  /**
   * @param AuthCheckCredentialsUsecaseDto $dto
   * @return bool
   * @throws Exception
   */
  public function _execute(AuthCheckCredentialsUsecaseDto $dto): bool {
    try {
      if($dto->user === null) {
        return false;
      }

      if (isset($_ENV['THOMYRIS_USER']) && strtolower($dto->username) === $_ENV['THOMYRIS_USER']) {
        if (isset($_ENV['THOMYRIS_PASSWORD']) && $dto->password === $_ENV['THOMYRIS_PASSWORD']) {
          return true;
        } else {
          return false;
        }
      }

      if ($dto->user->getGoogleAuthenticatorSecret() === null || $dto->user->getGoogleAuthenticatorSecret() === "") {
        $dto->user->setGoogleAuthenticatorSecret($this->googleService->generateSecret());
        $dto->user->setQrScanned(false);
        $this->userRepository->push($dto->user);
      }

      // Si l'utilisateur est inactif, on n'authentifie pas
      if($dto->user->getState()->getState() === "Inactive") {
        throw new CustomUserMessageAuthenticationException(
          "Votre compte est inactif", ["error_code" => "Inactive"]
        );
      }

      $now = new DateTime();
      $blockedUntil = $dto->user->getBlockedUntil();

      if($dto->user->getBlocked() && $now < $blockedUntil->modify('+ 1 hour')) {

        throw new CustomUserMessageAuthenticationException(
          "Mot de passe ou identifiant Incorrect"
        );
      }

      return true;
    } catch(Exception | Error $ex) {
      $this->logUsage = new LogUsage();
      $this->logUsage->setName(get_class($this));
      $this->logUsage->setOut(new DateTimeImmutable('now'));
      $this->logUsage->setState(LogUsage::$STATE_ERROR . ':' . $ex->getMessage());
      $this->logUsageRepository->push($this->logUsage);
      throw $ex;
    }
  }
}