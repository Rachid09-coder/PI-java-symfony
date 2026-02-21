<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\AuthenticationManager;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class GoogleAuthController extends AbstractController
{
    #[Route('/auth/google', name: 'app_google_login', methods: ['GET'])]
    public function googleLogin(
        SessionInterface $session,
        ParameterBagInterface $params
    ): Response {
        try {
            $clientId = $params->get('oauth_google_client_id');
            $clientSecret = $params->get('oauth_google_client_secret');
            $redirectUri = $params->get('oauth_google_callback_url');

            // Debug: Check if credentials exist
            if (!$clientId) {
                throw new \Exception('OAUTH_GOOGLE_CLIENT_ID is not set in .env or .env.local');
            }
            if (!$clientSecret) {
                throw new \Exception('OAUTH_GOOGLE_CLIENT_SECRET is not set in .env or .env.local');
            }
            if (!$redirectUri) {
                throw new \Exception('OAUTH_GOOGLE_CALLBACK_URL is not set in .env or .env.local');
            }

            $provider = new Google([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri' => $redirectUri,
            ]);

            $authorizationUrl = $provider->getAuthorizationUrl([
                'scope' => ['openid', 'email', 'profile']
            ]);

            // Store state in session for verification
            $session->set('oauth2state', $provider->getState());

            return $this->redirect($authorizationUrl);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Google OAuth Error: ' . $e->getMessage());
            return $this->redirectToRoute('app_auth_login');
        }
    }

    #[Route('/auth/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function googleCallback(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        ParameterBagInterface $params,
        EventDispatcherInterface $eventDispatcher
    ): Response {
        $state = $request->query->get('state');
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        // Check for errors from Google
        if ($error) {
            $this->addFlash('danger', 'Authentification Google échouée: ' . $error);
            return $this->redirectToRoute('app_auth_login');
        }

        // Verify state for security
        $storedState = $session->get('oauth2state');
        if ($storedState !== $state || !$state) {
            $session->remove('oauth2state');
            $this->addFlash('danger', 'État OAuth invalide');
            return $this->redirectToRoute('app_auth_login');
        }

        try {
            $clientId = $params->get('oauth_google_client_id');
            $clientSecret = $params->get('oauth_google_client_secret');
            $redirectUri = $params->get('oauth_google_callback_url');

            if (!$clientId || !$clientSecret || !$redirectUri) {
                $this->addFlash('danger', 'Google OAuth credentials are not configured.');
                return $this->redirectToRoute('app_auth_login');
            }

            $provider = new Google([
                'clientId' => $clientId,
                'clientSecret' => $clientSecret,
                'redirectUri' => $redirectUri,
            ]);

            // Get access token
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);

            // Get resource owner (user info)
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $googleUser = $resourceOwner->toArray();

            // Find or create user
            $user = $userRepository->findOneBy(['email' => $googleUser['email']]);

            if (!$user) {
                // Create new user
                $user = new User();
                $user->setEmail($googleUser['email']);
                
                // Parse name into first and last name
                $fullName = $googleUser['name'] ?? 'OAuth User';
                $nameParts = explode(' ', $fullName, 2);
                $user->setName($nameParts[0] ?? 'User');
                $user->setPrenom($nameParts[1] ?? '');
                
                $user->setGoogleId($googleUser['sub']); // Google's unique user ID
                $user->setPassword('google_oauth'); // Mark as OAuth user
                $user->setRole('etudiant'); // Default role
                $user->setNumtel('0000000000'); // Placeholder phone number
                $user->setIsActive(true);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Compte créé avec succès via Google!');
            } else {
                // Update Google ID if not already set
                if (!$user->getGoogleId()) {
                    $user->setGoogleId($googleUser['sub']);
                    $em->flush();
                }
            }

            // Create token using Symfony signature: (user, firewallName, roles)
            $token = new UsernamePasswordToken(
                $user,
                'main',
                $user->getRoles()
            );

            // Set token in security context
            $this->container->get('security.token_storage')->setToken($token);

            // Store token in session under the firewall key and ensure it's saved
            $session->set('_security_main', serialize($token));
            $session->save();

            // Dispatch interactive login event so listeners (if any) run
            $event = new InteractiveLoginEvent($request, $token);
            $eventDispatcher->dispatch($event);

            $this->addFlash('success', 'Connexion réussie!');

            return $this->redirectToRoute('app_redirect_user');

        } catch (\Exception $e) {
            error_log('Google OAuth Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            $this->addFlash('danger', 'Erreur: ' . substr($e->getMessage(), 0, 100));
            return $this->redirectToRoute('app_auth_login');
        }
    }
}
