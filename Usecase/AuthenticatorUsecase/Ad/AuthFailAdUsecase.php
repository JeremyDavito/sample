<?php

namespace App\Usecase\AuthenticatorUsecase\Ad;

use DateTime;
use Exception;
use DateTimeZone;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

use App\Service\LogService;
use App\Repository\UserRepository;
use App\Repository\StateRepository;
use App\Repository\LogConnectionRepository;

class AuthFailAdUsecase {

  /**
   * @var UserRepository $userRepository
   */
  private UserRepository $userRepository;

  /**
   * @var LogService $logService
   */
  private LogService $logService;

  /**
   * @var LogConnectionRepository
   */
  private LogConnectionRepository $logConnectionRepository;

  /**
   * @var StateRepository
   */
  private StateRepository $stateRepository;

  public function __construct(
    LogService $logService,
    UserRepository $userRepository,
    StateRepository $stateRepository,
    LogConnectionRepository $logConnectionRepository
  ){
    $this->logService = $logService;
    $this->userRepository = $userRepository;
    $this->stateRepository = $stateRepository;
    $this->logConnectionRepository = $logConnectionRepository;
  }

  public function execute(Request $request, AuthenticationException $exception): RedirectResponse {
    try {
      $user = null;
      $username = '';
      try {
        $usernameForm = $request->request->get('_username');
        $usernameSession = null;
        if ($request->getSession()->get('security.token_storage')) {
          $usernameSession = $request->getSession()->get('security.token_storage')->getToken()->getUser();
        }
        if ($usernameForm) {
          $username = $usernameForm;
        } elseif ($usernameSession) {
          $username = $usernameSession;
        }
      } catch (Exception $e) {}

      if ($username !== '') {
        $user = $this->userRepository->findOneBy(['login' => $username]);
      }

      if ($user) {
        // Si le compte existe et n'est pas déjà bloqué
        if (!$user->getBlocked()) {
          $this->logService->logConnection($username, 'fail_password');

          // Vérifie si l'utilisateur a été débloquer par un admin.
          $getLogsTwoHours = $this->logConnectionRepository->findAttemptsOfUser($username, 'unblocked', '-2 hour');
          $getLogsFifteenMins = $this->logConnectionRepository->findAttemptsOfUser($username, 'unblocked', '-15 min');

          // Si on trouve plus de 9 entrées loupées sur le mot de passe dans les 2 dernières heures, on désactive le compte
          if (count($this->logConnectionRepository->findAttemptsOfUser($username, 'fail_password', '-2 hour')) >= 9 && count($getLogsTwoHours) < 1) {
            $state = $this->stateRepository->find(2); // Inactive
            $user->setState($state);

            $this->userRepository->push($user);
          }
          // Si on trouve plus de 5 entrées loupées sur le mot de passe dans les 15 dernières minutes, on ban temporairement
          else if (count($this->logConnectionRepository->findAttemptsOfUser($username, 'fail_password', '-15 min')) >= 5 && count($getLogsFifteenMins) < 1 ) {
            $user->setBlocked(true);
            $datetime = new DateTime();
            $timezone = new DateTimeZone('UTC');
            $datetime->setTimezone($timezone);
            $user->setBlockedUntil($datetime->modify('+15 minutes'));

            $this->userRepository->push($user);
          }
        } else { // Si le compte existe et qu'il est déjà bloqué
          $this->logService->logConnection($username, 'blocked');
        }
      } else { // Si le compte n'existe pas
        $this->logService->logConnection($username, 'fail_login');
      }

      if ($request->hasSession()) {
        $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
      }

      return new RedirectResponse('/login');
    } catch (Exception $e) {
      return new RedirectResponse("/login");
    }
  }
}