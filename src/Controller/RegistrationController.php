<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\SiteOwnerManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

// Fred note: J'ouvre ici un vrai point d'entree client pour sortir d'un parcours uniquement guest.
final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier,
        SiteOwnerManager $siteOwnerManager,
        string $mailerDsn,
        string $mailerFromAddress,
    ): Response {
        if ($this->getUser() instanceof User) {
            return $this->redirectToRoute('app_account');
        }

        $draftUser = new User();
        $form = $this->createForm(RegistrationType::class, $draftUser);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ('' !== trim((string) $form->get('website')->getData())) {
                $form->addError(new FormError('La validation du formulaire a échoué.'));
            }

            $firstName = trim((string) $form->get('firstName')->getData());
            $lastName = trim((string) $form->get('lastName')->getData());
            $fullName = trim($firstName.' '.$lastName);

            if ($fullName !== '') {
                $draftUser->setFullName($fullName);
            }

            if ($draftUser->getEmail() && $userRepository->findOneBy(['email' => $draftUser->getEmail()]) instanceof User) {
                $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse email.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $draftUser
                ->setEmail((string) $draftUser->getEmail())
                ->setFullName($draftUser->getFullName() ?: 'Fan Sound Of Memories')
                ->setPhone($draftUser->getPhone())
                ->setCountryCode($draftUser->getCountryCode())
                ->setDefaultAddress($draftUser->getDefaultAddress())
                ->setAddressBuilding($draftUser->getAddressBuilding())
                ->setAddressExtra($draftUser->getAddressExtra())
                ->setPostalCode($draftUser->getPostalCode())
                ->setCity($draftUser->getCity())
                ->setRoles([])
                ->setIsVerified(false);

            $entityManager->persist($user);
            $user->setPassword($passwordHasher->hashPassword($user, (string) $draftUser->getPlainPassword()));
            $user->eraseCredentials();
            $entityManager->flush();

            $emailVerifier->sendEmailConfirmation(
                'app_verify_email',
                $user,
                (new TemplatedEmail())
                    ->from(new Address($mailerFromAddress, $siteOwnerManager->getOwnerName()))
                    ->replyTo(new Address($siteOwnerManager->getOwnerEmail(), $siteOwnerManager->getOwnerName()))
                    ->to((string) $user->getEmail())
                    ->subject('Confirmez votre adresse email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context([
                        'user' => $user,
                    ])
            );

            if (str_starts_with($mailerDsn, 'null://')) {
                $this->addFlash('warning', 'Le compte fan a été créé. L’email de vérification sera disponible dès que la messagerie du site sera activée.');
            } elseif (str_contains($mailerDsn, 'dev-sendmail.php')) {
                $this->addFlash('success', 'Le compte fan a été créé. L’email de vérification est maintenant disponible dans la boîte mail locale.');
            } else {
                $this->addFlash('success', 'Le compte fan a été créé. Un email de vérification vient d’être envoyé.');
            }

            return $this->redirectToRoute('app_login', [
                'prefill_email' => $user->getEmail(),
            ]);
        }

        return $this->render('security/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/verification/email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyUserEmail(
        Request $request,
        UserRepository $userRepository,
        EmailVerifier $emailVerifier,
        TranslatorInterface $translator,
    ): Response {
        $id = $request->query->get('id');

        if (!\is_string($id) || !ctype_digit($id)) {
            $this->addFlash('error', 'Le lien de vérification est invalide.');

            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->find((int) $id);

        if (!$user instanceof User) {
            $this->addFlash('error', 'Le compte demandé est introuvable.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_login', [
                'prefill_email' => $user->getEmail(),
            ]);
        }

        $this->addFlash('success', 'Adresse email confirmée. Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login', [
            'prefill_email' => $user->getEmail(),
        ]);
    }
}
