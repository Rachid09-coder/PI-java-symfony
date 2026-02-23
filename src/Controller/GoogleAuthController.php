<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\AuthenticationManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GoogleAuthController extends AbstractController
{
    #[Route('/auth/google', name: 'app_google_login', methods: ['GET'])]
    public function googleLogin(
        Request $request,
        SessionInterface $session,
        ParameterBagInterface $params
    ): Response {
        try {
            $clientId = $params->get('oauth_google_client_id');
            $clientSecret = $params->get('oauth_google_client_secret');
            $redirectUri = $params->get('oauth_google_callback_url');

            if (!$clientId || !$clientSecret) {
                $this->addFlash('warning', 'Connexion Google non configurée. Utilisez email et mot de passe, ou ajoutez OAUTH_GOOGLE_CLIENT_ID et OAUTH_GOOGLE_CLIENT_SECRET dans .env ou .env.local (voir GOOGLE_OAUTH_SETUP.md).');
                return $this->redirectToRoute('app_login');
            }

            if (!$redirectUri) {
                $redirectUri = $request->getSchemeAndHttpHost() . '/auth/google/callback';
            }

            $provider = new Google([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri' => $redirectUri,
            ]);

            // Store state in session for verification
            $authorizationUrl = $provider->getAuthorizationUrl([
                'scope' => ['openid', 'email', 'profile']
            ]);

            $session->set('oauth2state', $provider->getState());
            $session->save();

            return $this->redirect($authorizationUrl);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }

    #[Route('/auth/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function googleCallback(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        EventDispatcherInterface $eventDispatcher,
        UserPasswordHasherInterface $passwordHasher,
        TokenStorageInterface $tokenStorage
    ): Response {
        $state = $request->query->get('state');
        // Check for errors from Google
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        if ($error) {
            $this->addFlash('danger', 'Authentification Google échouée: ' . $error);
            return $this->redirectToRoute('app_login');
        }

        if (!$code) {
            $this->addFlash('danger', 'Code d\'autorisation manquant. Réessayez en cliquant sur « Google ».');
            return $this->redirectToRoute('app_login');
        }

        $storedState = $session->get('oauth2state');
        if ($storedState !== $state || !$state) {
            $session->remove('oauth2state');
            $this->addFlash('danger', 'Session expirée ou lien invalide. Réessayez en cliquant sur « Google ».');
            return $this->redirectToRoute('app_login');
        }
        $session->remove('oauth2state');

        try {
            $clientId = $params->get('oauth_google_client_id');
            $clientSecret = $params->get('oauth_google_client_secret');
            $redirectUri = $params->get('oauth_google_callback_url') ?: $request->getSchemeAndHttpHost() . '/auth/google/callback';

            if (!$clientId || !$clientSecret) {
                $this->addFlash('danger', 'Connexion Google non configurée.');
                return $this->redirectToRoute('app_login');
            }

            $provider = new Google([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri' => $redirectUri,
            ]);

            $googleUser = $resourceOwner->toArray();
            $accessToken = $provider->getAccessToken('authorization_code', ['code' => $code]);
            $resourceOwner = $provider->getResourceOwner($accessToken);

            $email = $resourceOwner->getEmail();
            $googleId = $resourceOwner->getId();
            $fullName = $resourceOwner->getName() ?? 'OAuth User';

                // Create new user
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $nameParts = explode(' ', $fullName, 2);
                $user->setName($nameParts[0] ?? 'User');
                $user->setPrenom($nameParts[1] ?? '');
                $user->setGoogleId($googleId);
                $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                $user->setRole('etudiant');
                $user->setNumtel('0000000000');
                $user->setIsActive(true);
                // Update Google ID if not already set

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès via Google!');
            } else {
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleId);
                    $em->flush();
                }
            // Dispatch interactive login event so listeners (if any) run
            }

            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $tokenStorage->setToken($token);
            $session->set('_security_main', serialize($token));
            $session->save();

            $event = new InteractiveLoginEvent($request, $token);
            $eventDispatcher->dispatch($event);

            $this->addFlash('success', 'Connexion réussie!');

            return $this->redirectToRoute('app_redirect_user');

        } catch (\Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            $msg = $e->getMessage();
            if (str_contains($msg, 'redirect_uri_mismatch') || str_contains($msg, 'redirect_uri')) {
                $callbackUrl = $params->get('oauth_google_callback_url') ?: $request->getSchemeAndHttpHost() . '/auth/google/callback';
                $this->addFlash('danger', 'URL de redirection incorrecte. Dans Google Cloud Console → Credentials → votre client OAuth, ajoutez dans "Authorized redirect URIs" exactement : ' . $callbackUrl);
            } else {
                $this->addFlash('danger', 'Erreur: ' . substr($msg, 0, 120));
            }
            return $this->redirectToRoute('app_login');
        }
    }
}
