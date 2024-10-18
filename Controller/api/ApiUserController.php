<?php

namespace App\Controller\API;

use Exception;
use App\Common\ERRORS;
use App\Validator\REGEXS;
use App\Inversify\Inversify;
use App\Usecase\GetUserUsecase;
use App\Usecase\GetUsersUsecase;
use App\Usecase\UpdateUserUsecase;
use App\Usecase\DeleteUserUsecase;
use App\Validator\GenericValidator;
use App\Usecase\Dto\OptionsUsecaseDto;
use App\Usecase\Dto\GetUserUsecaseDto;
use App\Usecase\Dto\DeleteUserUsecaseDto;
use App\Usecase\Dto\UpdateUserUsecaseDto;
use App\Usecase\Dto\CreateUserUsecaseDto;
use App\Usecase\UpdateUserPasswordUsecase;
use App\Controller\Helper\ControllerHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Usecase\CreateUserUsecase\CreateUserUsecase;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class ApiUserController extends ControllerHelper
{
  private Security $security;
  private GetUserUsecase $getUserUsecase;
  private GetUsersUsecase $getUsersUsecase;
  private CreateUserUsecase $createUserUsecase;
  private UpdateUserUsecase $updateUserUsecase;
  private DeleteUserUsecase $deleteUserUsecase;
  private UpdateUserPasswordUsecase $updateUserPasswordUsecase;

  public function __construct(
    Inversify                 $inversify,
    Security                  $security,
    GetUserUsecase            $getUserUsecase,
    SerializerInterface       $serializer,
    GetUsersUsecase           $getUsersUsecase,
    UpdateUserUsecase         $updateUserUsecase,
    DeleteUserUsecase         $deleteUserUsecase,
    UpdateUserPasswordUsecase $updateUserPasswordUsecase
  )
  {
    $this->security = $security;
    $this->serializer = $serializer;
    $this->getUserUsecase = $getUserUsecase;
    $this->getUsersUsecase = $getUsersUsecase;
    $this->updateUserUsecase = $updateUserUsecase;
    $this->deleteUserUsecase = $deleteUserUsecase;
    $this->createUserUsecase = $inversify->createUserUsecase;
    $this->updateUserPasswordUsecase = $updateUserPasswordUsecase;
  }

  /**
   * @IsGranted("ROLE_ADMINISTRATOR", message="You do not have the rights to access this page.")
   * @Route("/api/user/", methods={"POST"}, name="api_user_post")
   * @param Request $request
   * @return Response
   * @throws ExceptionInterface
   */
  public function create(Request $request): Response
  {
    try {
      if (!$request->isXmlHttpRequest()) {
        return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
      }
      $dto = new CreateUserUsecaseDto();
      if ($_ENV["AUTH_METHOD"] == "BDD") {
        $dto->lastName = (new GenericValidator($this->getParam($request, 'lastName'), REGEXS::$REGEX_NAME))->get();
        $dto->firstName = (new GenericValidator($this->getParam($request, 'firstName'), REGEXS::$REGEX_NAME))->get();
        $dto->email = (new GenericValidator($this->getParam($request, 'email'), REGEXS::$REGEX_EMAIL))->get();
      }
      if ($_ENV["AUTH_METHOD"] == "AD") {
        $dto->adSamAccount = (new GenericValidator($this->getParam($request, 'adSamAccount'), REGEXS::$REGEX_NAME))->get();
        $dto->adDn = (new GenericValidator($this->getParam($request, 'adDn'), REGEXS::$REGEX_AD_DN))->get();
        $dto->adId = $this->getParam($request, 'adId');
      }
      $dto->login = (new GenericValidator($this->getParam($request, 'login'), REGEXS::$REGEX_NAME))->get();
      $dto->role = $this->getParam($request, 'role');
      $dto->user = $this->security->getUser();

      $result = $this->createUserUsecase->execute($dto);
      $serializable = $this->serializer->normalize($result, null, ['groups' => 'core']);
      $serializable['ad'] = $this->serializer->normalize($result->getAd(), null, ['groups' => 'ad']);
      return new JsonResponse($serializable, 200);

    } catch (Exception $e) {
      return new JsonResponse([
        'errorMessage' => $e->getMessage(),
        'message' => ERRORS::$API_USER_CREATE_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_USER_CREATE_FAIL
      ], 406);
    }
  }

  /**
   * @IsGranted("ROLE_ADMINISTRATOR", message="You do not have the rights to access this page.")
   * @Route("/api/user/", methods={"GET"}, name="api_user_get")
   * @param Request $request
   * @return Response
   * @throws ExceptionInterface
   */
  public function getAll(Request $request): Response
  {
    try {
      if (!$request->isXmlHttpRequest()) {
        return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
      }

      $options = new OptionsUsecaseDto($request->headers->get('meta-data-guardian'));

      $result = $this->getUsersUsecase->execute($options);

      $serializable = $this->serializer->normalize($result->data, null, ['groups' => 'core']);

      $response = new JsonResponse($serializable, 200);

      $response->headers->add([
        'meta-data-guardian' => json_encode($result->meta),
      ]);

      return $response;
    } catch (Exception $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_USER_GET_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_USER_GET_FAIL
      ], 406);
    }
  }

  /**
   * @IsGranted("ROLE_ADMINISTRATOR", message="You do not have the rights to access this page.")
   * @Route("/api/user/{id}", methods={"GET"}, name="api_user_id_get")
   * @param Request $request
   * @param int $id
   * @return Response
   * @throws ExceptionInterface
   */
  public function getById(int $id, Request $request): Response
  {
    try {
      if (!$request->isXmlHttpRequest()) {
        return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
      }

      $dto = new GetUserUsecaseDto();
      $dto->id = $id;
      $result = $this->getUserUsecase->execute($dto);
      $serializable = $this->serializer->normalize($result, null, ['groups' => 'core']);
      $serializable['ad'] = $this->serializer->normalize($result->getAd(), null, ['groups' => 'ad']);

      return new JsonResponse($serializable, 200);
    } catch (Exception $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_USER_GET_ID_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_USER_GET_ID_FAIL
      ], 406);
    }
  }

  /**
   * @IsGranted("ROLE_USER", message="You do not have the rights to access this page.")
   * @Route("/api/user/{id}", methods={"PATCH"}, name="api_user_id_put")
   * @param int $id
   * @param Request $request
   * @return Response
   * @throws ExceptionInterface
   */
  public function updateById(int $id, Request $request): Response
  {
    try {
      if (!$request->isXmlHttpRequest()) {
        return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
      }

      $dto = new UpdateUserUsecaseDto();
      $dto->id = $id;
      $dto->update = $this->getParam($request, 'update');
      if ($this->security->getUser()->getRoles() == array('ROLE_USER') && $this->security->getUser()->getId() != $id) {
        return new JsonResponse([
          'message' => ERRORS::$NOT_ALLOWED,
          'error' => ERRORS::$NOT_ALLOWED
        ], 406);
      }

      $roles = $this->security->getUser()->getRoles();
      if (
        isset($dto->update['role']) && $dto->update['role'] == 'ROLE_ADMINISTRATOR'
        && !in_array('ROLE_ADMINISTRATOR', $roles)
        && !in_array('ROLE_SUPER_ADMINISTRATOR', $roles)
      ) {
        return new JsonResponse([
          'message' => ERRORS::$NOT_ALLOWED,
          'error' => ERRORS::$NOT_ALLOWED
        ], 406);
      }

      if (isset($dto->update['role']) && $dto->update['role'] == 'ROLE_SUPER_ADMINISTRATOR') {
        return new JsonResponse([
          'message' => ERRORS::$NOT_ALLOWED,
          'error' => ERRORS::$NOT_ALLOWED
        ], 406);
      }

      $result = $this->updateUserUsecase->execute($dto);
      $serializable = $this->serializer->normalize($result, null, ['groups' => 'core']);
      $serializable['ad'] = $this->serializer->normalize($result->getAd(), null, ['groups' => 'ad']);

      return new JsonResponse($serializable, 200);
    } catch (Exception $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_USER_UPDATE_BY_ID_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_USER_UPDATE_BY_ID_FAIL
      ], 406);
    }
  }

  /**
   * @IsGranted("ROLE_ADMINISTRATOR", message="You do not have the rights to access this page.")
   * @Route("/api/user/{id}", methods={"DELETE"}, name="api_user_id_delete")
   * @param Request $request
   * @param int $id
   * @return Response
   * @throws ExceptionInterface
   */
  public function deleteById(int $id, Request $request): Response
  {
    try {
      if (!$request->isXmlHttpRequest()) {
        return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
      }

      $dto = new DeleteUserUsecaseDto();
      $dto->id = $id;
      $result = $this->deleteUserUsecase->execute($dto);
      $serializable = $this->serializer->normalize($result);

      return new JsonResponse($serializable, 200);
    } catch (Exception $e) {
      return new JsonResponse([
        'message' => ERRORS::$API_USER_DELETE_ID_FAIL,
        'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_USER_DELETE_ID_FAIL
      ], 406);
    }
  }
}