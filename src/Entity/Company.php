<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CompanyRepository")
 * @ORM\HasLifecycleCallbacks()
 * @Vich\Uploadable
 * @UniqueEntity("siret", message="Siret déjà prise")
 */
class Company implements \Serializable
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=191, unique=true)
     */
    private $siret;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255)
     */
    private $email;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", nullable=true)
     */
    private $phone;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", nullable=true)
     */
    private $addressNumber;

    /**
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", nullable=true)
     */
    private $addressStreet;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $addressPostCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $city;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $country;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $filename;

    /**
     * @var File|null
     * @Vich\UploadableField(mapping="company_image", fileNameProperty="filename")
     */
    private $imageFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $video;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $website;

    /**
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $latitude;

    /**
     * @ORM\Column(type="decimal", precision=11, scale=8, nullable=true)
     */
    private $longitude;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isValid;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\User", mappedBy="company")
     */
    private $users;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Store", inversedBy="companies")
     * @ORM\JoinColumn(nullable=false)
     */
    private $store;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CompanyCategory")
     */
    private $category;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $slug;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */

    private $introduction;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCompleted;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Service")
     */
    private $services;

    /**
     * @ORM\Column(type="text")
     */
    private $barCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $otherCategory;

    /**
     * @ORM\OneToMany(targetEntity=Subscription::class, mappedBy="company")
     */
    private $subscriptions;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->isValid = false;
        $this->isCompleted = false;
        $this->updatedAt = new \Datetime();
        $this->services = new ArrayCollection();
        $this->subscriptions = new ArrayCollection();
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function initSlug()
    {
        if (empty($this->getSlug() )){
            $slugify = new Slugify();
            $slug = $slugify->slugify($this->getName());
            $this->setSlug($slug);
        }
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(string $siret): self
    {
        $this->siret = $siret;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(?int $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAddressNumber(): ?string
    {
        return $this->addressNumber;
    }

    public function setAddressNumber(string $addressNumber): self
    {
        $this->addressNumber = $addressNumber;

        return $this;
    }

    public function getAddressStreet(): ?string
    {
        return $this->addressStreet;
    }

    public function setAddresseStreet(string $addressStreet): self
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    public function getAddressePostCode(): ?int
    {
        return $this->addressPostCode;
    }

    public function setAddressPostCode(int $addressPostCode): self
    {
        $this->addressPostCode = $addressPostCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param null|string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * @param null|File $imageFile
     * @return $this
     */
    public function setImageFile(?File $imageFile)
    {
        $this->imageFile = $imageFile;

        if ($this->imageFile instanceof UploadedFile) {
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function setVideo(?string $video): self
    {
        $this->video = $video;

        return $this;
    }

    public function setAddressStreet(string $addressStreet): self
    {
        $this->addressStreet = $addressStreet;

        return $this;
    }

    public function getAddressPostCode(): ?int
    {
        return $this->addressPostCode;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription():  ?string
    {
        return $this->description;

    }

    public function setDescription(?string $description): self
    {
        $this->description =str_replace("\r\n",'<br>', $description);


        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): self
    {
        $this->website = $website;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(?string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(?string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function isValid(): ?bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setCompany($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            // set the owning side to null (unless already changed)
            if ($user->getCompany() === $this) {
                $user->setCompany(null);
            }
        }

        return $this;
    }

    public function getStore(): ?Store
    {
        return $this->store;
    }

    public function setStore(?Store $store): self
    {
        $this->store = $store;

        return $this;
    }

    public function getCategory(): ?CompanyCategory
    {
        return $this->category;
    }

    public function setCategory(?CompanyCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(string $introduction): self
    {
        $this->introduction = $introduction;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getIsCompleted(): ?bool
    {
        return $this->isCompleted;
    }

    public function setIsCompleted(bool $isCompleted): self
    {
        $this->isCompleted = $isCompleted;

        return $this;
    }
    public function __toString()
    {
       return strval( $this->getName() );
    }

    /**
     * @return Collection|Service[]
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services[] = $service;
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        if ($this->services->contains($service)) {
            $this->services->removeElement($service);
        }

        return $this;
    }

    public function getBarCode(): ?string
    {
        return $this->barCode;
    }

    public function setBarCode(string $barCode): self
    {
        $this->barCode = $barCode;

        return $this;
    }

    public function getOtherCategory(): ?string
    {
        return $this->otherCategory;
    }

    public function setOtherCategory(?string $otherCategory): self
    {
        $this->otherCategory = $otherCategory;

        return $this;
    }

    public function serialize()
    {
        return serialize($this->id);
    }

    public function unserialize($serialized)
    {
        $this->id = unserialize($serialized);

    }

    /**
     * @return Collection|Subscription[]
     */
    public function getSubscriptions(): Collection
    {
        return $this->subscriptions;
    }

    public function addSubscription(Subscription $subscription): self
    {
        if (!$this->subscriptions->contains($subscription)) {
            $this->subscriptions[] = $subscription;
            $subscription->setCompany($this);
        }

        return $this;
    }

    public function removeSubscription(Subscription $subscription): self
    {
        if ($this->subscriptions->contains($subscription)) {
            $this->subscriptions->removeElement($subscription);
            // set the owning side to null (unless already changed)
            if ($subscription->getCompany() === $this) {
                $subscription->setCompany(null);
            }
        }

        return $this;
    }
    
}
