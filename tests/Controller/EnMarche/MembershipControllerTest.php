<?php

namespace Tests\AppBundle\Controller\EnMarche;

use AppBundle\DataFixtures\ORM\LoadAdherentData;
use AppBundle\DataFixtures\ORM\LoadHomeBlockData;
use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentActivationToken;
use AppBundle\Mailer\Message\AdherentAccountActivationMessage;
use AppBundle\Membership\AdherentEmailSubscription;
use AppBundle\Repository\AdherentActivationTokenRepository;
use AppBundle\Repository\AdherentRepository;
use AppBundle\Repository\EmailRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\AppBundle\Controller\ControllerTestTrait;
use Tests\AppBundle\MysqlWebTestCase;

/**
 * @group functional
 * @group membership
 */
class MembershipControllerTest extends MysqlWebTestCase
{
    use ControllerTestTrait;

    /**
     * @var AdherentRepository
     */
    private $adherentRepository;

    /**
     * @var AdherentActivationTokenRepository
     */
    private $activationTokenRepository;

    /**
     * @var EmailRepository
     */
    private $emailRepository;

    /**
     * @dataProvider provideEmailAddress
     */
    public function testCannotCreateMembershipAccountWithSomeoneElseEmailAddress($emailAddress): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/inscription');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $data = static::createFormData();
        $data['user_registration']['emailAddress']['first'] = $emailAddress;
        $data['user_registration']['emailAddress']['second'] = $emailAddress;
        $crawler = $this->client->submit($crawler->selectButton('Créer mon compte')->form(), $data);

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertValidationErrors(['data.emailAddress'], $this->client->getContainer());
        $errors = $crawler->filter('.form__error');
        $this->assertSame('Cette adresse e-mail existe déjà.', $errors->text());
    }

    /**
     * These data come from the LoadAdherentData fixtures file.
     *
     * @see LoadAdherentData
     */
    public function provideEmailAddress(): array
    {
        return [
            ['michelle.dufour@example.ch'],
            ['carl999@example.fr'],
        ];
    }

    public function testCreateMembershipAccountForFrenchAdherentIsSuccessful(): void
    {
        $this->client->request(Request::METHOD_GET, '/inscription');

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        $this->client->submit($this->client->getCrawler()->selectButton('Créer mon compte')->form(), static::createFormData());

        $this->assertClientIsRedirectedTo('/presque-fini', $this->client);

        $adherent = $this->getAdherentRepository()->findOneByEmail('jean-paul@dupont.tld');
        $this->assertInstanceOf(Adherent::class, $adherent);
        $this->assertNull($adherent->getGender());
        $this->assertSame('Jean-Paul', $adherent->getFirstName());
        $this->assertSame('Dupont', $adherent->getLastName());
        $this->assertEmpty($adherent->getAddress());
        $this->assertEmpty($adherent->getCityName());
        $this->assertSame('FR', $adherent->getCountry());
        $this->assertNull($adherent->getBirthdate());
        $this->assertFalse($adherent->getComMobile());
        $this->assertNull($adherent->getLatitude());
        $this->assertNull($adherent->getLongitude());
        $this->assertNull($adherent->getPosition());
        $this->assertTrue($adherent->hasSubscribedLocalHostEmails());
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_MOVEMENT_INFORMATION));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_GOVERNMENT_INFORMATION));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_WEEKLY_LETTER));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_MICROLEARNING));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_MOOC));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_DONATOR_INFORMATION));
        $this->assertTrue($adherent->hasEmailSubscription(AdherentEmailSubscription::SUBSCRIBED_EMAILS_REFERENTS));
        $this->assertTrue($adherent->hasCitizenProjectCreationEmailSubscription());

        /** @var Adherent $adherent */
        $this->assertInstanceOf(
            Adherent::class,
            $adherent = $this->client->getContainer()->get('doctrine')->getRepository(Adherent::class)->findOneByEmail('jean-paul@dupont.tld')
        );
        $this->assertSame('Jean-Paul', $adherent->getFirstName());
        $this->assertSame('Dupont', $adherent->getLastName());
        $this->assertInstanceOf(AdherentActivationToken::class, $activationToken = $this->activationTokenRepository->findAdherentMostRecentKey((string) $adherent->getUuid()));
        $this->assertCount(1, $this->emailRepository->findRecipientMessages(AdherentAccountActivationMessage::class, 'paul@dupont.tld'));

        // Activate the user account
        $activateAccountUrl = sprintf('/inscription/finaliser/%s/%s', $adherent->getUuid(), $activationToken->getValue());
        $this->client->request(Request::METHOD_GET, $activateAccountUrl);

        $this->assertResponseStatusCode(Response::HTTP_FOUND, $this->client->getResponse());
        $this->assertClientIsRedirectedTo('/adhesion', $this->client);

        $this->client->followRedirect();

        // User is automatically logged-in
        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());

        // Activate user account twice
        $this->logout($this->client);
        $this->client->request(Request::METHOD_GET, $activateAccountUrl);

        $this->assertClientIsRedirectedTo('/connexion', $this->client);

        $crawler = $this->client->followRedirect();

        $this->assertResponseStatusCode(Response::HTTP_OK, $this->client->getResponse());
        $this->assertContains('Votre compte est déjà actif.', $crawler->filter('.flash')->text());

        // Try to authenticate with credentials
        $this->client->submit($crawler->selectButton('Connexion')->form([
            '_login_email' => 'jean-paul@dupont.tld',
            '_login_password' => LoadAdherentData::DEFAULT_PASSWORD,
        ]));

        $this->assertClientIsRedirectedTo('http://'.$this->hosts['app'].'/evenements', $this->client);

        $this->client->followRedirect();
    }

    private static function createFormData(): array
    {
        return [
            'g-recaptcha-response' => 'dummy',
            'user_registration' => [
                'firstName' => 'jean-pauL',
                'lastName' => 'duPont',
                'emailAddress' => [
                    'first' => 'jean-paul@dupont.tld',
                    'second' => 'jean-paul@dupont.tld',
                ],
                'password' => LoadAdherentData::DEFAULT_PASSWORD,
                'address' => [
                    'country' => 'FR',
                    'postalCode' => '92110',
                ],
                'comEmail' => true,
            ],
        ];
    }

    protected function setUp()
    {
        parent::setUp();

        $this->init([
            LoadAdherentData::class,
            LoadHomeBlockData::class,
        ]);

        $this->adherentRepository = $this->getAdherentRepository();
        $this->activationTokenRepository = $this->getActivationTokenRepository();
        $this->emailRepository = $this->getEmailRepository();
    }

    protected function tearDown()
    {
        $this->kill();

        $this->emailRepository = null;
        $this->activationTokenRepository = null;
        $this->adherentRepository = null;

        parent::tearDown();
    }
}
