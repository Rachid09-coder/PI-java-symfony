<?php

namespace App\Controller;

use App\Service\FaceRecognitionService;
use App\Security\FormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FaceAuthController extends AbstractController
{
    private EntityManagerInterface $em;
    private FaceRecognitionService $faceService;
    private CsrfTokenManagerInterface $csrfManager;
    private UserAuthenticatorInterface $userAuthenticator;
    private FormAuthenticator $formAuthenticator;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        EntityManagerInterface $em,
        FaceRecognitionService $faceService,
        CsrfTokenManagerInterface $csrfManager,
        UserAuthenticatorInterface $userAuthenticator,
        FormAuthenticator $formAuthenticator,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->em = $em;
        $this->faceService = $faceService;
        $this->csrfManager = $csrfManager;
        $this->userAuthenticator = $userAuthenticator;
        $this->formAuthenticator = $formAuthenticator;
        $this->urlGenerator = $urlGenerator;
    }

    #[Route('/face/register', name: 'face_register_page', methods: ['GET'])]
    public function showRegisterPage(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        return $this->render('security/face_register.html.twig', [
            'csrf_token' => $this->csrfManager->getToken('face_register')->getValue(),
        ]);
    }

    #[Route('/face/register', name: 'face_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        // Allow storing a face descriptor either on the logged-in user
        // or temporarily in session for users who are registering.
        $content = json_decode($request->getContent(), true);
        $token = $content['_csrf_token'] ?? $request->request->get('_csrf_token');
        if (!$this->csrfManager->isTokenValid(new CsrfToken('face_register', (string)$token))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $descriptor = $content['descriptor'] ?? null;
        if (!is_array($descriptor) || count($descriptor) < 1) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid descriptor'], 400);
        }

        $user = $this->getUser();
        if ($user) {
            $user->setFaceDescriptor($descriptor);
            $this->em->persist($user);
            $this->em->flush();
            return new JsonResponse(['success' => true]);
        }

        // Not authenticated: store descriptor in session for later association
        $session = $request->getSession();
        $session->set('face_descriptor_pending', $descriptor);
        return new JsonResponse(['success' => true, 'message' => 'Stored in session']);
    }

    #[Route('/face/login', name: 'face_login_page', methods: ['GET'])]
    public function showLoginPage(): Response
    {
        return $this->render('security/face_login.html.twig', [
            'csrf_token' => $this->csrfManager->getToken('face_login')->getValue(),
        ]);
    }

    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $token = $content['_csrf_token'] ?? $request->request->get('_csrf_token');
        if (!$this->csrfManager->isTokenValid(new CsrfToken('face_login', (string)$token))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 400);
        }

        $descriptor = $content['descriptor'] ?? null;
        if (!is_array($descriptor) || count($descriptor) < 1) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid descriptor'], 400);
        }

        $user = $this->faceService->findMatchingUser($descriptor);
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'No match found'], 404);
        }

        // Programmatically authenticate the user
        $this->userAuthenticator->authenticateUser($user, $this->formAuthenticator, $request);

        $redirect = $this->urlGenerator->generate('app_redirect_user');
        return new JsonResponse(['success' => true, 'redirect' => $redirect]);
    }
}
