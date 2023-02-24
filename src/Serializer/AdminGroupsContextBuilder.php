<?php

namespace App\Serializer;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use App\Entity\CheeseListing;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AdminGroupsContextBuilder implements SerializerContextBuilderInterface
{



    public function __construct(private SerializerContextBuilderInterface $decorated, private AuthorizationCheckerInterface $authorizationChecker)
    {

    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        //$resourceClass = $context['resource_class'] ?? null;

        if (
           // $resourceClass === User::class &&
             isset($context['groups'])
            && $this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            $context['groups'][] = $normalization?'admin:read':'admin:write';
        }

        return $context;
    }
}