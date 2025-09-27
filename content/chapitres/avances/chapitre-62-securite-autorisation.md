---
title: "Chapitre 62 : Sécurité et Autorisation - Protéger votre API Platform"
description: "Maîtriser la sécurité et l'autorisation dans une API Platform avec DDD"
date: 2024-12-19
draft: true
type: "docs"
weight: 62
---

## 🎯 Objectif de ce Chapitre

### Mon Problème : Comment Sécuriser une API Platform Complexe ?

**Voici ce qui s'est passé avec Gyroscops** : J'avais une API Platform qui fonctionnait bien, mais la sécurité était un vrai casse-tête. Comment gérer les permissions ? Comment sécuriser les endpoints ? Comment gérer l'authentification multi-tenant ?

**Mais attendez...** Quand j'ai voulu implémenter une sécurité robuste, j'étais perdu. OAuth2, JWT, RBAC, ABAC, Keycloak... Tellement d'options ! Comment choisir ? Comment implémenter ?

**Soudain, je réalisais que la sécurité n'était pas optionnelle !** Il me fallait une approche structurée et complète.

### La Sécurité : Mon Guide Complet

La sécurité dans une API Platform avec DDD m'a permis de :
- **Protéger** les données sensibles
- **Contrôler** l'accès aux ressources
- **Gérer** les permissions granulaires
- **Auditer** les actions utilisateurs

## Qu'est-ce que la Sécurité dans une API Platform ?

### Le Concept Fondamental

La sécurité dans une API Platform consiste à protéger les ressources et contrôler l'accès. **L'idée** : Chaque requête doit être authentifiée et autorisée avant d'accéder aux données.

**Avec Gyroscops, voici comment j'ai structuré la sécurité** :

### Les 4 Piliers de la Sécurité

#### 1. **Authentification** - Qui êtes-vous ?

**Voici comment j'ai implémenté l'authentification avec Gyroscops** :

**OAuth2 + JWT** :
- Tokens JWT pour l'authentification
- Refresh tokens pour la sécurité
- Expiration automatique des tokens

**Keycloak** :
- Gestion centralisée des utilisateurs
- Intégration OAuth2
- Gestion des rôles et permissions

#### 2. **Autorisation** - Que pouvez-vous faire ?

**Voici comment j'ai implémenté l'autorisation avec Gyroscops** :

**RBAC (Role-Based Access Control)** :
- Rôles définis par domaine
- Permissions granulaires
- Hiérarchie des rôles

**ABAC (Attribute-Based Access Control)** :
- Contrôle basé sur les attributs
- Règles métier complexes
- Contexte dynamique

#### 3. **Validation** - Les données sont-elles valides ?

**Voici comment j'ai implémenté la validation avec Gyroscops** :

**Validation des Entrées** :
- Validation des données d'entrée
- Sanitisation des données
- Gestion des erreurs

**Validation des Permissions** :
- Vérification des permissions
- Contrôle d'accès aux ressources
- Audit des actions

#### 4. **Audit** - Que s'est-il passé ?

**Voici comment j'ai implémenté l'audit avec Gyroscops** :

**Logging de Sécurité** :
- Logs d'authentification
- Logs d'autorisation
- Logs d'audit

**Monitoring** :
- Détection d'intrusions
- Alertes de sécurité
- Métriques de sécurité

## Comment Implémenter la Sécurité

### 1. **Configuration de l'Authentification**

**Avec Gyroscops** : J'ai configuré l'authentification :

```yaml
# config/packages/security.yaml
security:
    providers:
        keycloak:
            id: App\Security\KeycloakUserProvider
    firewalls:
        api:
            pattern: ^/api
            stateless: true
            jwt: ~
    access_control:
        - { path: ^/api/auth, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: ROLE_USER }
```

**Résultat** : Authentification JWT configurée.

### 2. **Implémentation de l'Autorisation**

**Avec Gyroscops** : J'ai implémenté l'autorisation :

```php
#[Route('/api/payments', methods: ['GET'])]
#[IsGranted('PAYMENT_READ', subject: 'organization')]
public function getPayments(Organization $organization): JsonResponse
{
    // Logique métier
}
```

