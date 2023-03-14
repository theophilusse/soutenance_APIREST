<?php

namespace App\Entity;

use App\Repository\TrajetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TrajetRepository::class)]
class Trajet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personne $conducteur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ville $depart = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Ville $destination = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToMany(targetEntity: Personne::class)]
    private Collection $passager;

    #[ORM\Column]
    private ?float $distance = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Voiture $voiture = null;

    public function __construct()
    {
        $this->passager = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlaces(): ?int
    {
        $voiture = $this->getVoiture();
        if ($voiture)
            return ($voiture->getPlaces());
        return (0);
    }

    public function getConducteur(): ?Personne
    {
        return $this->conducteur;
    }

    public function setConducteur(?Personne $conducteur): self
    {
        $this->conducteur = $conducteur;

        return $this;
    }

    public function getDepart(): ?Ville
    {
        return $this->depart;
    }

    public function setDepart(?Ville $depart): self
    {
        $this->depart = $depart;

        return $this;
    }

    public function getDestination(): ?Ville
    {
        return $this->destination;
    }

    public function setDestination(?Ville $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, Personne>
     */
    public function getPassager(): Collection
    {
        return $this->passager;
    }

    public function addPassager(Personne $passager)
    {
        if ($this->passager->contains($passager));
            return null;
        $this->passager->add($passager);
        return $this;
    }

    public function removePassager(Personne $passager): self
    {
        $this->passager->removeElement($passager);

        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getVoiture(): ?Voiture
    {
        return $this->voiture;
    }

    public function setVoiture(?Voiture $voiture): self
    {
        $this->voiture = $voiture;

        return $this;
    }
}
