<?php

namespace AppBundle\Entity;

use Algolia\AlgoliaSearchBundle\Mapping\Annotation as Algolia;
use AppBundle\Entity\Report\ReportableInterface;
use AppBundle\Geocoder\GeoPointInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *   name="events",
 *   uniqueConstraints={
 *     @ORM\UniqueConstraint(name="event_uuid_unique", columns="uuid"),
 *     @ORM\UniqueConstraint(name="event_slug_unique", columns="slug")
 *   }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *     "event" = "AppBundle\Entity\Event",
 *     "citizen_action" = "AppBundle\Entity\CitizenAction"
 * })
 *
 * @Algolia\Index
 */
abstract class BaseEvent implements GeoPointInterface, ReportableInterface
{
    const EVENT_TYPE = 'event';
    const CITIZEN_ACTION_TYPE = 'citizen_action';

    const STATUS_SCHEDULED = 'SCHEDULED';
    const STATUS_CANCELLED = 'CANCELLED';

    const STATUSES = [
        self::STATUS_SCHEDULED,
        self::STATUS_CANCELLED,
    ];

    const ACTIVE_STATUSES = [
        self::STATUS_SCHEDULED,
    ];

    use EntityIdentityTrait;
    use EntityCrudTrait;
    use EntityPostAddressTrait;
    use EntityTimestampableTrait;

    /**
     * @var string|null
     *
     * @ORM\Column(length=100)
     *
     * @Algolia\Attribute
     */
    protected $name;

    /**
     * The event canonical name.
     *
     * @var string|null
     *
     * @ORM\Column(length=100)
     *
     * @Algolia\Attribute
     */
    protected $canonicalName;

    /**
     * @var string|null
     *
     * @ORM\Column(length=130)
     * @Gedmo\Slug(fields={"beginAt", "canonicalName"}, dateFormat="Y-m-d")
     *
     * @Algolia\Attribute
     */
    protected $slug;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text")
     *
     * @Algolia\Attribute
     */
    protected $description;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime")
     */
    protected $beginAt;

    /**
     * @var \DateTimeInterface|null
     *
     * @ORM\Column(type="datetime")
     */
    protected $finishAt;

    /**
     * @var Adherent|null
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Adherent")
     * @ORM\JoinColumn(onDelete="RESTRICT")
     */
    protected $organizer;

    /**
     * @var int|null
     *
     * @ORM\Column(type="smallint", options={"unsigned": true})
     */
    protected $participantsCount;

    /**
     * @var string|null
     *
     * @ORM\Column(length=20)
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": true})
     */
    protected $published = true;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $capacity;

    /**
     * Mapping to be defined in child classes.
     *
     * @var BaseEventCategory|null
     */
    protected $category;

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    protected static function canonicalize(string $name)
    {
        return mb_strtolower($name);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getCategoryName(): string
    {
        return $this->category->getName();
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBeginAt(): \DateTimeInterface
    {
        return $this->beginAt;
    }

    public function getFinishAt(): \DateTimeInterface
    {
        return $this->finishAt;
    }

    public function getOrganizer(): ?Adherent
    {
        return $this->organizer;
    }

    public function getOrganizerName(): ?string
    {
        return $this->organizer ? $this->organizer->getFirstName() : '';
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getParticipantsCount(): int
    {
        return $this->participantsCount;
    }

    public function incrementParticipantsCount(int $increment = 1): void
    {
        $this->participantsCount += $increment;
    }

    public function decrementParticipantsCount(int $increment = 1): void
    {
        $this->participantsCount -= $increment;
    }

    public function updatePostAddress(PostAddress $postAddress): void
    {
        if (!$this->postAddress->equals($postAddress)) {
            $this->postAddress = $postAddress;
        }
    }

    protected function setName(string $name): void
    {
        $this->name = ucfirst($name);
        $this->canonicalName = static::canonicalize($name);
    }

    public function isFinished(): bool
    {
        // The production web server is configured with Europe/Paris timezone.
        // So if the event happens in France, then we can compare its ending
        // date and time with the current time.
        if ('FR' === $country = $this->getCountry()) {
            return $this->finishAt < new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        }

        // However, for an event taking place in another country in the world,
        // we need to know the timezone of this country. Some large countries
        // like the United States, Canada, Russia or Australia have multiple
        // timezones. Since we cannot accurately know the timezone of the event
        // taking place in a foreign country, the algorithm below will make the
        // following simple assumption.
        //
        // If there is at least one timezone for which the event is considered
        // not finished, then the method will return false. However, if the
        // event is finished in all timezones of this country, then the method
        // can return true.
        foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::PER_COUNTRY, $country) as $timezone) {
            $finishAt = new \DateTimeImmutable($this->finishAt->format('Y-m-d H:i'), $timezone = new \DateTimeZone($timezone));
            if (false === $finishAt < new \DateTime('now', $timezone)) {
                return false;
            }
        }

        return true;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new \InvalidArgumentException('Invalid status "%" given.', $status);
        }

        $this->status = $status;
    }

    public function publish(): void
    {
        $this->published = true;
    }

    public function cancel(): void
    {
        $this->status = self::STATUS_CANCELLED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isCancelled(): bool
    {
        return self::STATUS_CANCELLED === $this->status;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): void
    {
        $this->published = $published;
    }

    /**
     * @Algolia\Attribute(algoliaName="begin_at")
     */
    public function getReadableCreatedAt(): string
    {
        return $this->beginAt->format('d/m/Y H:i');
    }

    /**
     * @Algolia\Attribute(algoliaName="finish_at")
     */
    public function getReadableUpdatedAt(): string
    {
        return $this->finishAt->format('d/m/Y H:i');
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function isFull(): bool
    {
        if (!$this->capacity) {
            return false;
        }

        return $this->participantsCount >= $this->capacity;
    }

    abstract public function getType();

    public function isCitizenAction(): bool
    {
        return self::CITIZEN_ACTION_TYPE === $this->getType();
    }

    public function equals(self $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }
}
