<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class FormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    private UrlGeneratorInterface $urlGenerator;
    private HttpClientInterface $httpClient;
    private string $recaptchaSecret;

    /** Clé secrète Google de test (toujours valide, pour dev uniquement). */
    private const RECAPTCHA_TEST_SECRET = '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe';

    public function __construct(UrlGeneratorInterface $urlGenerator, HttpClientInterface $httpClient, string $recaptchaSecret)
    {
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $httpClient;
        // Si pas de clé configurée, utiliser la clé de test Google pour que le widget affiché (test) valide
        $this->recaptchaSecret = $recaptchaSecret !== '' ? $recaptchaSecret : self::RECAPTCHA_TEST_SECRET;
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('_username', '');
        $password = $request->request->get('_password', '');
        $csrfToken = $request->request->get('_csrf_token');
        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        // Vérification reCAPTCHA uniquement si un secret est configuré (voir RECAPTCHA_SETUP.md)
        if ($this->recaptchaSecret !== '') {
            if (!$recaptchaResponse || trim($recaptchaResponse) === '') {
                throw new CustomUserMessageAuthenticationException('Veuillez compléter le reCAPTCHA.');
            }

            // Google attend application/x-www-form-urlencoded
            $body = http_build_query([
                'secret' => $this->recaptchaSecret,
                'response' => $recaptchaResponse,
                'remoteip' => $request->getClientIp() ?? '',
            ]);

            $resp = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => $body,
            ]);

            try {
                $data = $resp->toArray();
            } catch (\Exception $e) {
                throw new CustomUserMessageAuthenticationException('Impossible de vérifier le reCAPTCHA. Réessayez.');
            }

            if (empty($data['success']) || $data['success'] !== true) {
                throw new CustomUserMessageAuthenticationException('reCAPTCHA invalide. Veuillez le refaire et réessayer.');
            }
        }

        $request->getSession()->set(Security::LAST_USERNAME, $email);

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('app_redirect_user'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}
