<?php

namespace App\Controller\API;

use Exception;
use App\Common\ERRORS;
use App\Validator\REGEXS;
use App\Validator\IdValidator;
use App\Usecase\GetFolderUsecase;
use App\Usecase\EditFolderUsecase;
use App\Validator\StringValidator;
use App\Validator\GenericValidator;
use App\Usecase\DeleteFolderUsecase;
use App\Usecase\CreateFolderUsecase;
use App\Usecase\Dto\EditFolderUsecaseDto;
use App\Usecase\Dto\DeleteFolderUsecaseDto;
use App\Controller\Helper\ControllerHelper;
use App\Usecase\Dto\CreateFolderUsecaseDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @IsGranted("ROLE_USER", message="You do not have the rights to access this page.")
 */
class ApiFolderController extends ControllerHelper {
	private GetFolderUsecase $getFolderUsecase;
	private EditFolderUsecase $editFolderUsecase;
  private DeleteFolderUsecase $deleteFolderUsecase;
	private CreateFolderUsecase $createFolderUsecase;

	public function __construct(
		SerializerInterface $serializer,
		GetFolderUsecase $getFolderUsecase,
		EditFolderUsecase $editFolderUsecase,
		CreateFolderUsecase $createFolderUsecase,
    DeleteFolderUsecase $deleteFolderUsecase
	) {
		$this->serializer = $serializer;
		$this->getFolderUsecase = $getFolderUsecase;
		$this->editFolderUsecase = $editFolderUsecase;
		$this->createFolderUsecase = $createFolderUsecase;
    $this->deleteFolderUsecase = $deleteFolderUsecase;
	}

  /**
   * @Route("/api/folder/{id}", methods={"GET"}, name="api_folder_get")
   * @param int $id
   * @param Request $request
   * @return Response
   */
	public function getInfo(int $id, Request $request): Response {
		try {
			if (!$request->isXmlHttpRequest()) {
				return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
			}

			$result = $this->getFolderUsecase->execute($id);

			$logs = array_filter($result->logs, function($v, $k) {
				return $v->getAction() == 'CREATE' || $v->getAction() == 'UPDATE'  || $v->getAction() == 'CREATED' || $v->getAction() == 'CANCELED';
			}, ARRAY_FILTER_USE_BOTH);

			$logs = array_map(function($v) {
				$paramName = "";
				if($v->getAction() == "UPDATE") {
					$paramName = array_keys($v->getNewData())[0];
				}
				return array(
					'action' => $v->getAction(),
					'user' => $v->getLogin()->getFirstName().' '. $v->getLogin()->getLastName(),
					'createdAt' => gmdate('Y-m-d H:i:s', $v->getCreatedAt()->getTimestamp()),
					'paramName' => $paramName
				);
			}, $logs);

			return $this->json(array(
				'name' => $result->folder->getName(),
				'description' => $result->folder->getDescription(),
				'children' => count($result->folder->getItems()) + count($result->folder->getFolders()),
				'creator' => $result->folder->getCreator()->getFirstName().' '. $result->folder->getCreator()->getLastName(),
				'createAt' => gmdate('Y-m-d H:i:s', $result->folder->getCreatedAt()->getTimestamp()),
				'history' => $logs
			), 200);
		} catch (Exception $e) {
			return $this->json([
				'message' => ERRORS::$API_FOLDER_GET,
				'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_FOLDER_GET
			], 406);
		}
	}

  /**
   * @Route("/api/folder/", methods={"POST"}, name="api_folder_create")
   * @param Request $request
   * @return Response
   * @throws ExceptionInterface
   */
	public function create(Request $request): Response {
		try {
			if (!$request->isXmlHttpRequest()) {
				return $this->json(['error' => ERRORS::$WRONG_PROTOCOL], 400);
			}

			$dto = new CreateFolderUsecaseDto();
			$dto->user = $this->getUser();
      $dto->name = (new GenericValidator($this->getParam($request, 'name'), REGEXS::$REGEX_FOLDER_NAME))->get();
			$dto->chestId = (new IdValidator($request->get('chestId')))->get();
      $dto->description = (new GenericValidator($this->getParam($request, 'description'), REGEXS::$REGEX_ITEM_DESCRIPTION, true))->get();
			$dto->parentFolderId = (new IdValidator($request->get('parentFolderId')))->get();

			$result = $this->createFolderUsecase->execute($dto);
      $serializable = $this->serializer->normalize($result, null, ['groups' => 'core']);

      return new JsonResponse($serializable, 200);
		} catch (Exception $e) {
			return $this->json([
				'message' => ERRORS::$API_FOLDER_CREATE,
				'error' => ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$API_FOLDER_CREATE
			], 406);
		}
	}

	/**
	 * Delete folder
	 * @Route("/api/folder/{id}", name="api_folder_delete", methods="DELETE")
	 */
	public function folderDelete(int $id, Request $request): JsonResponse
	{
		if (!$request->isXmlHttpRequest()) {
			throw new BadRequestHttpException(ERRORS::$CANNOT_ACCESS_THIS_PAGE);
		}

		try {
      $dto = new DeleteFolderUsecaseDto();
      $dto->id = $id;
      $dto->user = $this->getUser();

      $this->deleteFolderUsecase->execute($dto);

			return new JsonResponse("Dossier supprimé", 200);
		} catch (\Exception $e) {
			return new JsonResponse(["message" => "Echec de la suppression"], 500);
		}
	}

	/**
	 * Update folder's name or description
	 * @Route("/api/folder/{id}", name="api_folder_edit", methods="PATCH")
	 */
	public function folderEdit(string $id, Request $request): ?Response {
		try {
			if (!$request->isXmlHttpRequest()){
				throw new BadRequestHttpException(ERRORS::$CANNOT_ACCESS_THIS_PAGE);
			}

			$dto = new EditFolderUsecaseDto();
			$dto->id = $id;
      $dto->user = $this->getUser();
      $dto->type = (new StringValidator($this->getParam($request,'elementType')))->get();
      $dto->value = (new StringValidator($this->getParam($request, 'elementValue')))->get();

      if ($dto->type === 'name') {
        new GenericValidator($dto->value, REGEXS::$REGEX_FOLDER_NAME);
      } elseif ($dto->type === 'description') {
        new GenericValidator($dto->value, REGEXS::$REGEX_ITEM_DESCRIPTION, true);
      }

			$result = $this->editFolderUsecase->execute($dto);

			return new JsonResponse([
				'message' => "Dossier mis à jour",
				'id' => $result->getId()
			], 200);
		} catch (Exception $e) {
			return new JsonResponse(["message" => "Echec de l'édition"], 500);
		}
	}
}