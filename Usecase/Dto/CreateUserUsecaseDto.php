<?php

namespace App\Usecase\Dto;

use App\Entity\AD;
use App\Entity\User;

class CreateUserUsecaseDto {
  /**
   * @var string The login
   */
  public string $login;

  /**
   * @var int|null the AD id
   */
  public ?int $adId  = null;

  /**
   * @var string|null the user email
   */
  public ?string $email  = null;

  /**
   * @var string|null Security Account Manager (SAM) - account in the AD
   */
  public ?string $adSamAccount  = null;

  /**
   * @var string|null Distinguished Name (DN) - path from the root to the resource
   */
  public ?string $adDn  = null;

  /**
   * @var string the role
   */
  public ?string $role  = null;

  /**
   * @var string|null the role
   */
  public ?string $firstName = null;

  /**
   * @var string|null the role
   */
  public ?string $lastName = null;

  /**
   * @var string|null
   */
  public ?string $password = null;

  /**
   * @var User|null $user the role
   */
  public ?User $user = null;

  /**
   * @var AD|null $ad the ad
   */
  public ?AD $ad = null;
}