**Résultat** : Autorisation basée sur les permissions.

### 3. **Gestion des Permissions**

**Avec Gyroscops** : J'ai géré les permissions :

```php
final class PaymentVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, ['PAYMENT_READ', 'PAYMENT_WRITE', 'PAYMENT_DELETE'])
            && $subject instanceof Payment;
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        return match ($attribute) {
            'PAYMENT_READ' => $this->canRead($user, $subject),
            'PAYMENT_WRITE' => $this->canWrite($user, $subject),
            'PAYMENT_DELETE' => $this->canDelete($user, $subject),
            default => false
        };
    }
}
```

**Résultat** : Permissions granulaires et flexibles.

### 4. **Audit et Monitoring**

**Avec Gyroscops** : J'ai implémenté l'audit :

```php
final class SecurityAuditLogger
{
    public function logAuthentication(string $userId, bool $success): void
    {
        $this->logger->info('Authentication attempt', [
            'user_id' => $userId,
            'success' => $success,
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
    
    public function logAuthorization(string $userId, string $resource, string $action, bool $granted): void
    {
        $this->logger->info('Authorization check', [
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
            'granted' => $granted,
            'timestamp' => new \DateTimeImmutable()
        ]);
    }
}
```

**Résultat** : Audit complet des actions de sécurité.

## Les Avantages de la Sécurité Structurée

### 1. **Protection des Données**

**Avec Gyroscops** : La sécurité structurée protège les données :
- Accès contrôlé aux ressources
- Données sensibles protégées
- Conformité réglementaire

**Résultat** : Données sécurisées et conformes.

### 2. **Contrôle d'Accès Granulaire**

**Avec Gyroscops** : La sécurité structurée permet un contrôle granulaire :
- Permissions par ressource
- Rôles contextuels
- Règles métier complexes

**Résultat** : Contrôle d'accès précis et flexible.

### 3. **Audit et Conformité**

**Avec Gyroscops** : La sécurité structurée facilite l'audit :
- Logs complets des actions
- Traçabilité des accès
- Conformité réglementaire

**Résultat** : Audit complet et conformité assurée.

### 4. **Évolutivité**

**Avec Gyroscops** : La sécurité structurée est évolutive :
- Ajout facile de nouvelles permissions
- Extension des rôles
- Adaptation aux besoins métier

**Résultat** : Sécurité évolutive et maintenable.

## Les Inconvénients de la Sécurité Structurée

### 1. **Complexité Accrue**

**Avec Gyroscops** : La sécurité structurée ajoute de la complexité :
- Configuration complexe
- Gestion des permissions
- Debugging plus difficile

**Résultat** : Courbe d'apprentissage plus importante.

### 2. **Performance**

**Avec Gyroscops** : La sécurité structurée peut impacter les performances :
- Vérifications d'autorisation
- Validation des tokens
- Logs d'audit

**Résultat** : Performance potentiellement dégradée.

### 3. **Maintenance**

**Avec Gyroscops** : La sécurité structurée nécessite de la maintenance :
- Mise à jour des permissions
- Gestion des rôles
- Monitoring continu

**Résultat** : Maintenance plus complexe.

### 4. **Gestion des Erreurs**

**Avec Gyroscops** : La sécurité structurée complique la gestion des erreurs :
- Erreurs d'authentification
- Erreurs d'autorisation
- Messages d'erreur appropriés

**Résultat** : Gestion d'erreurs plus complexe.

## Les Pièges à Éviter

### 1. **Sécurité par Obscurité**

**❌ Mauvais** : Compter sur l'obscurité pour la sécurité
**✅ Bon** : Sécurité basée sur des standards éprouvés

**Pourquoi c'est important ?** L'obscurité n'est pas une sécurité.

### 2. **Permissions Trop Granulaires**

**❌ Mauvais** : Une permission pour chaque action
**✅ Bon** : Permissions par domaine métier

**Pourquoi c'est crucial ?** Des permissions trop granulaires sont difficiles à gérer.

