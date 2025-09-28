<?php

namespace App\Security\Middleware;

use App\Security\Authentication\HiveAuthenticationService;
use App\Security\Authorization\HiveAuthorizationService;
use App\Security\Audit\HiveSecurityAuditService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

// ✅ Middleware de Sécurité Gyroscops Cloud (Projet Gyroscops Cloud)
final class HiveSecurityMiddleware
{
    public function __construct(
        private HiveAuthenticationService $authService,
        private HiveAuthorizationService $authzService,
        private HiveSecurityAuditService $auditService,
        private LoggerInterface $logger
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Ignorer les requêtes non-API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->logger->info('Processing API request', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent')
        ]);

        // Vérifier l'authentification
        if (!$this->authenticateRequest($request)) {
            $this->auditService->logAuthenticationEvent(
                'anonymous',
                false,
                [
                    'path' => $request->getPathInfo(),
                    'ip' => $request->getClientIp()
                ]
            );
            
            $event->setResponse(new Response('Unauthorized', 401));
            return;
        }

        // Vérifier l'autorisation
        if (!$this->authorizeRequest($request)) {
            $this->auditService->logAuthorizationEvent(
                $this->getCurrentUserId($request),
                $this->extractResource($request),
                $this->extractAction($request),
                false,
                [
                    'path' => $request->getPathInfo(),
                    'ip' => $request->getClientIp()
                ]
            );
            
            $event->setResponse(new Response('Forbidden', 403));
            return;
        }

        // Log de succès
        $this->auditService->logAuthorizationEvent(
            $this->getCurrentUserId($request),
            $this->extractResource($request),
            $this->extractAction($request),
            true,
            [
                'path' => $request->getPathInfo(),
                'ip' => $request->getClientIp()
            ]
        );
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        
        // Ignorer les requêtes non-API
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        // Ajouter des headers de sécurité
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Content-Security-Policy', "default-src 'self'");

        $this->logger->info('API response sent', [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'status_code' => $response->getStatusCode(),
            'response_time' => microtime(true) - $request->server->get('REQUEST_TIME_FLOAT')
        ]);
    }

    private function authenticateRequest(Request $request): bool
    {
        $token = $this->extractToken($request);
        
        if (!$token) {
            return false;
        }

        try {
            $result = $this->authService->validateToken($token);
            return $result->isSuccess();
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'error' => $e->getMessage(),
                'path' => $request->getPathInfo()
            ]);
            return false;
        }
    }

    private function authorizeRequest(Request $request): bool
    {
        $user = $this->getCurrentUser($request);
        $resource = $this->extractResource($request);
        $action = $this->extractAction($request);

        if (!$user || !$resource || !$action) {
            return false;
        }

        try {
            return $this->authzService->hasPermission($user, $resource, $action);
        } catch (\Exception $e) {
            $this->logger->error('Authorization error', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()->toString(),
                'resource' => $resource,
                'action' => $action
            ]);
            return false;
        }
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        return substr($authHeader, 7);
    }

    private function getCurrentUser(Request $request): ?object
    {
        // Implémentation pour récupérer l'utilisateur actuel
        // depuis le token ou la session
        return $request->attributes->get('user');
    }

    private function getCurrentUserId(Request $request): string
    {
        $user = $this->getCurrentUser($request);
        return $user ? $user->getId()->toString() : 'anonymous';
    }

    private function extractResource(Request $request): ?string
    {
        $path = $request->getPathInfo();
        
        // Extraire la ressource depuis le chemin
        // /api/payments -> payment
        // /api/users -> user
        // /api/organizations -> organization
        
        if (preg_match('/^\/api\/([^\/]+)/', $path, $matches)) {
            return rtrim($matches[1], 's'); // Enlever le 's' du pluriel
        }
        
        return null;
    }

    private function extractAction(Request $request): ?string
    {
        $method = $request->getMethod();
        
        return match ($method) {
            'GET' => 'read',
            'POST' => 'write',
            'PUT', 'PATCH' => 'write',
            'DELETE' => 'delete',
            default => null
        };
    }
}
