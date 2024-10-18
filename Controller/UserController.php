<?php

namespace App\Controller;

use Exception;
use App\Common\ERRORS;
use App\Usecase\GetAdsUsecase;
use App\Usecase\GetUsersUsecase;
use App\Usecase\Dto\OptionsUsecaseDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @IsGranted("ROLE_ADMINISTRATOR", message="You do not have the rights to access this page.")
 */
class UserController extends AbstractController {

  /**
   * @var GetUsersUsecase
   */
  private GetUsersUsecase  $getUsersUsecase;

  /**
   * @var GetAdsUsecase
   */
  private GetAdsUsecase  $getAdsUsecase;

  public function __construct(
    GetAdsUsecase $getAdsUsecase,
    GetUsersUsecase $getUsersUsecase
  ) {
    $this->getAdsUsecase = $getAdsUsecase;
    $this->getUsersUsecase = $getUsersUsecase;
  }

  /**
   * @Route("/routes/user/", name="routes_user_")
   */
  public function index(Request $request): Response {
    return $this->render('user/index.html.twig', []);
  }

  /**
   * @Route("/actions/user/get_content", name="actions_user_getContent")
   * @throws Exception
   */
  public function getContent(Request $request): Response {
    try {
      if (!$request->isXmlHttpRequest()) {
        throw new BadRequestHttpException(ERRORS::$CANNOT_ACCESS_THIS_PAGE);
      }
      $authMethods = $_ENV['AUTH_METHOD'] ?? 'AD';

      $options = new OptionsUsecaseDto($request->headers->get('meta-data-guardian'));
      $options->limit = 10000;

      $ads = $this->getAdsUsecase->execute(new OptionsUsecaseDto());
      $defaultPathAd = $_ENV['AD_DEFAULT_DN']??'OU=Utilisateurs,OU=Xefi,DC=Xefi,DC=priv';

      $result = $this->getUsersUsecase->execute($options);

      return $this->render('user/content.html.twig', [
        'data' => array(
          'users' => $result->data,
          'ads' => $ads->data,
          'adDnDefault' => $defaultPathAd,
          'authMethods' => $authMethods
        ),
      ]);
    } catch (Exception $e) {
      $this->addFlash('error', ERRORS::$CTRL_USER_GET_CONTENT_FAIL);
      return $this->render('user/content.html.twig', [
        'data' => array(
          'users' => [],
          'ads' => [],
          'adDnDefault' => ''
        ),
      ]);
    }
  }
}