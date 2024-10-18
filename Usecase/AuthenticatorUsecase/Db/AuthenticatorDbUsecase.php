<?php

namespace App\Usecase\AuthenticatorUsecase\Db;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Usecase\Dto\AuthGetUserUsecaseDto;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;
use App\Usecase\AuthenticatorUsecase\AuthenticatorUsecase;

class AuthenticatorDbUsecase extends AuthenticatorUsecase {

  public AuthFailDbUsecase $authFailDbUsecase;
  public AuthSuccessDbUsecase $authSuccessDbUsecase;
  public AuthGetUserDbUsecase $authGetUserDbUsecase;
  public AuthCheckCredentialsDbUsecase $authCheckCredentialsDbUsecase;

  public function __construct(
    AuthFailDbUsecase $authFailDbUsecase,
    AuthSuccessDbUsecase $authSuccessDbUsecase,
    AuthGetUserDbUsecase $authGetUserDbUsecase,
    AuthCheckCredentialsDbUsecase $authCheckCredentialsDbUsecase
  )
  {
    $this->authFailDbUsecase = $authFailDbUsecase;
    $this->authSuccessDbUsecase = $authSuccessDbUsecase;
    $this->authGetUserDbUsecase = $authGetUserDbUsecase;
    $this->authCheckCredentialsDbUsecase = $authCheckCredentialsDbUsecase;
  }

  /**
   * @param AuthGetUserUsecaseDto $dto
   * @return UserInterface
   * @throws \Exception
   */
  public function AuthGetUserUsecase(AuthGetUserUsecaseDto $dto): ?UserInterface {
   return $this->authGetUserDbUsecase->execute($dto);
  }

  /**
   * @param AuthCheckCredentialsUsecaseDto $dto
   * @return bool
   * @throws \Exception
   */
  public function AuthCheckCredentialsUsecase(AuthCheckCredentialsUsecaseDto $dto): bool {
    return $this->authCheckCredentialsDbUsecase->execute($dto);

  }

  /**
   * Function redirect if fail
   * @param Request $request
   * @param AuthenticationException $exception
   * @return RedirectResponse
   */
  public function AuthFailUsecase(Request $request, AuthenticationException $exception): RedirectResponse {
    return $this->authFailDbUsecase->execute($request, $exception);
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @return RedirectResponse
   */
  public function AuthSuccessUsecase(Request $request, TokenInterface $token): RedirectResponse {
    return $this->authSuccessDbUsecase->execute($request, $token);
  }
}