<?php

namespace App\Inversify;

use App\Repository\ChestRepository;
use App\Repository\LogUsageRepository;
use App\Usecase\AuthenticatorUsecase\Ad\AuthFailAdUsecase;
use App\Usecase\AuthenticatorUsecase\AuthenticatorUsecase;
use App\Usecase\AuthenticatorUsecase\Db\AuthFailDbUsecase;
use App\Usecase\AuthenticatorUsecase\Ad\AuthGetUserAdUsecase;
use App\Usecase\AuthenticatorUsecase\Ad\AuthSuccessAdUsecase;
use App\Usecase\AuthenticatorUsecase\Db\AuthGetUserDbUsecase;
use App\Usecase\AuthenticatorUsecase\Db\AuthSuccessDbUsecase;
use App\Usecase\AuthenticatorUsecase\Ad\AuthenticatorAdUsecase;
use App\Usecase\AuthenticatorUsecase\Db\AuthenticatorDbUsecase;
use App\Usecase\AuthenticatorUsecase\Db\AuthCheckCredentialsDbUsecase;
use App\Usecase\AuthenticatorUsecase\Ad\AuthCheckCredentialsAdUsecase;

class InversifyUsecase {

  public ChestRepository $chestRepository;
  public LogUsageRepository $logUsageRepository;
  public AuthenticatorUsecase $authenticatorUsecase;

  public function __construct(
    AuthFailAdUsecase $authFailAdUsecase,
    AuthFailDbUsecase $authFailDbUsecase,
    AuthSuccessAdUsecase $authSuccessAdUsecase,
    AuthGetUserAdUsecase $authGetUserAdUsecase,
    AuthSuccessDbUsecase $authSuccessDbUsecase,
    AuthGetUserDbUsecase $authGetUserDbUsecase,
    AuthCheckCredentialsAdUsecase $authCheckCredentialsAdUsecase,
    AuthCheckCredentialsDbUsecase $authCheckCredentialsDbUsecase
  )
  {
    /* feature mode */
    if (isset($_ENV["AUTH_METHOD"])){
      if ($_ENV["AUTH_METHOD"] == "BDD") {
        $this->authenticatorUsecase = new authenticatorDbUsecase(
          $authFailDbUsecase,
          $authSuccessDbUsecase,
          $authGetUserDbUsecase,
          $authCheckCredentialsDbUsecase
        );

      } else if ($_ENV["AUTH_METHOD"] == "AD") {
        $this->authenticatorUsecase = new authenticatorAdUsecase(
          $authFailAdUsecase,
          $authGetUserAdUsecase,
          $authSuccessAdUsecase,
          $authCheckCredentialsAdUsecase
        );
      }
    } else {
      $this->authenticatorUsecase = new authenticatorAdUsecase(
        $authFailAdUsecase,
        $authGetUserAdUsecase,
        $authSuccessAdUsecase,
        $authCheckCredentialsAdUsecase
      );
    }
  }
}