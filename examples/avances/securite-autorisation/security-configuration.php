<?php

namespace App\Security\Configuration;

use App\Security\Authentication\HiveAuthenticationService;
use App\Security\Authorization\HiveAuthorizationService;
use App\Security\Audit\HiveSecurityAuditService;
use App\Security\Voter\PaymentVoter;
use App\Security\Voter\UserVoter;
use App\Security\Voter\OrganizationVoter;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

// ✅ Configuration de Sécurité Hive (Projet Hive)
final class HiveSecurityConfiguration
{
    public function configureSecurity(ContainerBuilder $container): void
    {
        $this->configureAuthentication($container);
        $this->configureAuthorization($container);
        $this->configureVoters($container);
        $this->configureAudit($container);
    }

    private function configureAuthentication(ContainerBuilder $container): void
    {
        // Service d'authentification
        $authServiceDef = new Definition(HiveAuthenticationService::class);
        $authServiceDef->setAutowired(true);
        $authServiceDef->setPublic(true);
        $container->setDefinition('hive.security.authentication', $authServiceDef);

        // Configuration Keycloak
        $keycloakConfig = [
            'realm' => $_ENV['KEYCLOAK_REALM'],
            'client_id' => $_ENV['KEYCLOAK_CLIENT_ID'],
            'client_secret' => $_ENV['KEYCLOAK_CLIENT_SECRET'],
            'base_url' => $_ENV['KEYCLOAK_BASE_URL'],
        ];

        $container->setParameter('hive.security.keycloak', $keycloakConfig);
    }

    private function configureAuthorization(ContainerBuilder $container): void
    {
        // Service d'autorisation
        $authzServiceDef = new Definition(HiveAuthorizationService::class);
        $authzServiceDef->setAutowired(true);
        $authzServiceDef->setPublic(true);
        $container->setDefinition('hive.security.authorization', $authzServiceDef);

        // Configuration des permissions
        $permissions = [
            'payment' => [
                'read' => ['ROLE_USER', 'ROLE_ADMIN'],
                'write' => ['ROLE_USER', 'ROLE_ADMIN'],
                'delete' => ['ROLE_ADMIN'],
                'approve' => ['ROLE_ADMIN', 'ROLE_MANAGER'],
                'refund' => ['ROLE_ADMIN', 'ROLE_MANAGER'],
            ],
            'user' => [
                'read' => ['ROLE_USER', 'ROLE_ADMIN'],
                'write' => ['ROLE_ADMIN'],
                'delete' => ['ROLE_ADMIN'],
            ],
            'organization' => [
                'read' => ['ROLE_USER', 'ROLE_ADMIN'],
                'write' => ['ROLE_ADMIN', 'ROLE_MANAGER'],
                'delete' => ['ROLE_ADMIN'],
            ],
        ];

        $container->setParameter('hive.security.permissions', $permissions);
    }

    private function configureVoters(ContainerBuilder $container): void
    {
        // Voter pour les paiements
        $paymentVoterDef = new Definition(PaymentVoter::class);
        $paymentVoterDef->setAutowired(true);
        $paymentVoterDef->addTag('security.voter');
        $container->setDefinition('hive.security.voter.payment', $paymentVoterDef);

        // Voter pour les utilisateurs
        $userVoterDef = new Definition(UserVoter::class);
        $userVoterDef->setAutowired(true);
        $userVoterDef->addTag('security.voter');
        $container->setDefinition('hive.security.voter.user', $userVoterDef);

        // Voter pour les organisations
        $organizationVoterDef = new Definition(OrganizationVoter::class);
        $organizationVoterDef->setAutowired(true);
        $organizationVoterDef->addTag('security.voter');
        $container->setDefinition('hive.security.voter.organization', $organizationVoterDef);
    }

    private function configureAudit(ContainerBuilder $container): void
    {
        // Service d'audit de sécurité
        $auditServiceDef = new Definition(HiveSecurityAuditService::class);
        $auditServiceDef->setAutowired(true);
        $auditServiceDef->setPublic(true);
        $container->setDefinition('hive.security.audit', $auditServiceDef);

        // Configuration des événements d'audit
        $auditEvents = [
            'authentication' => [
                'success' => 'info',
                'failure' => 'warning',
            ],
            'authorization' => [
                'granted' => 'info',
                'denied' => 'warning',
            ],
            'violation' => [
                'all' => 'error',
            ],
        ];

        $container->setParameter('hive.security.audit.events', $auditEvents);
    }
}
