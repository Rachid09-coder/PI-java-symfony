<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/whoami', name: 'whoami')]
    public function whoami(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return new Response('NOT LOGGED');
        }

        return new Response(
            'EMAIL: '.$user->getUserIdentifier().
            '<br>ROLES: '.implode(',', $user->getRoles())
        );
    }
}
