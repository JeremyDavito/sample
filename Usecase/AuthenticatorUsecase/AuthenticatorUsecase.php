<?php

namespace App\Usecase\AuthenticatorUsecase;

use App\Usecase\Usecase;
use App\Usecase\Dto\AuthGetUserUsecaseDto;
use Symfony\Component\HttpFoundation\Request;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

abstract class AuthenticatorUsecase extends Usecase {

  /**
   * @param AuthGetUserUsecaseDto $dto
   * @return mixed
   */
  abstract public function AuthGetUserUsecase(AuthGetUserUsecaseDto $dto): ?UserInterface;

  /**
   * @param AuthCheckCredentialsUsecaseDto $dto
   * @return mixed
   */
  abstract public function AuthCheckCredentialsUsecase(AuthCheckCredentialsUsecaseDto $dto): bool;

  /**
   * @param  Request $request
   * @param  AuthenticationException $exception
   * @return RedirectResponse
   */
  abstract public function AuthFailUsecase(Request $request, AuthenticationException $exception): RedirectResponse;

  /**
   * @param Request $request
   * @param TokenInterface $token
   * @return RedirectResponse
   */
  abstract public function AuthSuccessUsecase(Request $request, TokenInterface $token): RedirectResponse;

}