### 3. **Ignorer l'Audit**

**❌ Mauvais** : Pas d'audit des actions de sécurité
**✅ Bon** : Audit complet des actions

**Pourquoi c'est essentiel ?** L'audit est essentiel pour la sécurité.

### 4. **Tokens Non Sécurisés**

**❌ Mauvais** : Tokens non sécurisés ou non expirés
**✅ Bon** : Tokens sécurisés avec expiration

**Pourquoi c'est la clé ?** Les tokens non sécurisés sont une faille de sécurité.

## L'Évolution vers la Sécurité Structurée

### Phase 1 : Authentification Basique

**Avec Gyroscops** : Au début, j'avais une authentification basique :
- Login/password simple
- Sessions PHP
- Pas d'autorisation

**Résultat** : Développement rapide, sécurité faible.

### Phase 2 : Introduction de l'Autorisation

**Avec Gyroscops** : J'ai introduit l'autorisation :
- Rôles basiques
- Permissions simples
- Contrôle d'accès

**Résultat** : Sécurité améliorée, complexité accrue.

### Phase 3 : Sécurité Complète

**Avec Gyroscops** : Maintenant, j'ai une sécurité complète :
- OAuth2 + JWT
- RBAC + ABAC
- Audit complet

**Résultat** : Sécurité robuste et évolutive.

## 🏗️ Implémentation Concrète dans le Projet Hive

### Sécurité Appliquée à Hive

Le projet Hive applique concrètement les principes de sécurité à travers son architecture et ses ADR (Architecture Decision Records). Voici comment :

#### Système d'Authentification Hive

```php
// ✅ Système d'Authentification Hive (Projet Hive)
final class HiveAuthenticationService
{
    public function __construct(
        private KeycloakClient $keycloakClient,
        private JwtTokenService $jwtService,
        private UserRepositoryInterface $userRepository,
        private LoggerInterface $logger
    ) {}
    
    public function authenticate(string $email, string $password): AuthResult
    {
        $this->logger->info('Authentication attempt', [
            'email' => $email,
            'timestamp' => new \DateTimeImmutable()
        ]);
        
        try {
            $keycloakUser = $this->keycloakClient->authenticate($email, $password);
            
            if (!$keycloakUser) {
                $this->logger->warning('Authentication failed', [
                    'email' => $email,
                    'reason' => 'Invalid credentials'
                ]);
                
                return AuthResult::failure('Invalid credentials');
            }
            
            $user = $this->userRepository->findByEmail(new Email($email));
            if (!$user) {
                $this->logger->warning('User not found', [
                    'email' => $email
                ]);
                
                return AuthResult::failure('User not found');
            }
            
            $token = $this->jwtService->generateToken($user);
            
            $this->logger->info('Authentication successful', [
                'user_id' => $user->getId()->toString(),
                'email' => $email
            ]);
            
            return AuthResult::success($user, $token);
            
        } catch (\Exception $e) {
            $this->logger->error('Authentication error', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
            
            return AuthResult::failure('Authentication failed');
        }
    }
    
    public function refreshToken(string $refreshToken): AuthResult
    {
        try {
            $newToken = $this->jwtService->refreshToken($refreshToken);
            
            $this->logger->info('Token refreshed successfully');
            
            return AuthResult::success(null, $newToken);
            
        } catch (\Exception $e) {
            $this->logger->error('Token refresh failed', [
                'error' => $e->getMessage()
            ]);
            
            return AuthResult::failure('Token refresh failed');
        }
    }
}
```

#### Système d'Autorisation Hive

