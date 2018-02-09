<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Report\Report;
use AppBundle\Report\ReportManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/signalements")
 */
class AdminReportController extends Controller
{
    /**
     * @Route("/{id}/resolve", name="app_admin_report_resolve")
     * @Method("GET")
     * @Security("has_role('ROLE_APP_ADMIN_REPORT_APPROVE')")
     */
    public function resolveAction(Request $request, Report $report, ReportManager $reportManager): Response
    {
        if (!$this->isCsrfTokenValid(sprintf('report.%s', $report->getId()), $request->query->get('token'))) {
            throw new BadRequestHttpException('Invalid Csrf token provided.');
        }

        try {
            $reportManager->resolve($report);
            $this->addFlash('sonata_flash_success', sprintf('Le signalement « %s » a été résolu avec succès.', $report->getId()));
        } catch (\LogicException $e) {
            throw new BadRequestHttpException($e->getMessage(), $e);
        }

        return $this->redirectToRoute('admin_app_report_report_list');
    }
}
