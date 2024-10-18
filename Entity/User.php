<?php

namespace App\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Scheb\TwoFactorBundle\Model\Google\TwoFactorInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 * @ORM\Table(name="`user`")
 * @UniqueEntity("login", message="This login is already used.")
 */
class User implements UserInterface, TwoFactorInterface, PasswordAuthenticatedUserInterface
{
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("core")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=180, unique=true)
   * @Assert\NotNull(message="This field cannot be blank")
   * @Groups("core")
   */
  private $login;

  /**
   * @ORM\Column(type="string", length=100, nullable=true)
   * @Groups("core")
   */
  private $password;

  /**
   * @ORM\Column(type="string", length=100, nullable=true)
   * @Groups("core")
   */
  private $firstName;

  /**
   * @ORM\Column(type="string", length=100, nullable=true)
   * @Groups("core")
   */
  private $lastName;

  /**
   * @ORM\Column(type="string", length=100, nullable=true, unique=true)
   * @Groups("core")
   */
  private $email;

  /**
   * @ORM\Column(type="boolean", options={"default":false})
   */
  private $readFeatures = false;

  /**
   * @ORM\Column(type="boolean")
   * @Groups("core")
   */
  private $blocked;

  /**
   * @ORM\Column(type="boolean", options={"default":false})
   * @Groups("core")
   */
  private ?bool $passwordExpiration = false;

  /**
   * @ORM\Column(type="datetime", nullable=true)
   * @Groups("core")
   */
  private $blocked_until;

  /**
   * @ORM\ManyToOne(targetEntity=State::class)
   * @ORM\JoinColumn(nullable=false)
   * @Groups("core")
   */
  private $state;

  /**
   * @ORM\Column(type="json")
   * @Groups("core")
   */
  private $roles = [];

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("core")
   */
  private $googleAuthenticatorSecret;

  /**
   * @ORM\Column(type="boolean")
   * @Groups("core")
   */
  private $qrScanned;

  /**
   * @ORM\Column(type="string", length=70, nullable=true)
   * @Groups("core")
   */
  private $sam_account;

  /**
   * @ORM\ManyToOne(targetEntity=AD::class, inversedBy="users")
   * @ORM\JoinColumn(nullable=true)
   * @Groups("ad")
   */
  private $ad;

  /**
   * @ORM\Column(type="string", length=255, nullable=true)
   * @Groups("core")
   */
  private $ad_dn;

  /**
   * @ORM\Column(type="integer", nullable=true)
   * @Groups("core")
   */
  private $lastChestId;

  /**
   * @ORM\ManyToOne(targetEntity=User::class)
   * @ORM\JoinColumn(nullable=true)
   * @Groups("core")
   */
  private $creator;

  /**
   * @ORM\Column(type="datetime_immutable", nullable=true)
   * @Groups("core")
   */
  private $createdAt;

  /**
   * @ORM\ManyToMany(targetEntity=Chest::class, mappedBy="users")
   * @Groups("user_chest")
   */
  private $chests;

  /**
   * @ORM\OneToMany(targetEntity=Item::class, mappedBy="creator")
   * @Groups("user_item")
   */
  private $items;

  /**
   * @ORM\OneToMany(targetEntity=Folder::class, mappedBy="creator")
   * @Groups("user_folder")
   */
  private $folders;

  /**
   * @ORM\OneToMany(targetEntity=LogItem::class, mappedBy="login")
   * @Groups("user_log_item")
   */
  private $logItems;

  /**
   * @ORM\OneToMany(targetEntity=LogFolder::class, mappedBy="login")
   * @Groups("user_log_folder")
   */
  private $logFolders;

  /**
   * @ORM\OneToMany(targetEntity=Trash::class, mappedBy="LastUserAction")
   */
  private $trashes;

  ///////////////////////////////////////// Constructor

  /**
   *
   */
  public function __construct() {
    $this->createdAt = new DateTimeImmutable('now');
    $this->items = new ArrayCollection();
    $this->folders = new ArrayCollection();
    $this->chests = new ArrayCollection();
    $this->logItems = new ArrayCollection();
    $this->logFolders = new ArrayCollection();
    $this->trashes = new ArrayCollection();
  }

  ///////////////////////////////////////// Generic method




  /**
   * @see PasswordAuthenticatedUserInterface
   * @return string|null
   */
  public function getPassword(): ?string
  {
    return $this->password;
  }


  public function setPassword(string $password): self
  {
    $this->password = $password;

    return $this;
  }

  /**
   * Returning a salt is only needed if you are not using a modern
   * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
   *
   * @see UserInterface
   */
  public function getSalt(): ?string
  {
    return null;
  }

  /**
   * @see UserInterface
   */
  public function eraseCredentials()
  {
    // If you store any temporary, sensitive data on the user, clear it here
    // $this->plainPassword = null;
  }

  /**
   * @return bool
   */
  public function getPasswordExpiration(): ?bool {
    return $this->passwordExpiration;
  }

  /**
   * @param bool $passwordExpiration
   * @return $this
   */
  public function setPasswordExpiration(bool $passwordExpiration): self {
    $this->passwordExpiration = $passwordExpiration;
    return $this;
  }



  ///////////////////////////////////////// GET SETTER
  /**
   * @return int|null
   */
  public function getId(): ?int {
    return $this->id;
  }

  /**
   * @return string|null
   */
  public function getSamAccount(): ?string {
    return $this->sam_account;
  }

  /**
   * @param string|null $sam_account
   * @return $this|null
   */
  public function setSamAccount(?string $sam_account): self {
    $this->sam_account = $sam_account;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getLogin(): ?string {
    return $this->login;
  }

  /**
   * @param string $login
   * @return $this
   */
  public function setLogin(string $login): self {
    $this->login = $login;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getFirstName(): ?string {
    return $this->firstName;
  }

  /**
   * @param string|null $firstName
   * @return $this
   */
  public function setFirstName(?string $firstName): self {
    $this->firstName = $firstName;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getLastName(): ?string {
    return $this->lastName;
  }

  /**
   * @return string|null
   */
  public function getEmail(): ?string {
    return $this->email;
  }

  /**
   * @param string|null $email
   * @return $this
   */
  public function setEmail(?string $email): self {
    $this->email = $email;
    return $this;
  }

  /**
   * @param string|null $lastName
   * @return $this
   */
  public function setLastName(?string $lastName): self {
    $this->lastName = $lastName;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  public function getUserIdentifier(): string {
    if (!empty($this->firstName) && !empty($this->lastName))
      return $this->firstName . " " . $this->lastName;
    else
      return $this->login;
  }

  /**
   * @deprecated since Symfony 5.3, use getUserIdentifier instead
   */
  public function getUsername(): string {
    return (string) $this->login;
  }

  /**
   * @see UserInterface
   */
  public function getRoles(): array {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  /**
   * @param array $roles
   * @return $this
   */
  public function setRoles(array $roles): self {
    $this->roles = $roles;

    return $this;
  }

  /**
   * @return bool|null
   */
  public function getBlocked(): ?bool {
    return $this->blocked;
  }

  /**
   * @param bool $blocked
   * @return $this
   */
  public function setBlocked(bool $blocked): self {
    $this->blocked = $blocked;

    return $this;
  }

  /**
   * @return DateTimeInterface|null
   */
  public function getBlockedUntil(): ?DateTimeInterface {
    return $this->blocked_until;
  }

  /**
   * @param DateTimeInterface|null $blocked_until
   * @return $this
   */
  public function setBlockedUntil(?DateTimeInterface $blocked_until): self {
    $this->blocked_until = $blocked_until;

    return $this;
  }

  /**
   * @return State|null
   */
  public function getState(): ?State {
    return $this->state;
  }

  /**
   * @param AD|null $ad
   * @return $this|null
   */
  public function setAd(?AD $ad): self {
    $this->ad = $ad;

    return $this;
  }

  /**
   * @return AD|null
   */
  public function getAd(): ?AD {
    return $this->ad;
  }

  /**
   * @return string|null
   */
  public function getAdDn(): ?string {
    return $this->ad_dn;
  }

  /**
   * @param string|null $ad_dn
   * @return $this
   */
  public function setAdDn(?string $ad_dn): self {
    $this->ad_dn = $ad_dn;

    return $this;
  }

  /**
   * @param State|null $state
   * @return $this
   */
  public function setState(?State $state): self {
    $this->state = $state;

    return $this;
  }

  /**
   * @return bool
   */
  public function isGoogleAuthenticatorEnabled(): bool {
    return (bool)$this->googleAuthenticatorSecret;
  }

  /**
   * @return string
   */
  public function getGoogleAuthenticatorUsername(): string {
    return $_ENV['TWOFA_NAME'];
  }

  /**
   * @return string|null
   */
  public function getGoogleAuthenticatorSecret(): ?string {
    return $this->googleAuthenticatorSecret;
  }

  /**
   * @param string|null $googleAuthenticatorSecret
   * @return $this
   */
  public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): self {
    $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

    return $this;
  }

  /**
   * @return bool|null
   */
  public function getQrScanned(): ?bool {
    return $this->qrScanned;
  }

  /**
   * @param bool $qrScanned
   * @return $this
   */
  public function setQrScanned(bool $qrScanned): self {
    $this->qrScanned = $qrScanned;

    return $this;
  }

  /**
   * @return Collection|Chest[]
   */
  public function getChests(): Collection {
    return $this->chests;
  }

  /**
   * @param Chest|null $chest
   * @return $this
   */
  public function addChest(Chest $chest): self {
    if (!$this->chests->contains($chest)) {
      $this->chests[] = $chest;
    }

    return $this;
  }

  /**
   * @param Chest $chest
   * @return $this
   */
  public function removeChest(Chest $chest): self {
    $this->chests->removeElement($chest);

    return $this;
  }

  /**
   * @return Collection|Item[]
   */
  public function getItems(): Collection {
    return $this->items;
  }

  /**
   * @param Item $item
   * @return $this
   */
  public function addItem(Item $item): self {
    if (!$this->items->contains($item)) {
      $this->items[] = $item;
      $item->setCreator($this);
    }

    return $this;
  }

  /**
   * @param Item $item
   * @return $this
   */
  public function removeItem(Item $item): self {
    if ($this->items->removeElement($item)) {
      // set the owning side to null (unless already changed)
      if ($item->getCreator() === $this) {
        $item->setCreator(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection|Folder[]
   */
  public function getFolders(): Collection {
    return $this->folders;
  }

  /**
   * @param Folder $folder
   * @return $this
   */
  public function addFolder(Folder $folder): self {
    if (!$this->folders->contains($folder)) {
      $this->folders[] = $folder;
      $folder->setCreator($this);
    }

    return $this;
  }

  /**
   * @param Folder $folder
   * @return $this
   */
  public function removeFolder(Folder $folder): self {
    if ($this->folders->removeElement($folder)) {
      // set the owning side to null (unless already changed)
      if ($folder->getCreator() === $this) {
        $folder->setCreator(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection|LogItem[]
   */
  public function getLogItems(): Collection {
    return $this->logItems;
  }

  /**
   * @param LogItem $logItem
   * @return $this
   */
  public function addLogItem(LogItem $logItem): self {
    if (!$this->logItems->contains($logItem)) {
      $this->logItems[] = $logItem;
      $logItem->setLogin($this);
    }

    return $this;
  }

  /**
   * @param LogItem $logItem
   * @return $this
   */
  public function removeLogItem(LogItem $logItem): self {
    if ($this->logItems->removeElement($logItem)) {
      // set the owning side to null (unless already changed)
      if ($logItem->getLogin() === $this) {
        $logItem->setLogin(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection|LogFolder[]
   */
  public function getLogFolders(): Collection {
    return $this->logFolders;
  }

  /**
   * @param LogFolder $logFolder
   * @return $this
   */
  public function addLogFolder(LogFolder $logFolder): self {
    if (!$this->logFolders->contains($logFolder)) {
      $this->logFolders[] = $logFolder;
      $logFolder->setLogin($this);
    }

    return $this;
  }

  /**
   * @param LogFolder $logFolder
   * @return $this
   */
  public function removeLogFolder(LogFolder $logFolder): self {
    if ($this->logFolders->removeElement($logFolder)) {
      // set the owning side to null (unless already changed)
      if ($logFolder->getLogin() === $this) {
        $logFolder->setLogin(null);
      }
    }

    return $this;
  }

  /**
   * @return bool|null
   */
  public function getReadFeatures(): ?bool {
    return $this->readFeatures;
  }

  /**
   * @param bool $readFeatures
   * @return $this
   */
  public function setReadFeatures(bool $readFeatures): self {
    $this->readFeatures = $readFeatures;

    return $this;
  }

  /**
   * @return User|null
   */
  public function getCreator(): ?User {
    return $this->creator;
  }

  /**
   * @param User|null $creator
   * @return $this
   */
  public function setCreator(?User $creator): self {
    $this->creator = $creator;

    return $this;
  }

  /**
   * @return DateTimeImmutable|null
   */
  public function getCreatedAt(): ?DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * @param int $lastChestId
   * @return $this
   */
  public function setLastChestId(int $lastChestId): self {
    $this->lastChestId = $lastChestId;

    return $this;
  }

  /**
   * @return int|null
   */
  public function getLastChestId(): ?int {
    return $this->lastChestId;
  }

  /**
   * @param DateTimeImmutable $createdAt
   * @return $this
   */
  public function setCreatedAt(DateTimeImmutable $createdAt): self {
    $this->createdAt = $createdAt;

    return $this;
  }

  ////////////////// CUSTOM METHOD

  /**
   * @return string
   */
  public function __toString() {
    return ucfirst($this->firstName) . ' ' . strtoupper($this->lastName);
  }

  /**
   * @param Folder|Item $element
   * @return bool
   */
  public function hasRights($element): bool {
    $userChests = $this->getChests();
    $elmentChest = $element->getChest();
    if ($userChests->contains($elmentChest) === true) {
      return true;
    }
    return false;
  }

  /**
   * @return Collection<int, Trash>
   */
  public function getTrashes(): Collection
  {
      return $this->trashes;
  }

  public function addTrash(Trash $trash): self
  {
      if (!$this->trashes->contains($trash)) {
          $this->trashes[] = $trash;
          $trash->setLastUserAction($this);
      }

      return $this;
  }

  public function removeTrash(Trash $trash): self
  {
      if ($this->trashes->removeElement($trash)) {
          // set the owning side to null (unless already changed)
          if ($trash->getLastUserAction() === $this) {
              $trash->setLastUserAction(null);
          }
      }

      return $this;
  }

}
