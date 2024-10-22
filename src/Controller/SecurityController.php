<?php

namespace App\Controller;

use App\Entity\ApiToken;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends BaseController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser()) {
             return $this->redirectToRoute('app_dashboard');
         }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        $page['homeLink'] = '';
        $page['logoutLink'] = '';
        $page['username'] = '';
        $session['level'] = 1;

        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            #'page' => $page,
            #'session' => $session,
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

    #[Route(path: '/2fa-enable', name:'app_2fa_enable')]
    public function enable2fa(TotpAuthenticatorInterface $totpAuthenticator, EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        if (!$user->isTotpAuthenticationEnabled()) {
            $user->setTotpSecret($totpAuthenticator->generateSecret());
            $entityManager->flush();
        }

        dd($user);
    }

    /**
     * @Route("/authentication/2fa/qr-code", name="app_qr_code")
     */
    #[Route(path: '/2fa-qr-code', name:'app_2fa_disable')]
    public function displayGoogleAuthenticatorQrCode(QrCodeGenerator $qrCodeGenerator)
    {
        // $qrCode is provided by the endroid/qr-code library. See the docs how to customize the look of the QR code:
        // https://github.com/endroid/qr-code
        $qrCode = $qrCodeGenerator->getTotpQrCode($this->getUser());
        return new Response($qrCode->writeString(), 200, ['Content-Type' => 'image/png']);
    }
}
