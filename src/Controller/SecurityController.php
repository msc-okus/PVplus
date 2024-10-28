<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Email\Generator\CodeGeneratorInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, CodeGeneratorInterface $codeGenerator): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('app_dashboard');
         }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }


    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }


    #[Route('/create_token', name:'api_create_token')]
    public function createToken(ApiTokenRepository $apiTokenRepository, UserRepository $userRepository): Response
    {
        if($this->getUser()){
            $user= $userRepository->findOneBy(['email'=>$this->getUser()->getUserIdentifier()]);
            $token = new ApiToken($user) ;
            $apiTokenRepository->save($token,true);

            return $this->json($token,200,[],['groups'=>['token:read']]);
        }

        return $this->json(["token"=>null]);
    }

    #[Route('/api_redirection', name:'api_redirection')]
    public function api_redirection(ApiTokenRepository $apiTokenRepository, UserRepository $userRepository,Security $security): ?Response
    {
        /*if($security->isGranted('ROLE_ADMIN')){
            return null;
        }*/

        return null;
    }

    /**
     * Enables the 2fa and redirects to form with QR code to set up Autenticator app
     * if you click 'cancel' 2fa is NOT enabled
     *
     * @param Request $request
     * @param TotpAuthenticatorInterface $totpAuthenticator
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    #[Route(path: '/autentication/2fa/enable', name:'app_2fa_enable')]
    public function enable2fa(Request $request, TotpAuthenticatorInterface $totpAuthenticator, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user->isTotpAuthenticationEnabled()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
            $user->setUse2fa(true);
            $entityManager->flush();
        }

        if ($request->request->get('cancel') === 'cancel'){
            $user->setUse2fa(false);
            $entityManager->flush();

            return $this->redirectToRoute('app_dashboard');
        }

        return $this->render('login/2fa_enable.html.twig');
    }

    /**
     * generates the QR code as an image. Is used to show the QR code in twig layout
     *
     * @param TotpAuthenticatorInterface $totpAuthenticator
     * @return Response
     */
    #[Route(path: '/autentication/2fa/qr-code', name:'app_2fa_qrcode')]
    #[IsGranted('ROLE_USER')]
    public function displayGoogleAuthenticatorQrCode(TotpAuthenticatorInterface $totpAuthenticator): Response
    {
        $qrCodeContent = $totpAuthenticator->getQRContent($this->getUser());

        $writer = new PngWriter();
        $qrCode = new QrCode($qrCodeContent);
        $result = $writer->write($qrCode);

        return new Response($result->getString(), 200, ['Content-Type' => 'image/png']);
    }
}
