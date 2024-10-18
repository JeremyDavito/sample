<?php

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FolderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=FolderRepository::class)
 */
class Folder {
  /**
   * @ORM\Id
   * @ORM\GeneratedValue
   * @ORM\Column(type="integer")
   * @Groups("core")
   */
  private $id;

  /**
   * @ORM\Column(type="string", length=50)
   * @Assert\NotBlank(message="Le nom ne peut pas être vide")
   * @Assert\Length(
   *      max = 50,
   *      maxMessage = "Le nom ne peut pas être plus long que {{ limit }} caractères"
   * )
   * @Groups("core")
   */
  private $name;

  /**
   * @ORM\Column(type="text", nullable=true)
   * @Groups("core")
   */
  private $description;

  /**
   * @ORM\Column(type="datetime_immutable")
   * @Groups("core")
   */
  private $createdAt;

  /**
   * @ORM\Column(type="datetime_immutable")
   * @Groups("core")
   */
  private $modifiedAt;

  /**
   * @ORM\ManyToOne(targetEntity=User::class, inversedBy="folders")
   * @ORM\JoinColumn(nullable=false)
   * @Groups("core")
   */
  private $creator;

  /**
   * @ORM\ManyToOne(targetEntity=Folder::class, inversedBy="folders")
   */
  private $parent;

  /**
   * @ORM\OneToMany(targetEntity=Item::class, mappedBy="folder", cascade={"remove"})
   * @ORM\JoinColumn(onDelete="CASCADE")
   * @Groups("extend")
   */
  private $items;

  /**
   * @ORM\OneToMany(targetEntity=Folder::class, mappedBy="parent", cascade={"remove"})
   * @ORM\JoinColumn(onDelete="CASCADE")
   * @Groups("extend")
   */
  private $folders;

  /**
   * @ORM\ManyToOne(targetEntity=Chest::class, inversedBy="folders")
   * @Groups("core")
   */
  private $chest;

  /**
   * @ORM\OneToMany(targetEntity=Trash::class, mappedBy="Folder")
   * @Groups("extend")
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
    $this->trashes = new ArrayCollection();
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
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * @param string $name
   * @return $this
   */
  public function setName(string $name): self {
    $this->name = $name;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * @param string $description
   * @return $this
   */
  public function setDescription(string $description): self {
    $this->description = $description;

    return $this;
  }

  /**
   * @return DateTimeImmutable|null
   */
  public function getCreatedAt(): ?DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * @param DateTimeImmutable $createdAt
   * @return $this
   */
  public function setCreatedAt(DateTimeImmutable $createdAt): self {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * @return DateTimeImmutable|null
   */
  public function getModifiedAt(): ?DateTimeImmutable {
    return $this->modifiedAt;
  }

  /**
   * @param DateTimeImmutable $modifiedAt
   * @return $this
   */
  public function setModifiedAt(DateTimeImmutable $modifiedAt): self {
    $this->modifiedAt = $modifiedAt;

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
      $item->setFolder($this);
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
      if ($item->getFolder() === $this) {
        $item->setFolder(null);
      }
    }

    return $this;
  }

  /**
   * @return $this|null
   */
  public function getParent(): ?self {
    return $this->parent;
  }

  /**
   * @param Folder|null $parent
   * @return $this
   */
  public function setParent(?self $parent): self {
    $this->parent = $parent;

    return $this;
  }

  /**
   * @return Collection|self[]
   */
  public function getFolders(): Collection {
    return $this->folders;
  }

  /**
   * @param Folder $folder
   * @return $this
   */
  public function addFolder(self $folder): self {
    if (!$this->folders->contains($folder)) {
      $this->folders[] = $folder;
      $folder->setParent($this);
    }

    return $this;
  }

  /**
   * @param Folder $folder
   * @return $this
   */
  public function removeFolder(self $folder): self {
    if ($this->folders->removeElement($folder)) {
      // set the owning side to null (unless already changed)
      if ($folder->getParent() === $this) {
        $folder->setParent(null);
      }
    }

    return $this;
  }

  /**
   * @return Chest
   */
  public function getChest(): Chest {
    return $this->chest;
  }

  /**
   * @param Chest|null $chest
   * @return $this
   */
  public function setChest(?Chest $chest): self {
    $this->chest = $chest;

    return $this;
  }

  /**
   * @param User $user
   * @return bool
   */
  public function hasRightsOnParentFolder(User $user): bool {
    if ($this->parent === null)
      return false;

    return $user->hasRights($this->parent);
  }

  ////////////////// CUSTOM METHOD

  /**
   * @param $element
   * @return bool
   */
  public function contains($element): bool {
    if (is_a($element, Folder::class)) {
      return $this->folders->contains($element);
    } elseif (is_a($element, Item::class)) {
      return $this->items->contains($element);
    } else return false;
  }

  /**
   * @param array $folders
   * @return bool
   */
  public function hasParentsOnList(array $folders) {
    if ($this->parent !== null) {
      if (in_array($this->parent, $folders))
        return true;
      else
        return $this->parent->hasParentsOnList($folders);
    } else return false;
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
          $trash->setFolder($this);
      }

      return $this;
  }

  public function removeTrash(Trash $trash): self
  {
      if ($this->trashes->removeElement($trash)) {
          // set the owning side to null (unless already changed)
          if ($trash->getFolder() === $this) {
              $trash->setFolder(null);
          }
      }

      return $this;
  }
}