```php
// ✅ Système d'Autorisation Hive (Projet Hive)
final class HiveAuthorizationService
{
    public function __construct(
        private PermissionRepositoryInterface $permissionRepository,
        private RoleRepositoryInterface $roleRepository,
        private LoggerInterface $logger
    ) {}
    
    public function hasPermission(User $user, string $resource, string $action): bool
    {
        $this->logger->info('Checking permission', [
            'user_id' => $user->getId()->toString(),
            'resource' => $resource,
            'action' => $action
        ]);
        
        try {
            $userRoles = $this->roleRepository->findByUser($user);
            
            foreach ($userRoles as $role) {
                $permissions = $this->permissionRepository->findByRole($role);
                
                foreach ($permissions as $permission) {
                    if ($permission->matches($resource, $action)) {
                        $this->logger->info('Permission granted', [
                            'user_id' => $user->getId()->toString(),
                            'resource' => $resource,
                            'action' => $action,
                            'role' => $role->getName()
                        ]);
                        
                        return true;
                    }
                }
            }
            
            $this->logger->warning('Permission denied', [
                'user_id' => $user->getId()->toString(),
                'resource' => $resource,
                'action' => $action
            ]);
            
            return false;
            
        } catch (\Exception $e) {
            $this->logger->error('Authorization error', [
                'user_id' => $user->getId()->toString(),
                'resource' => $resource,
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    public function checkResourceAccess(User $user, string $resource, string $action, mixed $subject): bool
    {
        // Vérification des permissions de base
        if (!$this->hasPermission($user, $resource, $action)) {
            return false;
        }
        
        // Vérification des permissions contextuelles
        if ($subject instanceof OrganizationAware) {
            return $this->checkOrganizationAccess($user, $subject);
        }
        
        if ($subject instanceof UserAware) {
            return $this->checkUserAccess($user, $subject);
        }
        
        return true;
    }
    
    private function checkOrganizationAccess(User $user, OrganizationAware $subject): bool
    {
        $userOrganizations = $this->getUserOrganizations($user);
        $subjectOrganization = $subject->getOrganizationId();
        
        return in_array($subjectOrganization, $userOrganizations);
    }
    
    private function checkUserAccess(User $user, UserAware $subject): bool
    {
        return $user->getId()->equals($subject->getUserId());
    }
}
```

#### Voters de Sécurité Hive

```php
// ✅ Voters de Sécurité Hive (Projet Hive)
final class PaymentVoter extends Voter
{
    public function __construct(
        private HiveAuthorizationService $authorizationService,
        private LoggerInterface $logger
    ) {}
    
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
            'PAYMENT_READ',
            'PAYMENT_WRITE',
            'PAYMENT_DELETE',
            'PAYMENT_APPROVE',
            'PAYMENT_REFUND'
        ]) && $subject instanceof Payment;
    }
    
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        
        if (!$user instanceof User) {
            return false;
        }
        
        $this->logger->info('Voting on payment permission', [
            'user_id' => $user->getId()->toString(),
            'attribute' => $attribute,
            'payment_id' => $subject->getId()->toString()
        ]);
        
        $result = match ($attribute) {
            'PAYMENT_READ' => $this->canRead($user, $subject),
            'PAYMENT_WRITE' => $this->canWrite($user, $subject),
            'PAYMENT_DELETE' => $this->canDelete($user, $subject),
            'PAYMENT_APPROVE' => $this->canApprove($user, $subject),
            'PAYMENT_REFUND' => $this->canRefund($user, $subject),
            default => false
        };
        
        $this->logger->info('Vote result', [
            'user_id' => $user->getId()->toString(),
            'attribute' => $attribute,
            'result' => $result
        ]);
        
        return $result;
    }
    
    private function canRead(User $user, Payment $payment): bool
    {
        return $this->authorizationService->checkResourceAccess(
            $user,
            'payment',
            'read',
            $payment
        );
    }
    
    private function canWrite(User $user, Payment $payment): bool
    {
        return $this->authorizationService->checkResourceAccess(
            $user,
            'payment',
            'write',
            $payment
        );
    }
    
    private function canDelete(User $user, Payment $payment): bool
    {
        return $this->authorizationService->checkResourceAccess(
            $user,
            'payment',
            'delete',
            $payment
        );
    }
    
    private function canApprove(User $user, Payment $payment): bool
    {
        return $this->authorizationService->checkResourceAccess(
            $user,
            'payment',
            'approve',
            $payment
        );
    }
    
    private function canRefund(User $user, Payment $payment): bool
    {
        return $this->authorizationService->checkResourceAccess(
            $user,
            'payment',
            'refund',
            $payment
        );
    }
}
```

