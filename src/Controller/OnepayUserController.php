<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/api')]
class OnepayUserController extends AbstractController
{
    #[Route('/login', name: 'app_login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        // Cette méthode reste vide car le JWT bundle gère l'authentification
        // Le JWT sera automatiquement généré si les identifiants sont corrects
        return $this->json([
            'message' => 'Connexion réussie'
        ]);
    }

    #[Route('/logout', name: 'app_logout', methods: ['POST'])]
    public function logout(): Response
    {
        // Cette méthode peut rester vide car la déconnexion est gérée côté client
        // en supprimant le token JWT
        return $this->json([
            'message' => 'Déconnexion réussie'
        ]);
    }

    #[Route('/user/profile', name: 'app_user_profile', methods: ['GET'])]
    public function profile(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non authentifié'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                // Ajoutez d'autres champs selon vos besoins
            ]
        ]);
    }
} 