# Architecture Générale du Projet Hive

## Vue d'ensemble de l'Architecture

```mermaid
graph TB
    subgraph "Frontend Layer"
        PWA[PWA React Admin]
        API_CLIENT[API Client]
    end
    
    subgraph "API Platform Layer"
        API_GATEWAY[API Gateway]
        AUTH[Authentication]
        AUTHZ[Authorization]
    end
    
    subgraph "Domain Layer"
        subgraph "Authentication Domain"
            USER[User]
            ORG[Organization]
            ROLE[Role]
        end
        
        subgraph "Payment Domain"
            PAYMENT[Payment]
            SUBSCRIPTION[Subscription]
            BILLING[Billing]
        end
        
        subgraph "Integration Domain"
            INTEGRATION[Integration]
            WORKFLOW[Workflow]
            CONNECTOR[Connector]
        end
        
        subgraph "Monitoring Domain"
            METRICS[Metrics]
            ALERT[Alert]
            HEALTH[Health Check]
        end
    end
    
    subgraph "Infrastructure Layer"
        subgraph "Repositories"
            SQL_REPO[SQL Repository]
            API_REPO[API Repository]
            ES_REPO[ElasticSearch Repository]
            MONGO_REPO[MongoDB Repository]
            MEMORY_REPO[In-Memory Repository]
        end
        
        subgraph "External Services"
            KEYCLOAK[Keycloak]
            EXTERNAL_API[External APIs]
            DATABASE[(Database)]
            ELASTICSEARCH[(ElasticSearch)]
            MONGODB[(MongoDB)]
        end
    end
    
    subgraph "Cross-Cutting Concerns"
        EVENT_BUS[Event Bus]
        LOGGING[Logging]
        METRICS_COLLECTOR[Metrics Collector]
        AUDIT[Audit]
    end
    
    PWA --> API_CLIENT
    API_CLIENT --> API_GATEWAY
    API_GATEWAY --> AUTH
    API_GATEWAY --> AUTHZ
    
    AUTH --> USER
    AUTHZ --> ROLE
    
    API_GATEWAY --> PAYMENT
    API_GATEWAY --> INTEGRATION
    API_GATEWAY --> METRICS
    
    PAYMENT --> SQL_REPO
    INTEGRATION --> API_REPO
    METRICS --> ES_REPO
    
    SQL_REPO --> DATABASE
    API_REPO --> EXTERNAL_API
    ES_REPO --> ELASTICSEARCH
    MONGO_REPO --> MONGODB
    
    EVENT_BUS --> LOGGING
    EVENT_BUS --> METRICS_COLLECTOR
    EVENT_BUS --> AUDIT
    
    style PWA fill:#e1f5fe
    style API_GATEWAY fill:#fff3e0
    style PAYMENT fill:#e8f5e8
    style INTEGRATION fill:#fff8e1
    style METRICS fill:#f3e5f5
```

## Architecture CQRS avec Event Sourcing

```mermaid
graph TB
    subgraph "Command Side"
        COMMAND[Command]
        COMMAND_HANDLER[Command Handler]
        AGGREGATE[Aggregate]
        EVENT_STORE[Event Store]
    end
    
    subgraph "Query Side"
        QUERY[Query]
        QUERY_HANDLER[Query Handler]
        READ_MODEL[Read Model]
        PROJECTION[Projection]
    end
    
    subgraph "Event Bus"
        EVENT_BUS[Event Bus]
        EVENT_HANDLER[Event Handler]
    end
    
    COMMAND --> COMMAND_HANDLER
    COMMAND_HANDLER --> AGGREGATE
    AGGREGATE --> EVENT_STORE
    AGGREGATE --> EVENT_BUS
    
    EVENT_BUS --> EVENT_HANDLER
    EVENT_HANDLER --> PROJECTION
    PROJECTION --> READ_MODEL
    
    QUERY --> QUERY_HANDLER
    QUERY_HANDLER --> READ_MODEL
    
    style COMMAND fill:#ffeb3b
    style QUERY fill:#4caf50
    style EVENT_BUS fill:#ff9800
    style AGGREGATE fill:#e91e63
```

## Architecture des Repositories

```mermaid
graph TB
    subgraph "Repository Interfaces"
        CMD_REPO[Command Repository]
        QUERY_REPO[Query Repository]
    end
    
    subgraph "Repository Implementations"
        SQL_CMD[SQL Command Repository]
        SQL_QUERY[SQL Query Repository]
        API_CMD[API Command Repository]
        API_QUERY[API Query Repository]
        ES_CMD[ElasticSearch Command Repository]
        ES_QUERY[ElasticSearch Query Repository]
        MONGO_CMD[MongoDB Command Repository]
        MONGO_QUERY[MongoDB Query Repository]
        MEMORY_CMD[In-Memory Command Repository]
        MEMORY_QUERY[In-Memory Query Repository]
    end
    
    subgraph "Data Sources"
        DATABASE[(SQL Database)]
        EXTERNAL_API[External APIs]
        ELASTICSEARCH[(ElasticSearch)]
        MONGODB[(MongoDB)]
        MEMORY[(In-Memory)]
    end
    
    CMD_REPO --> SQL_CMD
    CMD_REPO --> API_CMD
    CMD_REPO --> ES_CMD
    CMD_REPO --> MONGO_CMD
    CMD_REPO --> MEMORY_CMD
    
    QUERY_REPO --> SQL_QUERY
    QUERY_REPO --> API_QUERY
    QUERY_REPO --> ES_QUERY
    QUERY_REPO --> MONGO_QUERY
    QUERY_REPO --> MEMORY_QUERY
    
    SQL_CMD --> DATABASE
    SQL_QUERY --> DATABASE
    API_CMD --> EXTERNAL_API
    API_QUERY --> EXTERNAL_API
    ES_CMD --> ELASTICSEARCH
    ES_QUERY --> ELASTICSEARCH
    MONGO_CMD --> MONGODB
    MONGO_QUERY --> MONGODB
    MEMORY_CMD --> MEMORY
    MEMORY_QUERY --> MEMORY
    
    style CMD_REPO fill:#ff5722
    style QUERY_REPO fill:#4caf50
    style DATABASE fill:#2196f3
    style EXTERNAL_API fill:#ff9800
    style ELASTICSEARCH fill:#9c27b0
    style MONGODB fill:#4caf50
    style MEMORY fill:#607d8b
```

