<?php

namespace App\Usecase\CreateUserUsecase;

use Error;
use Exception;
use App\Entity\User;
use App\Common\ERRORS;
use DateTimeImmutable;
use App\Entity\LogUsage;
use App\Inversify\Inversify;
use App\Repository\UserRepository;
use App\Usecase\CreateChestUsecase;
use App\Repository\StateRepository;
use App\Service\Mailer\MailerService;
use App\Repository\LogUsageRepository;
use App\Usecase\GeneratePasswordUsecase;
use App\Usecase\Dto\CreateUserUsecaseDto;
use App\Usecase\Dto\CreateChestUsecaseDto;
use App\Usecase\Dto\GeneratePasswordUsecaseDto;
use App\Service\Mailer\Model\MailerServiceModel;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateUserDbUsecase extends CreateUserUsecase
{
  public MailerService $mailerService;
  public UserRepository $userRepository;
  public StateRepository $stateRepository;
  public CreateChestUsecase $createChestUsecase;
  public LogUsageRepository $logUsageRepository;
  public GeneratePasswordUsecase $generatePasswordUsecase;
  public UserPasswordHasherInterface $userPasswordHasherInterface;

  public function __construct(
    Inversify $inversify
  )
  {
    $this->mailerService = $inversify->mailerService;
    $this->userRepository = $inversify->userRepository;
    $this->stateRepository = $inversify->stateRepository;
    $this->logUsageRepository = $inversify->logUsageRepository;
    $this->createChestUsecase = $inversify->createChestUsecase;
    $this->generatePasswordUsecase = $inversify->generatePasswordUsecase;
    $this->userPasswordHasherInterface = $inversify->userPasswordHasherInterface;
  }

/**
 * create a user without AD
 * @param $dto CreateUserUsecaseDto
 * @throws Exception
 * @return User
 */
public function execute(CreateUserUsecaseDto $dto): User {
    $logUsage = new LogUsage();
    $logUsage->setName('CreateUserDbUsecase');
    $logUsage->setState(LogUsage::$STATE_START);
    $logUsage->setOut(new DateTimeImmutable('now'));
    try {
      $this->logUsageRepository->push($logUsage);
      $state = $this->stateRepository->findOneBy(["state" => "Active"]);
      $user = new User();

      $temporaryPassword = new GeneratePasswordUsecaseDto();
      $temporaryPassword->length = 24;
      $temporaryPassword->specials = 1;
      $generatePassword = $this->generatePasswordUsecase->execute($temporaryPassword);

      $hashedPassword = $this->userPasswordHasherInterface->hashPassword(
        $user,
        $generatePassword
      );
      if($this->userRepository->findOneBy(['email' => $dto->email])){
        throw new Exception( ERRORS::$USER_MAIL_IS_ALREADY_USED);
      }
      if($this->userRepository->findOneBy(['login' => $dto->login])){
        throw new Exception( ERRORS::$USER_LOGIN_IS_ALREADY_USED);
      }

      $user
        ->setAd(null)
        ->setAdDn(null)
        ->setSamAccount(null)
        ->setRoles([$dto->role])
        ->setLogin(strtolower(trim($dto->login)))
        ->setCreator($dto->user)
        ->setState($state)
        ->setBlocked(false)
        ->setQrScanned(false)
        ->setLastName($dto->lastName)
        ->setFirstName($dto->firstName)
        ->setEmail(strtolower(trim($dto->email)))
        ->setPasswordExpiration(true)
        ->setPassword($hashedPassword);

      $user = $this->userRepository->push($user);

      $dtoChest = new CreateChestUsecaseDto();
      $dtoChest->name = 'Mon coffre';
      $dtoChest->description = 'Coffre de ' . $dto->login;
      $dtoChest->type = 'personal';
      $dtoChest->user = $dto->user ?? $user;
      $chest = $this->createChestUsecase->execute($dtoChest);
      $chest->addUser($user);

      try{
        $email = new MailerServiceModel();
        $email->receiver = $dto->email;
        $email->password = $generatePassword;
        $email->lastName = $dto->lastName;
        $email->firstName = $dto->firstName;
        $email->login = $dto->login;
        $this->mailerService->createMailNewUser($email);
      }catch (Exception | Error $e) {
        throw new Exception(ERRORS::$MAIL_WAS_NOT_SEND);
      }

      $logUsage->setState(LogUsage::$STATE_OK);
      $logUsage->setOut(new DateTimeImmutable('now'));
      $this->logUsageRepository->push($logUsage);
      return $user;
    } catch (Exception $e) {
      $logUsage->setOut(new DateTimeImmutable('now'));
      $logUsage->setState(LogUsage::$STATE_ERROR . ':' . $e->getMessage());
      $this->logUsageRepository->push($logUsage);
      throw new Exception(ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$CREATE_USER_USECASE_FAIL);
    }
  }
}