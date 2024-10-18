<?php

namespace App\Tests\Usecase\CreateUserUsecaseTest;

use Exception;
use App\Entity\User;
use App\Entity\State;
use App\Inversify\Inversify;
use PHPUnit\Framework\TestCase;
use App\Repository\UserRepository;
use App\Repository\ChestRepository;
use App\Repository\StateRepository;
use App\Service\Mailer\MailerService;
use App\Usecase\FakeGeneratePassword;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\CreateUserUsecaseDto;
use PHPUnit\Framework\MockObject\MockObject;
use App\Tests\Usecase\FakeCreateChestUsecase;
use App\Usecase\CreateUserUsecase\CreateUserUsecase;
use App\Usecase\CreateUserUsecase\CreateUserDbUsecase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

/**
 * @testdox CreateUserDbUsecase: The CreateUserUsecase Service Fake test suite
 */
class CreateUserDbUsecaseTest extends TestCase {
  /**
   * @var CreateUserDbUsecase
   */
  private CreateUserDbUsecase $usecase;

  /**
   * @var MockObject&Inversify
   */
  private MockObject $inverifyMock;

  /**
   * @var User
   */
  private User $userT;

  protected function setUp(): void {
    $this->inverifyMock = $this->createMock(Inversify::class);
    $this->logUsageRepositoryMock = $this->createMock(LogUsageRepository::class);
    $this->inverifyMock->logUsageRepository=$this->logUsageRepositoryMock;
    $this->stateRepositoryMock = $this->createMock(StateRepository::class);
    $this->inverifyMock->stateRepository=$this->stateRepositoryMock;
    $this->userRepositoryMock = $this->createMock(UserRepository::class);
    $this->inverifyMock->userRepository=$this->userRepositoryMock;
    $this->createChestUsecaseMock = $this->createMock(FakeCreateChestUsecase::class);
    $this->inverifyMock->createChestUsecase=$this->createChestUsecaseMock;
    $this->mailerServiceMock = $this->createMock(MailerService::class);
    $this->inverifyMock->mailerService=$this->mailerServiceMock;
    $this->chestRepositoryMock = $this->createMock(ChestRepository::class);
    $this->inverifyMock->chestRepository=$this->chestRepositoryMock;
    $this->createUserUsecaseMock = $this->createMock(CreateUserUsecase::class);
    $this->inverifyMock->createUserUsecase=$this->createUserUsecaseMock;
    $this->generatePasswordUsecaseMock = $this->createMock(FakeGeneratePassword::class);
    $this->inverifyMock->generatePasswordUsecase = $this->generatePasswordUsecaseMock;
    $this->userPasswordHasherInterfaceMock = $this->createMock(UserPasswordHasher::class);
    $this->inverifyMock->userPasswordHasherInterface = $this->userPasswordHasherInterfaceMock;

    $this->inverifyMock->logUsageRepository->method('push');
    $this->generatePasswordUsecaseMock->method('execute')->willReturn('dhfsdugf');
    $this->userPasswordHasherInterfaceMock->method('hashPassword')->willReturn('hashed');

    $activeState = new State();
    $activeState->setState('Active');
    $this->inverifyMock->stateRepository->method('find')->willReturn($activeState);

    $this->userT = new User();
    $this->userT->setLogin('charly');
    $this->userT->setState($activeState);
    $this->userT->setAd(null);
    $this->userT->setSamAccount(null);
    $this->userT->setBlocked(false);
    $this->userT->setQrScanned(false);
    $this->userT->setReadFeatures(false);
    $this->userT->setEmail('efef@ef.fr');
    $this->userT->setLastName('rggrrggr');
    $this->userT->setFirstName('gegregeg');
    $this->userT->setPasswordExpiration(true);
    $this->userT->setPassword('grgrg');

    $this->userT2 = new User();
    $this->userT2->setLogin('loic');
    $this->userT2->setState($activeState);
    $this->userT2->setAd(null);
    $this->userT2->setBlocked(false);
    $this->userT2->setQrScanned(false);
    $this->userT2->setReadFeatures(false);

    $this->userT2->setSamAccount(null);
    $this->userT2->setBlocked(false);
    $this->userT2->setQrScanned(false);
    $this->userT2->setReadFeatures(false);
    $this->userT2->setEmail('efeeeef@ef.fr');
    $this->userT2->setLastName('rggrrggr');
    $this->userT2->setFirstName('gegregeg');
    $this->userT2->setPasswordExpiration(true);
    $this->userT2->setPassword('grgrg');

    $this->inverifyMock->userRepository->method('push')->willReturn($this->userT);

    $this->usecase = new CreateUserDbUsecase(
      $this->inverifyMock
    );
  }

  protected function tearDown(): void {}

  /**
   * @testdox CreateUserDbUsecase:constructor: should build the object
   */
  public function testBuild() {
    //Arrange
    //Act
    //Assert
    $this->assertNotNull($this->usecase);
  }

  /**
   * @testdox CreateUserDBUsecase:create: should create a user
   * @throws Exception
   */
  public function testCreate() {
    //Arrange
    $dto = new CreateUserUsecaseDto();
    $dto->adSamAccount = 'adSamAccount';
    $dto->adId = 1;
    $dto->role = 'ROLE_USER';
    $dto->adDn = 'adDn';
    $dto->firstName = 'gegregeg';
    $dto->lastName = 'rggrrggr';
    $dto->email = 'fqke@fqke-Test.fr';
    $dto->login = 'login';
    $dto->password="grgrg";
    $dto->user = $this->userT2;

    $this->userT->setAdDn(null);
    $this->userT->setLogin($dto->login);
    $this->userT->setEmail($dto->email);
    $this->userT->setLogin($dto->login);

    //Act
    $result = $this->usecase->execute($dto);

    //Assert
    $this->assertEquals($this->userT, $result);
  }

}