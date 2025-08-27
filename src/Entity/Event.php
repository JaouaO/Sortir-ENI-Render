<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTime $startDateTime = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column]
    private ?\DateTime $registrationDeadline = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eventInfo = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?State $state = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Place $place = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'registeredEvents')]
    private Collection $registeredParticipants;

    #[ORM\ManyToOne(inversedBy: 'organizedEvents')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $organizer = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Site $site = null;

    public function __construct()
    {
        $this->registeredParticipants = new ArrayCollection();
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

    public function getStartDateTime(): ?\DateTime
    {
        return $this->startDateTime;
    }

    public function setStartDateTime(\DateTime $startDateTime): static
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getRegistrationDeadline(): ?\DateTime
    {
        return $this->registrationDeadline;
    }

    public function setRegistrationDeadline(\DateTime $registrationDeadline): static
    {
        $this->registrationDeadline = $registrationDeadline;

        return $this;
    }

    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getEventInfo(): ?string
    {
        return $this->eventInfo;
    }

    public function setEventInfo(?string $eventInfo): static
    {
        $this->eventInfo = $eventInfo;

        return $this;
    }

    public function getState(): ?State
    {
        return $this->state;
    }

    public function setState(?State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): static
    {
        $this->place = $place;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getRegisteredParticipants(): Collection
    {
        return $this->registeredParticipants;
    }

    public function addRegisteredParticipant(User $registeredParticipant): static
    {
        if (!$this->registeredParticipants->contains($registeredParticipant)) {
            $this->registeredParticipants->add($registeredParticipant);
            $registeredParticipant->addRegisteredEvent($this);
        }

        return $this;
    }

    public function removeRegisteredParticipant(User $registeredParticipant): static
    {
        if ($this->registeredParticipants->removeElement($registeredParticipant)) {
            $registeredParticipant->removeRegisteredEvent($this);
        }

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): static
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): static
    {
        $this->site = $site;

        return $this;
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        $now = new \DateTimeImmutable();

        if ($this->registrationDeadline >= $this->startDateTime) {
            $context->buildViolation('La date limite d’inscription doit être avant la date de l’événement.')
                ->atPath('registrationDeadline')
                ->addViolation();
        }

        if ($this->registrationDeadline <= $now) {
            $context->buildViolation('La date limite d’inscription doit être après aujourd’hui.')
                ->atPath('registrationDeadline')
                ->addViolation();
        }

        if($this->maxParticipants <1) {
            $context->buildViolation('Le nombre maximum de participant doit être supérieur à 0')
                ->atPath('maxParticipants')
                ->addViolation();
        }

        if($this->duration<2) {
            $context->buildViolation('La durée de la sortie doit être supérieur à 1mn')
                ->atPath('duration')
                ->addViolation();
        }
    }
}
