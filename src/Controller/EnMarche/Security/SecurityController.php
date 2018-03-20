<?php

namespace AppBundle\Controller\EnMarche\Security;

use AppBundle\Entity\Adherent;
use AppBundle\Entity\AdherentResetPasswordToken;
use AppBundle\Exception\AdherentTokenExpiredException;
use AppBundle\Form\AdherentResetPasswordType;
use AppBundle\Form\LoginType;
use AppBundle\Membership\MembershipRequestHandler;
use AppBundle\Repository\AdherentRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Constraints\NotBlank;

class SecurityController extends Controller
{
    /**
     * @Route("/connexion", name="app_user_login")
     * @Method("GET")
     */
    public function loginAction(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_search_events');
        }

        $securityUtils = $this->get('security.authentication_utils');

        $form = $this->get('form.factory')->createNamed('', LoginType::class, [
            '_login_email' => $securityUtils->getLastUsername(),
        ]);

        return $this->render('security/adherent_login.html.twig', [
            'form' => $form->createView(),
            'error' => $securityUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * @Route("/connexion/check", name="app_user_login_check")
     * @Method("POST")
     */
    public function loginCheckAction()
    {
    }

    /**
     * @Route("/deconnexion", name="logout")
     * @Method("GET")
     */
    public function logoutAction()
    {
    }

    /**
     * @Route("/mot-de-passe-oublie", name="forgot_password")
     * @Method("GET|POST")
     */
    public function retrieveForgotPasswordAction(Request $request)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_search_events');
        }

        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['constraints' => new NotBlank()])
            ->getForm()
            ->handleRequest($request)
        ;

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            if ($adherent = $this->getDoctrine()->getRepository(Adherent::class)->findOneByEmail($email)) {
                $this->get('app.adherent_reset_password_handler')->handle($adherent);
            }

            $this->addFlash('info', $this->get('translator')->trans('adherent.reset_password.email_sent'));

            return $this->redirectToRoute('app_user_login');
        }

        return $this->render('security/forgot_password.html.twig', [
            'legacy' => $request->query->getBoolean('legacy'),
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route(
     *   path="/changer-mot-de-passe/{adherent_uuid}/{reset_password_token}",
     *   name="adherent_reset_password",
     *   requirements={
     *     "adherent_uuid": "%pattern_uuid%",
     *     "reset_password_token": "%pattern_sha1%"
     *   }
     * )
     * @Method("GET|POST")
     * @Entity("adherent", expr="repository.findOneByUuid(adherent_uuid)")
     * @Entity("resetPasswordToken", expr="repository.findByToken(reset_password_token)")
     */
    public function resetPasswordAction(Request $request, Adherent $adherent, AdherentResetPasswordToken $resetPasswordToken)
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_search_events');
        }

        if ($resetPasswordToken->getUsageDate()) {
            throw $this->createNotFoundException('No available reset password token.');
        }

        $form = $this->createForm(AdherentResetPasswordType::class);

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();

            try {
                $this->get('app.adherent_reset_password_handler')->reset($adherent, $resetPasswordToken, $newPassword);
                $this->addFlash('info', $this->get('translator')->trans('adherent.reset_password.success'));

                return $this->redirectToRoute('app_user_profile');
            } catch (AdherentTokenExpiredException $e) {
                $this->addFlash('info', $this->get('translator')->trans('adherent.reset_password.expired_key'));
            }
        }

        return $this->render('security/adherent_reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/renvoyer-validation", name="adherent_resend_validation")
     * @Method("GET")
     */
    public function resendValidationEmailAction(MembershipRequestHandler $membershipRequestHandler, AuthenticationUtils $authenticationUtils, AdherentRepository $adherentRepository): Response
    {
        /** @var Adherent $adherent */
        $adherent = $adherentRepository->loadUserByUsername($authenticationUtils->getLastUsername());

        if ($adherent && !$adherent->isEnabled() && !$adherent->getActivatedAt()) {
            $membershipRequestHandler->sendEmailValidation($adherent);
            $this->addFlash('success', 'Un email de validation a bien été envoyé.');
        }

        return $this->redirectToRoute('app_user_login');
    }
}
