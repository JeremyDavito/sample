<?php

namespace App\Usecase\AuthenticatorUsecase\Ad;

use Exception;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Usecase\Dto\AuthGetUserUsecaseDto;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;
use App\Usecase\AuthenticatorUsecase\AuthenticatorUsecase;

class AuthenticatorAdUsecase extends AuthenticatorUsecase {

  public AuthFailAdUsecase $authFailAdUsecase;
  public AuthSuccessAdUsecase $authSuccessAdUsecase;
  public AuthGetUserAdUsecase $authGetUserAdUsecase;
  public AuthCheckCredentialsAdUsecase $authCheckCredentialsAdUsecase;

  public function __construct(
    AuthFailAdUsecase $authFailAdUsecase,
    AuthGetUserAdUsecase $authGetUserAdUsecase,
    AuthSuccessAdUsecase $authSuccessAdUsecase,
    AuthCheckCredentialsAdUsecase $authCheckCredentialsAdUsecase
  ) {
    $this->authFailAdUsecase = $authFailAdUsecase;
    $this->authGetUserAdUsecase = $authGetUserAdUsecase;
    $this->authSuccessAdUsecase = $authSuccessAdUsecase;
    $this->authCheckCredentialsAdUsecase = $authCheckCredentialsAdUsecase;
  }

  /**
   * @param AuthGetUserUsecaseDto $dto
   * @return UserInterface
   * @throws Exception
   */
  public function AuthGetUserUsecase(AuthGetUserUsecaseDto $dto): ?UserInterface {
    return $this->authGetUserAdUsecase->_execute($dto);
  }

  /**
   * @param AuthCheckCredentialsUsecaseDto $dto
   * @return bool
   * @throws Exception
   */
  public function AuthCheckCredentialsUsecase(AuthCheckCredentialsUsecaseDto $dto): bool {
    return $this->authCheckCredentialsAdUsecase->_execute($dto);

  }

  /**
   * Function redirect if fail
   * @param Request $request
   * @param AuthenticationException $exception
   * @return RedirectResponse
   */
  public function AuthFailUsecase(Request $request, AuthenticationException $exception): RedirectResponse {
    return $this->authFailAdUsecase->execute($request, $exception);
  }

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @return RedirectResponse
   */
  public function AuthSuccessUsecase(Request $request, TokenInterface $token): RedirectResponse {
    return $this->authSuccessAdUsecase->execute($request, $token);
  }
}