#### Audit de Sécurité Hive

```php
// ✅ Audit de Sécurité Hive (Projet Hive)
final class HiveSecurityAuditService
{
    public function __construct(
        private LoggerInterface $logger,
        private SecurityEventRepositoryInterface $eventRepository
    ) {}
    
    public function logAuthenticationEvent(string $userId, bool $success, array $context = []): void
    {
        $event = new SecurityEvent(
            SecurityEventType::AUTHENTICATION,
            $userId,
            $success,
            $context
        );
        
        $this->eventRepository->save($event);
        
        $this->logger->info('Authentication event logged', [
            'user_id' => $userId,
            'success' => $success,
            'context' => $context
        ]);
    }
    
    public function logAuthorizationEvent(string $userId, string $resource, string $action, bool $granted, array $context = []): void
    {
        $event = new SecurityEvent(
            SecurityEventType::AUTHORIZATION,
            $userId,
            $granted,
            array_merge($context, [
                'resource' => $resource,
                'action' => $action
            ])
        );
        
        $this->eventRepository->save($event);
        
        $this->logger->info('Authorization event logged', [
            'user_id' => $userId,
            'resource' => $resource,
            'action' => $action,
            'granted' => $granted,
            'context' => $context
        ]);
    }
    
    public function logSecurityViolation(string $userId, string $violationType, array $context = []): void
    {
        $event = new SecurityEvent(
            SecurityEventType::VIOLATION,
            $userId,
            false,
            array_merge($context, [
                'violation_type' => $violationType
            ])
        );
        
        $this->eventRepository->save($event);
        
        $this->logger->warning('Security violation logged', [
            'user_id' => $userId,
            'violation_type' => $violationType,
            'context' => $context
        ]);
    }
}
```

### Références aux ADR du Projet Hive

Ce chapitre s'appuie sur les Architecture Decision Records (ADR) suivants du projet Hive :
- **HIVE025** : Authorization System - Système d'autorisation
- **HIVE026** : Keycloak Resource and Scope Management - Gestion des ressources et scopes
- **HIVE040** : Enhanced Models with Property Access Patterns - Modèles enrichis pour la sécurité
- **HIVE041** : Cross-Cutting Concerns Architecture - Architecture des préoccupations transversales

{{< chapter-nav >}}
  {{{< chapter-option 
    letter="A" 
    color="red" 
    title="Je veux comprendre les chapitres de stockage" 
    subtitle="Vous voulez voir comment implémenter la persistance selon différents patterns" 
    criteria="Équipe expérimentée,Besoin de comprendre la persistance,Patterns de stockage à choisir,Implémentation à faire" 
    time="30-45 minutes" 
    chapter="15" 
    chapter-title="Stockage SQL - Approche Classique" 
    chapter-url="/chapitres/stockage/chapitre-15-stockage-sql-classique/" 
  >}}}}
  
  {{{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux comprendre les chapitres techniques" 
    subtitle="Vous voulez voir les aspects techniques d'affinement" 
    criteria="Équipe expérimentée,Besoin de comprendre les aspects techniques,Qualité et performance importantes,Bonnes pratiques à appliquer" 
    time="25-35 minutes" 
    chapter="58" 
    chapter-title="Gestion des Données et Validation" 
    chapter-url="/chapitres/techniques/chapitre-58-gestion-donnees-validation/" 
  >}}}}
  
  {{{< chapter-option 
    letter="C" 
    color="green" 
    title="Je veux comprendre le frontend" 
    subtitle="Vous voulez voir comment intégrer la sécurité avec le frontend" 
    criteria="Développeur frontend,Besoin d'intégrer la sécurité,API Platform à sécuriser,Interface utilisateur à créer" 
    time="25-35 minutes" 
    chapter="63" 
    chapter-title="Frontend et Intégration" 
    chapter-url="/chapitres/avances/chapitre-63-frontend-integration/" 
  >}}}}
  
{{< /chapter-nav >}}