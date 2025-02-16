<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request, 
        ?MailerInterface $mailer = null,
        ?LoggerInterface $logger = null
    ): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($mailer) {
                try {
                    $email = (new Email())
                        ->from($data['email'])
                        ->to('contact@onepay.com')
                        ->subject('Nouveau message de contact - OnePay')
                        ->html(
                            $this->renderView(
                                'emails/contact.html.twig',
                                [
                                    'name' => $data['name'],
                                    'email' => $data['email'],
                                    'message' => $data['message']
                                ]
                            )
                        );

                    $mailer->send($email);
                    $this->addFlash('success', 'Votre message a été envoyé avec succès !');
                    
                    if ($logger) {
                        $logger->info('Email de contact envoyé', [
                            'from' => $data['email'],
                            'name' => $data['name']
                        ]);
                    }
                } catch (\Exception $e) {
                    if ($logger) {
                        $logger->error('Erreur lors de l\'envoi de l\'email de contact', [
                            'error' => $e->getMessage(),
                            'from' => $data['email']
                        ]);
                    }
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message.');
                }
            } else {
                // Si le mailer n'est pas configuré, on affiche quand même un message de succès
                $this->addFlash('success', 'Votre message a été reçu !');
                if ($logger) {
                    $logger->warning('Tentative d\'envoi d\'email sans configuration de mailer');
                }
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('home/index.html.twig', [
            'contact_form' => $form->createView()
        ]);
    }
}
