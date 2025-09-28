# Processus Event Storming

## Atelier Event Storming - Étapes

```mermaid
graph TD
    A[Préparation] --> B[Session 1: Découverte]
    B --> C[Session 2: Affinage]
    C --> D[Session 3: Architecture]
    D --> E[Documentation]
    
    subgraph "Préparation"
        A1[Inviter les participants]
        A2[Préparer le matériel]
        A3[Définir les objectifs]
    end
    
    subgraph "Session 1: Découverte"
        B1[Identifier les événements métier]
        B2[Identifier les commandes]
        B3[Identifier les acteurs]
        B4[Identifier les règles métier]
    end
    
    subgraph "Session 2: Affinement"
        C1[Grouper les événements]
        C2[Définir les agrégats]
        C3[Identifier les bounded contexts]
        C4[Valider les règles métier]
    end
    
    subgraph "Session 3: Architecture"
        D1[Définir l'architecture]
        D2[Identifier les intégrations]
        D3[Planifier l'implémentation]
        D4[Prioriser les fonctionnalités]
    end
    
    subgraph "Documentation"
        E1[Créer les diagrammes]
        E2[Rédiger les spécifications]
        E3[Partager les résultats]
        E4[Planifier les prochaines étapes]
    end
    
    A --> A1
    A --> A2
    A --> A3
    
    B --> B1
    B --> B2
    B --> B3
    B --> B4
    
    C --> C1
    C --> C2
    C --> C3
    C --> C4
    
    D --> D1
    D --> D2
    D --> D3
    D --> D4
    
    E --> E1
    E --> E2
    E --> E3
    E --> E4
```

## Exemple d'Event Storming - Système de Paiement

```mermaid
graph TB
    subgraph "Acteurs"
        CUSTOMER[Customer]
        MERCHANT[Merchant]
        PAYMENT_GATEWAY[Payment Gateway]
        ADMIN[Admin]
    end
    
    subgraph "Commandes"
        CREATE_PAYMENT[Create Payment]
        PROCESS_PAYMENT[Process Payment]
        REFUND_PAYMENT[Refund Payment]
        CANCEL_PAYMENT[Cancel Payment]
    end
    
    subgraph "Événements"
        PAYMENT_CREATED[Payment Created]
        PAYMENT_PROCESSED[Payment Processed]
        PAYMENT_FAILED[Payment Failed]
        PAYMENT_REFUNDED[Payment Refunded]
        PAYMENT_CANCELLED[Payment Cancelled]
    end
    
    subgraph "Règles Métier"
        VALIDATE_AMOUNT[Amount > 0]
        VALIDATE_CURRENCY[Valid Currency]
        CHECK_BALANCE[Sufficient Balance]
        VERIFY_MERCHANT[Merchant Verified]
    end
    
    CUSTOMER --> CREATE_PAYMENT
    MERCHANT --> PROCESS_PAYMENT
    ADMIN --> REFUND_PAYMENT
    CUSTOMER --> CANCEL_PAYMENT
    
    CREATE_PAYMENT --> PAYMENT_CREATED
    PROCESS_PAYMENT --> PAYMENT_PROCESSED
    PROCESS_PAYMENT --> PAYMENT_FAILED
    REFUND_PAYMENT --> PAYMENT_REFUNDED
    CANCEL_PAYMENT --> PAYMENT_CANCELLED
    
    VALIDATE_AMOUNT --> CREATE_PAYMENT
    VALIDATE_CURRENCY --> CREATE_PAYMENT
    CHECK_BALANCE --> PROCESS_PAYMENT
    VERIFY_MERCHANT --> PROCESS_PAYMENT
    
    style CUSTOMER fill:#e1f5fe
    style MERCHANT fill:#e8f5e8
    style PAYMENT_GATEWAY fill:#fff3e0
    style ADMIN fill:#f3e5f5
    style CREATE_PAYMENT fill:#ffeb3b
    style PAYMENT_CREATED fill:#4caf50
    style VALIDATE_AMOUNT fill:#ff9800
```

## Bounded Contexts - Système Gyroscops Cloud

```mermaid
graph TB
    subgraph "Authentication Context"
        AUTH_USER[User]
        AUTH_ROLE[Role]
        AUTH_PERMISSION[Permission]
        AUTH_SESSION[Session]
    end
    
    subgraph "Payment Context"
        PAYMENT[Payment]
        SUBSCRIPTION[Subscription]
        BILLING[Billing]
        INVOICE[Invoice]
    end
    
    subgraph "Integration Context"
        INTEGRATION[Integration]
        WORKFLOW[Workflow]
        CONNECTOR[Connector]
        MAPPING[Mapping]
    end
    
    subgraph "Monitoring Context"
        METRICS[Metrics]
        ALERT[Alert]
        LOG[Log]
        HEALTH[Health Check]
    end
    
    subgraph "Organization Context"
        ORG[Organization]
        TEAM[Team]
        PROJECT[Project]
        SETTINGS[Settings]
    end
    
    AUTH_USER -.-> PAYMENT
    AUTH_USER -.-> INTEGRATION
    AUTH_USER -.-> METRICS
    AUTH_USER -.-> ORG
    
    PAYMENT -.-> INTEGRATION
    INTEGRATION -.-> METRICS
    ORG -.-> PAYMENT
    ORG -.-> INTEGRATION
    
    style AUTH_USER fill:#e1f5fe
    style PAYMENT fill:#e8f5e8
    style INTEGRATION fill:#fff3e0
    style METRICS fill:#f3e5f5
    style ORG fill:#fff8e1
```

