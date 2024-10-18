<?php

namespace App\Usecase\TrashUsecases;

use Exception;
use App\Common\ERRORS;
use App\Usecase\Usecase;
use App\Usecase\HasRightUsecase;
use App\Repository\TrashRepository;
use App\Repository\ChestRepository;
use App\Repository\LogUsageRepository;
use App\Usecase\Dto\OptionsUsecaseDto;
use App\Usecase\Dto\HasRightUsecaseDto;
use App\Usecase\Dto\ListOptionsUsecase;
use App\Usecase\Model\MetadataUsecaseModel;
use Symfony\Component\Security\Core\Security;
use App\Usecase\Dto\GetTrashElementsUpdateUsecaseDto;

/**
 * @method ListOptionsUsecase execute(OptionsUsecaseDto $dto)
 */
class GetTrashElementsUsecase extends Usecase {
  private TrashRepository $trashRepository;
  private ChestRepository $chestRepository;
  private HasRightUsecase $hasRightUsecase;

  public function __construct(
    Security $security,
    TrashRepository $trashRepository,
    HasRightUsecase $hasRightUsecase,
    ChestRepository $chestRepository,
    LogUsageRepository $logUsageRepository
  ) {
    parent::__construct(
      $security,
      $logUsageRepository
    );
    $this->trashRepository = $trashRepository;
    $this->chestRepository = $chestRepository;
    $this->hasRightUsecase = $hasRightUsecase;
  }

  /**
   * @param OptionsUsecaseDto $dtoOption
   * @param GetTrashElementsUpdateUsecaseDto $dto
   * @return ListOptionsUsecase
   * @throws Exception
   */
  public function _execute(OptionsUsecaseDto $dtoOption, GetTrashElementsUpdateUsecaseDto $dto): ListOptionsUsecase
  {
    try {
      $dtoUsecase = new HasRightUsecaseDto();
      $dtoUsecase->user = $dto->currentUser;
      $dtoUsecase->chest = $this->chestRepository->find($dto->chestId);
      $dtoUsecase->op = HasRightUsecase::$READ;
      $this->hasRightUsecase->execute($dtoUsecase);
      $currentChest = $dtoUsecase->chest;

      /* Get elements from trash without the one with 'deleted' status */
      $result =  $this->trashRepository->findByChest($dto->chestId, 'deleted', $dtoOption);
      $metadataUsecaseModel = new MetadataUsecaseModel($dtoOption);
      $metadataUsecaseModel->countTotal = $this->trashRepository->count([]);
      $result = new ListOptionsUsecase($result, $metadataUsecaseModel);

      $result->chest = $currentChest;
      $result->chestName = $dtoUsecase->chest->getName();

      return $result;
    } catch (Exception $e) {
      throw new Exception(ERRORS::isERRORS($e->getMessage()) ? $e->getMessage() : ERRORS::$GET_TRASH_USECASE_FAIL);
    }
  }
}