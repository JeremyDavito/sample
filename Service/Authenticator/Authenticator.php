<?php

namespace App\Service\Authenticator;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;

use App\Inversify\InversifyUsecase;
use App\Usecase\Dto\AuthGetUserUsecaseDto;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;
use App\Usecase\AuthenticatorUsecase\AuthenticatorUsecase;

class Authenticator extends AbstractFormLoginAuthenticator {
  use TargetPathTrait;

  /**
   * @var RouterInterface
   */
  private RouterInterface $router;

  /**
   * @var UserInterface|null
   */
  private ?UserInterface $user;

  /**
   * @var AuthenticatorUsecase $authenticatorUsecase
   */
  private AuthenticatorUsecase $authenticatorUsecase;

  /**
   * @var InversifyUsecase
   */
  private InversifyUsecase $inversifyUsecase;

  public function __construct(
    RouterInterface               $router,
    InversifyUsecase $inversifyUsecase
  ) {
    $this->router = $router;
    $this->authenticatorUsecase = $inversifyUsecase->authenticatorUsecase;
  }

  /**
   * @param Request $request
   * @return bool
   */
  public function supports(Request $request): bool {
    return $request->attributes->get("_route") === "login" && $request->isMethod('POST');
  }

  /**
   * @param Request $request
   * @return array
   */
  public function getCredentials(Request $request): array {
    // Stockage du username en session afin de l'afficher sur le form en cas d'erreur
    $request->getSession()->set(
      Security::LAST_USERNAME,
      $request->request->get("_username")
    );

    return [
      'username' => $request->request->get("_username"),
      'password' => $request->request->get("_password")
    ];
  }

  /**
   * Fonction permettant de récupérer le user correspondant au login envoyé
   * @param array $credentials Tableau des données du formulaire
   * @param UserProviderInterface $userProvider Manager
   * @return UserInterface
   * @throws Exception
   */
  public function getUser($credentials, UserProviderInterface $userProvider): ?UserInterface {
    $dto = new AuthGetUserUsecaseDto();
    $dto->username = $credentials['username'];
    $dto->password = $credentials['password'];
    return $this->authenticatorUsecase->authGetUserUsecase($dto);
  }

  /**
   * Fonction permettant de vérifier que les informations d'authenification envoyées sont correctes
   * @param array $credentials
   * @param UserInterface|null $user
   * @return bool
   * @throws Exception
   */
  public function checkCredentials($credentials, UserInterface $user = null): bool {
    $dto = new AuthCheckCredentialsUsecaseDto();
    $dto->username = $credentials['username'];
    $dto->password = $credentials['password'];
    $dto->user = $user;
    return $this->authenticatorUsecase->authCheckCredentialsUsecase($dto);
  }

  /**
   * Function redirect if success
   * @param  Request $request
   * @param  TokenInterface $token
   * @param  string $providerKey
   * @return RedirectResponse
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): RedirectResponse {
    return $this->authenticatorUsecase->authSuccessUsecase($request, $token);
  }

  /**
   * Function redirect if fail
   * @param  Request $request
   * @param  AuthenticationException $exception
   * @return RedirectResponse
   */
  public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse {
    return $this->authenticatorUsecase->AuthFailUsecase($request, $exception);
  }

  /**
   * Mandatory for abstract class
   * @return string
   */
  public function getLoginUrl(): string {
    return $this->router->generate("login");
  }
}