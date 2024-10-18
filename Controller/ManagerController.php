<?php

namespace App\Controller;

use App\Common\ERRORS;
use App\Usecase\GetItemCategoryUsecase;
use App\Usecase\GetManagerContentUsecase;
use App\Usecase\OTP\GenerateTotpTokenUsecase;
use App\Usecase\OTP\GetExpirationOtpKeyUsecase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Usecase\Dto\GetManagerContentUsecaseDto;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @IsGranted("ROLE_USER", message="You do not have the rights to access this page.")
 */
class ManagerController extends AbstractController {
  /**
   * @var GetManagerContentUsecase
   */
  private GetManagerContentUsecase $getManagerContentUsecase;

  /**
   * @var GetItemCategoryUsecase
   */
  private GetItemCategoryUsecase  $getItemCategoryUsecase;
  private GetExpirationOtpKeyUsecase $getExpirationOtpKeyUsecase;

	public function __construct(
    GetManagerContentUsecase $getManagerContentUsecase,
    GetItemCategoryUsecase $getItemCategoryUsecase,
    GenerateTotpTokenUsecase $generateTotpTokenUsecase,
    GetExpirationOtpKeyUsecase $getExpirationOtpKeyUsecase
  ) {
    $this->getItemCategoryUsecase = $getItemCategoryUsecase;
		$this->getManagerContentUsecase = $getManagerContentUsecase;
    $this->generateTotpTokenUsecase = $generateTotpTokenUsecase;
    $this->getExpirationOtpKeyUsecase = $getExpirationOtpKeyUsecase;
  }

	/**
	 * @Route("/routes/manager/", name="routes_manager_")
	 */
	public function index(Request $request): Response {
		$chestId = trim($request->query->get('chestId'));
		$folderId = trim($request->query->get('folderId'));
		$selectId = trim($request->query->get('selectId'));
		$selectType = trim($request->query->get('selectType'));

    return $this->render('manager/index.html.twig', [
			'chestId' => $chestId,
			'folderId' => $folderId,
			'selectId' => $selectId,
			'selectType' => $selectType,
		]);
	}

	/**
	 * @Route("/actions/manager/get_content", name="actions_manager_getContent")
	 */
	public function getContent(Request $request): Response {
		if (!$request->isXmlHttpRequest()) {
			throw new BadRequestHttpException(ERRORS::$CANNOT_ACCESS_THIS_PAGE);
		}

    $categories = $this->getItemCategoryUsecase->execute()->itemCategory;

		$dto = new GetManagerContentUsecaseDto();
		$dto->currentUser = $this->getUser();
		$dto->chestId = trim($request->query->get('chestId'));
		$dto->folderId = trim($request->query->get('folderId'));
		$dto->selectId = trim($request->query->get('selectId'));
		$dto->selectType = trim($request->query->get('selectType'));

		$getManagerContentUsecaseModel = $this->getManagerContentUsecase->execute($dto);
    $timeUntilNextInterval = $this->getExpirationOtpKeyUsecase->_execute();

		return $this->render('manager/content.html.twig', [
			'chestId' => $dto->chestId,
			'folderId' => $dto->folderId,
			'selectId' => $dto->selectId,
			'selectType' => $dto->selectType,
			'data' => $getManagerContentUsecaseModel,
      'categories' => $categories,
      'otp' => $timeUntilNextInterval
		]);
	}
}