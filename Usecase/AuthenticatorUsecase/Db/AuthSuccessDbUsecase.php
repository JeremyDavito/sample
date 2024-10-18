<?php

namespace App\Usecase\AuthenticatorUsecase\Db;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use App\Repository\UserRepository;

class AuthSuccessDbUsecase {

  /**
   * @var UserRepository $userRepository
   */
  private UserRepository $userRepository;

  public function __construct(
    UserRepository $userRepository
  ){
    $this->userRepository = $userRepository;
  }

  public function execute(Request $request, TokenInterface $token): RedirectResponse {
    try {
      $user = $token->getUser();

      if($user->getBlocked()) {
        $user->setBlocked(false);
        $user->setBlockedUntil(null);

        $this->userRepository->push($user);
      }

      // Sinon on renvoie sur la page d'accueil
      return new RedirectResponse("/");
    } catch (Exception $e) {
      return new RedirectResponse("/");
    }
  }
}