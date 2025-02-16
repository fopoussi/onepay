<?php

namespace App\Controller;

use App\Entity\OnepayUser;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SecurityController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectGoogle(ClientRegistry $clientRegistry): JsonResponse
    {
        return $this->redirect($clientRegistry->getClient('google')->redirect([
            'profile', 'email' // Les scopes demandés
        ]));
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(Request $request, ClientRegistry $clientRegistry, EntityManagerInterface $entityManager, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        try {
            // Récupérer le client Google
            $client = $clientRegistry->getClient('google');
            $user = $client->fetchUser();

            // Rechercher l'utilisateur par son Google ID
            $existingUser = $entityManager->getRepository(OnepayUser::class)
                ->findOneBy(['googleId' => $user->getId()]);

            if (!$existingUser) {
                // Vérifier si un utilisateur existe déjà avec cet email
                $existingUser = $entityManager->getRepository(OnepayUser::class)
                    ->findOneBy(['email' => $user->getEmail()]);

                if (!$existingUser) {
                    // Créer un nouvel utilisateur
                    $existingUser = new OnepayUser();
                    $existingUser->setEmail($user->getEmail());
                    $existingUser->setName($user->getName());
                    $existingUser->setGoogleId($user->getId());
                    $existingUser->setAvatar($user->getAvatar());
                    $existingUser->setCreatedAt(new \DateTime());
                    $existingUser->setPhoneNumber(''); // À remplir plus tard par l'utilisateur
                    
                    $entityManager->persist($existingUser);
                } else {
                    // Mettre à jour les informations Google de l'utilisateur existant
                    $existingUser->setGoogleId($user->getId());
                    $existingUser->setAvatar($user->getAvatar());
                }
                
                $entityManager->flush();
            }

            // Générer le token JWT
            $token = $jwtManager->create($existingUser);

            return new JsonResponse([
                'token' => $token,
                'user' => [
                    'id' => $existingUser->getId(),
                    'email' => $existingUser->getEmail(),
                    'name' => $existingUser->getName(),
                    'avatar' => $existingUser->getAvatar()
                ]
            ]);

        } catch (\Exception $e) {
            throw new AuthenticationException('Échec de l\'authentification Google');
        }
    }
}
