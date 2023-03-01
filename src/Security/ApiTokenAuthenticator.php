<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\ApiTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private UserRepository $userRepository,private ApiTokenRepository $apiTokenRepository, private UrlGeneratorInterface $urlGenerator, private EntityManagerInterface $em)
    {
    }

    public function supports(Request $request): ?bool
    {
        return str_starts_with($request->getPathInfo(), '/api') &&  $request->isMethod('GET');
    }

    public function authenticate(Request $request): Passport
    {



        if ($request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ')) {
            $apiToken = substr($request->headers->get('Authorization'), 7);
            $token = $this->apiTokenRepository->findOneBy(['token' => $apiToken]);

            if ($token === null) {
                throw new CustomUserMessageAuthenticationException('Invalid token. send a POST request to /create_token  with email and password as form-data in the Body request if you need to create a new Token ');
            }
            if ($token->isExpired()) {
                throw new CustomUserMessageAuthenticationException('This Token has expired. send a POST request to /create_token  with email and password as form-data in the Body request to create a new Token ');
            }
        } else {
            throw new CustomUserMessageAuthenticationException('No API token provided. send a POST request to /create_token  with email and password as form-data  in the Body request if you need to create a new Token ');
        }




        return new SelfValidatingPassport(
            new UserBadge($apiToken, function($apiToken) {
                $user = $this->userRepository->findByApiToken($apiToken);

                if (!$user) {

                    throw new UserNotFoundException();
                }
                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }


}
