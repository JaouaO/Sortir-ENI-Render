<?php

namespace App\Entity;

use App\Repository\StateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StateRepository::class)]
class State
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $description = null;

    /**
     * @var Collection<int, event>
     */
    #[ORM\OneToMany(targetEntity: event::class, mappedBy: 'state')]
    private Collection $event;

    public function __construct()
    {
        $this->event = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, event>
     */
    public function getEvent(): Collection
    {
        return $this->event;
    }

    public function addEvent(event $event): static
    {
        if (!$this->event->contains($event)) {
            $this->event->add($event);
            $event->setState($this);
        }

        return $this;
    }

    public function removeEvent(event $event): static
    {
        if ($this->event->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getState() === $this) {
                $event->setState(null);
            }
        }

        return $this;
    }
}
