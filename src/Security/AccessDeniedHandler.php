<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?Response
    {
        return new JsonResponse([
            '@context' => '/api/contexts/Error',
            '@type' => 'Error',
            'hydra:title' => 'Access Denied',
            'hydra:description' => 'Vous n\'avez pas les permissions nécessaires pour accéder à cette ressource.',
            'status' => Response::HTTP_FORBIDDEN,
            'message' => $accessDeniedException->getMessage()
        ], Response::HTTP_FORBIDDEN, [
            'Content-Type' => 'application/ld+json',
        ]);
    }
} 