## Flux d'Événements - Création de Paiement

```mermaid
sequenceDiagram
    participant C as Customer
    participant API as API Gateway
    participant CH as Command Handler
    participant A as Payment Aggregate
    participant ES as Event Store
    participant EB as Event Bus
    participant P as Projection
    participant RM as Read Model
    participant QH as Query Handler
    
    C->>API: POST /api/payments
    API->>CH: CreatePaymentCommand
    CH->>A: createPayment(amount, currency, customer)
    
    A->>A: validateAmount()
    A->>A: validateCurrency()
    A->>A: validateCustomer()
    
    A->>ES: save(PaymentCreated)
    A->>EB: publish(PaymentCreated)
    
    EB->>P: handle(PaymentCreated)
    P->>RM: updatePaymentList()
    
    A->>CH: PaymentCreated
    CH->>API: PaymentCreated
    API->>C: 201 Created
    
    Note over C,RM: Query pour récupérer la liste
    C->>API: GET /api/payments
    API->>QH: GetPaymentsQuery
    QH->>RM: findPayments()
    RM->>QH: Payment[]
    QH->>API: Payment[]
    API->>C: 200 OK
```

## Impact Mapping - Système de Paiement

```mermaid
graph TB
    subgraph "Objectif Business"
        OBJ[Augmenter les revenus de 20%]
    end
    
    subgraph "Acteurs"
        CUSTOMER[Customer]
        MERCHANT[Merchant]
        SUPPORT[Support Team]
        DEV[Development Team]
    end
    
    subgraph "Impacts"
        CUSTOMER_IMPACT[Paiement plus rapide]
        MERCHANT_IMPACT[Moins de frais]
        SUPPORT_IMPACT[Moins de tickets]
        DEV_IMPACT[Développement plus rapide]
    end
    
    subgraph "Livrables"
        PAYMENT_API[API de Paiement]
        DASHBOARD[Dashboard]
        DOCUMENTATION[Documentation]
        MONITORING[Monitoring]
    end
    
    OBJ --> CUSTOMER_IMPACT
    OBJ --> MERCHANT_IMPACT
    OBJ --> SUPPORT_IMPACT
    OBJ --> DEV_IMPACT
    
    CUSTOMER_IMPACT --> PAYMENT_API
    MERCHANT_IMPACT --> DASHBOARD
    SUPPORT_IMPACT --> DOCUMENTATION
    DEV_IMPACT --> MONITORING
    
    CUSTOMER --> CUSTOMER_IMPACT
    MERCHANT --> MERCHANT_IMPACT
    SUPPORT --> SUPPORT_IMPACT
    DEV --> DEV_IMPACT
    
    style OBJ fill:#ff5722
    style CUSTOMER fill:#e1f5fe
    style MERCHANT fill:#e8f5e8
    style SUPPORT fill:#fff3e0
    style DEV fill:#f3e5f5
    style PAYMENT_API fill:#4caf50
    style DASHBOARD fill:#ff9800
    style DOCUMENTATION fill:#9c27b0
    style MONITORING fill:#607d8b
```

## Example Mapping - Création de Paiement

```mermaid
graph TB
    subgraph "Règle Métier"
        RULE[Un paiement doit avoir un montant positif]
    end
    
    subgraph "Exemples"
        EX1[✅ Montant: 100.00€ → Paiement créé]
        EX2[❌ Montant: -50.00€ → Erreur de validation]
        EX3[❌ Montant: 0.00€ → Erreur de validation]
        EX4[❌ Montant: null → Erreur de validation]
    end
    
    subgraph "Questions"
        Q1[Que se passe-t-il si le montant est trop élevé?]
        Q2[Que se passe-t-il si la devise n'est pas supportée?]
        Q3[Que se passe-t-il si le client n'existe pas?]
    end
    
    subgraph "Tests d'Acceptation"
        T1[Given: Montant valide<br/>When: Créer le paiement<br/>Then: Paiement créé avec succès]
        T2[Given: Montant négatif<br/>When: Créer le paiement<br/>Then: Erreur de validation]
        T3[Given: Montant zéro<br/>When: Créer le paiement<br/>Then: Erreur de validation]
        T4[Given: Montant null<br/>When: Créer le paiement<br/>Then: Erreur de validation]
    end
    
    RULE --> EX1
    RULE --> EX2
    RULE --> EX3
    RULE --> EX4
    
    RULE --> Q1
    RULE --> Q2
    RULE --> Q3
    
    EX1 --> T1
    EX2 --> T2
    EX3 --> T3
    EX4 --> T4
    
    style RULE fill:#ff5722
    style EX1 fill:#4caf50
    style EX2 fill:#f44336
    style EX3 fill:#f44336
    style EX4 fill:#f44336
    style Q1 fill:#ff9800
    style Q2 fill:#ff9800
    style Q3 fill:#ff9800
    style T1 fill:#e8f5e8
    style T2 fill:#ffebee
    style T3 fill:#ffebee
    style T4 fill:#ffebee
```
