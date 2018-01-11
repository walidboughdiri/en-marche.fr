<?php

namespace AppBundle\CitizenProject;

use AppBundle\Entity\Adherent;
use AppBundle\Mailer\MailerService;
use AppBundle\Mailer\Message\CitizenProjectApprovedSummaryMessage;
use AppBundle\Mailer\Message\Message;
use AppBundle\Repository\CitizenProjectRepository;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class CitizenProjectBroadcaster
{
    private const CITIZEN_PROJECTS_SUMMARY_LIMIT = 15;

    private $citizenProjectRepository;
    private $mailer;
    private $twig;
    private $logger;

    public function __construct(
        CitizenProjectRepository $citizenProjectRepository,
        MailerService $mailer,
        Environment $twig,
        LoggerInterface $logger
    ) {
        $this->citizenProjectRepository = $citizenProjectRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->logger = $logger;
    }

    public function broadcast(Adherent $adherent, ?string $approvedSince): void
    {
        // Finds all citizen projects near the Adherent
        $citizenProjects = $this->citizenProjectRepository->findNearByCitizenProjectsForAdherent(
            $adherent,
            self::CITIZEN_PROJECTS_SUMMARY_LIMIT,
            $approvedSince
        );

        if (!$citizenProjects) {
            return;
        }

        $this->mailer->sendMessage($this->createMessage($citizenProjects, $adherent));
    }

    private function createMessage(array $citizenProjects, Adherent $adherent): Message
    {
        $summary = $this->twig->render(
            'citizen_project/_email_summary.html.twig', [
                'citizen_projects' => $citizenProjects,
            ]
        );

        return CitizenProjectApprovedSummaryMessage::create(
            $adherent,
            $summary
        );
    }
}
