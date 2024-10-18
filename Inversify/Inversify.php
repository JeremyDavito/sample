<?php

namespace App\Inversify;

use App\Encoder\PasswordEncoder;
use App\Repository\ADRepository;
use App\Service\OTP\TotpService;
use Symfony\Component\Ldap\Ldap;
use App\Service\Ldap\LdapService;
use App\Service\Nota\NotaService;
use App\Repository\UserRepository;
use App\Repository\ChestRepository;
use App\Repository\StateRepository;
use App\Usecase\CreateChestUsecase;
use App\Service\OTP\TotpServiceReal;
use App\Encoder\PasswordEncoderFake;
use App\Encoder\PasswordEncoderReal;
use App\Service\Ldap\LdapServiceFake;
use App\Service\Ldap\LdapServiceReal;
use App\Service\Nota\NotaServiceFake;
use App\Service\Nota\NotaServiceReal;
use App\Service\Mailer\MailerService;
use App\Repository\LogUsageRepository;
use App\Usecase\GeneratePasswordUsecase;
use App\Service\Mailer\MailerServiceReal;
use App\Service\Mailer\MailerServiceFake;
use App\Usecase\CreateUserUsecase\CreateUserUsecase;
use App\Usecase\CreateUserUsecase\CreateUserDbUsecase;
use App\Usecase\CreateUserUsecase\CreateUserLdapUsecase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class Inversify {
  public TotpService $totpService;
  public LdapService $ldapService;
  public NotaService $notaService;
  public ADRepository $adRepository;
  public MailerService $mailerService;
  public UserRepository $userRepository;
  public PasswordEncoder $passwordEncoder;
  public StateRepository $stateRepository;
  public ChestRepository $chestRepository;
  public CreateUserUsecase $createUserUsecase;
  public CreateChestUsecase $createChestUsecase;
  public LogUsageRepository $logUsageRepository;
  public GeneratePasswordUsecase $generatePasswordUsecase;
  public UserPasswordHasherInterface $userPasswordHasherInterface;

  public function __construct(
    ADRepository $adRepository,
    NotaServiceReal $notaServiceReal,
    NotaServiceFake $notaServiceFake,
    UserRepository $userRepository,
    ChestRepository $chestRepository,
    StateRepository $stateRepository,
    MailerServiceReal $mailerServiceReal,
    MailerServiceFake $mailerServiceFake,
    LogUsageRepository $logUsageRepository,
    CreateChestUsecase $createChestUsecase,
    PasswordEncoderReal $passwordEncoderReal,
    PasswordEncoderFake $passwordEncoderFake,
    GeneratePasswordUsecase $generatePasswordUsecase,
    UserPasswordHasherInterface $userPasswordHasherInterface
  )
  {
    $this->adRepository = $adRepository;
    $this->userRepository = $userRepository;
    $this->chestRepository = $chestRepository;
    $this->stateRepository = $stateRepository;
    $this->logUsageRepository = $logUsageRepository;
    $this->createChestUsecase = $createChestUsecase;
    $this->generatePasswordUsecase = $generatePasswordUsecase;
    $this->userPasswordHasherInterface = $userPasswordHasherInterface;
    $this->totpService = new TotpServiceReal();

    /* Environment mode */
    if ($_ENV["APP_ENV"] == "prod") {
      $ldap = Ldap::create(
        'ext_ldap',
        array(
          'connection_string' => $this->adRepository->find(1)->getAd(),
        ));
      $this->ldapService = new LdapServiceReal($ldap, $_ENV['AD_DEFAULT_DN'] ?? null);
      $this->passwordEncoder = $passwordEncoderReal;
      $this->notaService = $notaServiceReal;
      $this->mailerService = $mailerServiceReal;
    } else if ($_ENV["APP_ENV"] == "release") {
      $ldap = Ldap::create(
        'ext_ldap',
        array(
          'connection_string' => $this->adRepository->find(1)->getAd(),
        ));
      $this->ldapService = new LdapServiceReal($ldap, $_ENV['AD_DEFAULT_DN'] ?? null);
      $this->passwordEncoder = $passwordEncoderReal;
      $this->notaService = $notaServiceReal;
      $this->mailerService = $mailerServiceReal;
    } else if ($_ENV["APP_ENV"] == "release_local") {
      $this->ldapService = new LdapServiceFake();
      $this->passwordEncoder = $passwordEncoderReal;
      $this->notaService = $notaServiceFake;
      $this->mailerService = $mailerServiceReal;
    } else if ($_ENV["APP_ENV"] == "dev") {
      $this->ldapService = new LdapServiceFake();
      $this->passwordEncoder = $passwordEncoderReal;
      $this->notaService = $notaServiceFake;
      $this->mailerService = $mailerServiceReal;
    } else if ($_ENV["APP_ENV"] == "test") {
      $this->ldapService = new LdapServiceFake();
      $this->passwordEncoder = $passwordEncoderFake;
      $this->notaService = $notaServiceFake;
      $this->mailerService = $mailerServiceFake;
    } else {
      $this->ldapService = new LdapServiceFake();
      $this->passwordEncoder = $passwordEncoderFake;
      $this->notaService = $notaServiceFake;
      $this->mailerService = $mailerServiceFake;
    }
    /* feature mode */
    if (isset($_ENV["AUTH_METHOD"])){
      if ($_ENV["AUTH_METHOD"] == "BDD") {
        $this->createUserUsecase = new CreateUserDbUsecase($this);
      } else if ($_ENV["AUTH_METHOD"] == "AD") {
        $this->createUserUsecase = new CreateUserLdapUsecase($this);
      }
    } else {
      $this->createUserUsecase = new CreateUserLdapUsecase($this);
    }
  }
}