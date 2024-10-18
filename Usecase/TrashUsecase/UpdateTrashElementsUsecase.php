<?php

namespace App\Usecase\TrashUsecases;

use Exception;
use App\Entity\Item;
use App\Entity\Trash;
use App\Entity\Folder;
use App\Common\ERRORS;
use App\Usecase\Usecase;
use App\Service\LogService;
use App\Usecase\HasRightUsecase;
use App\Repository\ItemRepository;
use App\Repository\TrashRepository;
use App\Repository\FolderRepository;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\HasRightUsecaseDto;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use App\Usecase\Dto\GetTrashElementsUpdateUsecaseDto;

/**
 * @method bool execute(GetTrashElementsUpdateUsecaseDto $dto)
 */
class UpdateTrashElementsUsecase extends Usecase {
  private LogService $logService;
  private SerializerInterface $serializer;
  private TrashRepository $trashRepository;

  public function __construct(
    Security $security,
    LogService $logService,
    ItemRepository $itemRepository,
    SerializerInterface $serializer,
    HasRightUsecase $hasRightUsecase,
    TrashRepository $trashRepository,
    FolderRepository $folderRepository,
    LogUsageRepository $logUsageRepository
  ) {
    parent::__construct(
      $security,
      $logUsageRepository
    );
    $this->logService = $logService;
    $this->serializer = $serializer;
    $this->itemRepository = $itemRepository;
    $this->hasRightUsecase = $hasRightUsecase;
    $this->trashRepository = $trashRepository;
    $this->folderRepository = $folderRepository;
  }

  /**
   * @param $dto GetTrashElementsUpdateUsecaseDto
   * @return Trash
   * @throws Exception
   */
  public function _execute(GetTrashElementsUpdateUsecaseDto $dto): Trash {
      try {
        if (empty($dto->update)) {
          throw new Exception(ERRORS::$UPDATE_TRASH_USECASE_FAIL_CHANGE_EMPTY);
        }

      /* @var Trash $trash */
      $trash = $this->trashRepository->find($dto->id);
      if (!$trash) {
          throw new Exception(ERRORS::$UPDATE_TRASH_USECASE_FAIL_TRASH_NOT_FOUND);
      }

        $dtoUsecase = new HasRightUsecaseDto();
        $dtoUsecase->user = $this->security->getUser();

        $guardian = true;

          switch ($dto->update) {
            case 'delete':
              if ($trash->getState() != 'deleted') {
                if($trash->getFolder() !== null){
                  /* @var Folder $folder */
                  $folder = $trash->getFolder();
                  $oldFolder = $this->serializer->normalize($folder, null, ['groups' => 'folder']);

                  $dtoUsecase->chest = $folder->getChest();
                  $dtoUsecase->op = HasRightUsecase::$CREATE;
                  $this->hasRightUsecase->execute($dtoUsecase);

                  $folder->setModifiedAt(new \DateTimeImmutable());
                  $trash->setModifiedAt(new \DateTimeImmutable());
                  $trash->setLastUserAction($dto->currentUser);

                  $this->logService->logFolder(LogService::DELETED, $folder, $oldFolder);
                  $this->logService->logTrash(LogService::DELETED, $dto->currentUser, $trash);

                  $trash->setState('deleted');
                  $this->folderRepository->push($folder);

                  $guardian = false;
                }

                if($trash->getItem() !== null){
                  /* @var Item $item */
                  $item = $trash->getItem();
                  $oldItem = $this->serializer->normalize($item, 'json', ['groups' => 'item']);

                  $dtoUsecase->chest = $item->getChest();
                  $dtoUsecase->op = HasRightUsecase::$CREATE;
                  $this->hasRightUsecase->execute($dtoUsecase);

                  $item->setModifiedAt(new \DateTimeImmutable());
                  $trash->setModifiedAt(new \DateTimeImmutable());
                  $trash->setLastUserAction($dto->currentUser);

                  $this->logService->logItem(LogService::DELETED, $item, $oldItem);
                  $this->logService->logTrash(LogService::DELETED, $dto->currentUser, $trash);

                  $trash->setState('deleted');
                  $this->itemRepository->push($item);

                  $guardian = false;
                }
              }
              break;
            case 'restore':
              if ($trash->getState() != 'canceled' && $trash->getState() != 'deleted') {
                $trash->setState('canceled');
                if($trash->getFolder() !== null){

                  /* @var Folder $folder */
                  $folder = $trash->getFolder();
                  $oldFolder = $this->serializer->normalize($folder, null, ['groups' => 'folder']);

                  $dtoUsecase->chest = $folder->getChest();
                  $dtoUsecase->op = HasRightUsecase::$CREATE;
                  $this->hasRightUsecase->execute($dtoUsecase);

                  $folder->setParent(null);
                  $trash->setModifiedAt(new \DateTimeImmutable());
                  $folder->setModifiedAt(new \DateTimeImmutable());
                  $trash->setLastUserAction($dto->currentUser);

                  $this->logService->logFolder(LogService::CANCELED, $folder, $oldFolder);
                  $this->logService->logTrash(LogService::CANCELED, $dto->currentUser, $trash);

                  $trash->setState('canceled');
                  $this->folderRepository->push($folder);

                  $guardian = false;
                }
                if($trash->getItem() !== null){

                  /* @var Item $item */
                  $item = $trash->getItem();
                  $oldItem = $this->serializer->normalize($item, 'json', ['groups' => 'item']);

                  $dtoUsecase->chest = $item->getChest();
                  $dtoUsecase->op = HasRightUsecase::$CREATE;
                  $this->hasRightUsecase->execute($dtoUsecase);

                  $item->setFolder(null);
                  $item->setModifiedAt(new \DateTimeImmutable());
                  $trash->setModifiedAt(new \DateTimeImmutable());
                  $trash->setLastUserAction($dto->currentUser);

                  $this->logService->logItem(LogService::CANCELED, $item, $oldItem);
                  $this->logService->logTrash(LogService::CANCELED,$dto->currentUser, $trash);

                  $trash->setState('canceled');
                  $this->itemRepository->push($item);

                  $guardian = false;
                }
              }
              break;
          }
        if ($guardian) {
          throw new Exception(ERRORS::$UPDATE_TRASH_USECASE_FAIL_CHANGE_WRONG);
        }

        $this->trashRepository->push($trash);

      return $trash;
    } catch (Exception $e) {
      throw new Exception(ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$UPDATE_TRASH_USECASE_FAIL);
    }
  }
}