## Flux de Données avec Event Sourcing

```mermaid
sequenceDiagram
    participant Client
    participant API
    participant CommandHandler
    participant Aggregate
    participant EventStore
    participant EventBus
    participant Projection
    participant ReadModel
    participant QueryHandler
    
    Client->>API: POST /api/payments
    API->>CommandHandler: CreatePaymentCommand
    CommandHandler->>Aggregate: createPayment()
    Aggregate->>EventStore: save(PaymentCreated)
    Aggregate->>EventBus: publish(PaymentCreated)
    EventBus->>Projection: handle(PaymentCreated)
    Projection->>ReadModel: update()
    CommandHandler->>API: PaymentCreated
    API->>Client: 201 Created
    
    Client->>API: GET /api/payments
    API->>QueryHandler: GetPaymentsQuery
    QueryHandler->>ReadModel: findPayments()
    ReadModel->>QueryHandler: Payment[]
    QueryHandler->>API: Payment[]
    API->>Client: 200 OK
```

## Architecture de Sécurité

```mermaid
graph TB
    subgraph "Frontend Security"
        TOKEN[JWT Token]
        REFRESH[Refresh Token]
        STORAGE[Local Storage]
    end
    
    subgraph "API Security"
        MIDDLEWARE[Security Middleware]
        AUTH_SERVICE[Authentication Service]
        AUTHZ_SERVICE[Authorization Service]
        VOTER[Voter]
    end
    
    subgraph "External Security"
        KEYCLOAK[Keycloak]
        RBAC[RBAC]
        ABAC[ABAC]
    end
    
    subgraph "Audit & Monitoring"
        AUDIT_LOG[Audit Log]
        SECURITY_METRICS[Security Metrics]
        ALERT[Security Alert]
    end
    
    TOKEN --> MIDDLEWARE
    REFRESH --> AUTH_SERVICE
    STORAGE --> TOKEN
    
    MIDDLEWARE --> AUTH_SERVICE
    MIDDLEWARE --> AUTHZ_SERVICE
    AUTHZ_SERVICE --> VOTER
    
    AUTH_SERVICE --> KEYCLOAK
    AUTHZ_SERVICE --> RBAC
    AUTHZ_SERVICE --> ABAC
    
    MIDDLEWARE --> AUDIT_LOG
    AUTH_SERVICE --> SECURITY_METRICS
    AUTHZ_SERVICE --> ALERT
    
    style TOKEN fill:#ffeb3b
    style MIDDLEWARE fill:#ff5722
    style KEYCLOAK fill:#2196f3
    style AUDIT_LOG fill:#9c27b0
```

## Architecture Frontend

```mermaid
graph TB
    subgraph "UI Components"
        PAGES[Pages]
        COMPONENTS[Components]
        FORMS[Forms]
        LISTS[Lists]
    end
    
    subgraph "State Management"
        REDUX[Redux Store]
        SLICES[Slices]
        ACTIONS[Actions]
        SELECTORS[Selectors]
    end
    
    subgraph "API Layer"
        API_CLIENT[API Client]
        SERVICES[Services]
        HOOKS[Custom Hooks]
    end
    
    subgraph "Routing & Navigation"
        ROUTER[React Router]
        GUARDS[Route Guards]
        NAVIGATION[Navigation]
    end
    
    subgraph "External Dependencies"
        BACKEND[Backend API]
        KEYCLOAK[Keycloak]
        EXTERNAL[External APIs]
    end
    
    PAGES --> COMPONENTS
    COMPONENTS --> FORMS
    COMPONENTS --> LISTS
    
    PAGES --> REDUX
    COMPONENTS --> SELECTORS
    FORMS --> ACTIONS
    LISTS --> ACTIONS
    
    REDUX --> SLICES
    SLICES --> ACTIONS
    SLICES --> SELECTORS
    
    ACTIONS --> API_CLIENT
    SELECTORS --> SERVICES
    HOOKS --> API_CLIENT
    
    API_CLIENT --> SERVICES
    SERVICES --> HOOKS
    
    ROUTER --> GUARDS
    GUARDS --> NAVIGATION
    NAVIGATION --> PAGES
    
    API_CLIENT --> BACKEND
    API_CLIENT --> KEYCLOAK
    SERVICES --> EXTERNAL
    
    style PAGES fill:#e1f5fe
    style REDUX fill:#fff3e0
    style API_CLIENT fill:#e8f5e8
    style ROUTER fill:#fff8e1
```
