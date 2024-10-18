<?php

namespace App\Controller\API;

use Error;
use Exception;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Common\ERRORS;
use App\Validator\REGEXS;
use App\Inversify\InversifyUsecase;
use App\Validator\GenericValidator;
use App\Validator\PasswordValidator;
use App\Usecase\Dto\AuthGetUserUsecaseDto;
use App\Controller\Helper\ControllerHelper;
use App\Usecase\Dto\AuthCheckCredentialsUsecaseDto;
use App\Usecase\AuthenticatorUsecase\AuthenticatorUsecase;

class ApiAuthController extends ControllerHelper {

  /**
   * @var Security
   */
  private Security $security;

  /**
   * @var TokenStorageInterface
   */
  private TokenStorageInterface $tokenStorageInterface;

  /**
   * @var SessionInterface
   */
  private SessionInterface $session;

  /**
   * @var AuthenticatorUsecase
   */
  private AuthenticatorUsecase  $authenticatorUsecase;

  public function __construct(
    Security                      $security,
    SessionInterface              $session,
    InversifyUsecase              $inversifyUsecase,
    TokenStorageInterface         $tokenStorageInterface
  ) {
    $this->session = $session;
    $this->security = $security;
    $this->tokenStorageInterface = $tokenStorageInterface;
    $this->authenticatorUsecase = $inversifyUsecase->authenticatorUsecase;
  }

  /**
   * @Route("/api/auth/login", methods={"POST"}, name="api_auth_login")
   * @param Request $request
   * @return Response
   */
  public function login(Request $request): Response {
    try {
      $login = (new GenericValidator($this->getParam($request,'login'), REGEXS::$REGEX_LOGIN))->get();
      $password = (new PasswordValidator($this->getParam($request,'password')))->get();

      $dto = new AuthGetUserUsecaseDto();
      $dto->username = $login;
      $dto->password = $password;

      $user = $this->authenticatorUsecase->authGetUserUsecase($dto);

      $dto = new AuthCheckCredentialsUsecaseDto();
      $dto->username = $login;
      $dto->password = $password;
      $dto->user = $user;

      $checkCredentials = $this->authenticatorUsecase->authCheckCredentialsUsecase($dto);

      if($checkCredentials) {
        // generate token
        $token = new UsernamePasswordToken($user, 'api', 'main', $user->getRoles());

        // set token
        $this->tokenStorageInterface->setToken($token);

        // Set session security
        $this->session->set('security.token_storage', serialize($token));

        // Save session
        $this->session->save();

        return new JsonResponse([
          'session_id' => $this->session->getId()
        ], 200);
      } else {
        return new JsonResponse([
          'message' => ERRORS::$API_AUTH_LOGIN_FAIL,
          'error' => 'UNAUTHORIZED'
        ], 406);
      }
    } catch (Exception|Error $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_AUTH_LOGIN_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_AUTH_LOGIN_FAIL
      ], 406);
    }
  }

  /**
   * @Route("/api/auth/logout", methods={"POST"}, name="api_auth_logout")
   * @return Response
   */
  public function logout(): Response {
    try {
      $user = $this->security->getUser();
      $response = false;

      if($user) {
        $this->session->invalidate();
        $this->session->remove('security.token_storage');
        $this->session->save();
        $response = true;
      }

      return new JsonResponse([
        'response' => $response
      ], 200);
    } catch (Exception $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_AUTH_LOGOUT_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_AUTH_LOGOUT_FAIL
      ], 406);
    }
  }
}