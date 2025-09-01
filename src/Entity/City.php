<?php

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CityRepository::class)]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 5)]
    private ?string $postCode = null;

    /**
     * @var Collection<int, Place>
     */
    #[ORM\OneToMany(targetEntity: Place::class, mappedBy: 'city')]
    private Collection $place;

    public function __construct()
    {
        $this->place = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getPostCode(): ?string
    {
        return $this->postCode;
    }

    public function setPostCode(string $postCode): static
    {
        $this->postCode = $postCode;

        return $this;
    }

    /**
     * @return Collection<int, Place>
     */
    public function getPlace(): Collection
    {
        return $this->place;
    }

    public function addPlace(Place $place): static
    {
        if (!$this->place->contains($place)) {
            $this->place->add($place);
            $place->setCity($this);
        }

        return $this;
    }

    public function removePlace(Place $place): static
    {
        if ($this->place->removeElement($place)) {
            // set the owning side to null (unless already changed)
            if ($place->getCity() === $this) {
                $place->setCity(null);
            }
        }

        return $this;
    }
}
