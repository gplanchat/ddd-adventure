---
title: "Chapitre 31 : Stockage MongoDB - Event Sourcing"
weight: 31
draft: true
---

# Chapitre 31 : Stockage MongoDB - Event Sourcing

## 🎯 **Objectif de ce Chapitre**

Dans ce chapitre, vous allez découvrir comment implémenter Event Sourcing avec MongoDB, en stockant les événements comme source de vérité et en reconstruisant l'état à partir de ces événements.

## 📖 **Mon Expérience avec Gyroscops**

Avec Gyroscops, nous avions un besoin critique d'audit trail pour nos données financières. Les régulateurs exigeaient de pouvoir tracer chaque modification, et nous avions besoin de comprendre l'historique complet des transactions.

### **Le Problème avec l'État Actuel**

Notre collection `billing_documents` ne gardait que l'état final :
- Impossible de savoir qui avait modifié quoi et quand
- Pas de possibilité de "rejouer" l'historique
- Audit trail incomplet pour la conformité

### **La Révolution Event Sourcing**

J'ai découvert qu'Event Sourcing permettait de stocker l'historique complet :
- **Événements immutables** : Chaque changement est un événement
- **Audit trail complet** : Traçabilité totale
- **Replay possible** : Reconstruction de l'état à tout moment

## 🏗️ **Architecture Event Sourcing avec MongoDB**

### **Stockage des Événements**

```javascript
// Événement de création de facture
const InvoiceCreatedEvent = {
  _id: ObjectId(),
  aggregateId: "invoice_123",
  aggregateType: "Invoice",
  eventType: "InvoiceCreated",
  eventVersion: 1,
  eventData: {
    invoiceNumber: "INV-2024-001",
    customerId: "cust_456",
    amount: 1500.00,
    currency: "EUR",
    items: [
      { description: "Cloud Resources", amount: 1200.00 },
      { description: "Support", amount: 300.00 }
    ]
  },
  metadata: {
    userId: "user_789",
    correlationId: "corr_abc",
    causationId: "cause_def"
  },
  timestamp: new Date(),
  sequenceNumber: 1
};

// Événement de paiement
const InvoicePaidEvent = {
  _id: ObjectId(),
  aggregateId: "invoice_123",
  aggregateType: "Invoice",
  eventType: "InvoicePaid",
  eventVersion: 1,
  eventData: {
    paymentId: "pay_789",
    paymentMethod: "credit_card",
    paidAmount: 1500.00,
    paidAt: new Date()
  },
  metadata: {
    userId: "user_789",
    correlationId: "corr_abc",
    causationId: "cause_ghi"
  },
  timestamp: new Date(),
  sequenceNumber: 2
};
```

### **Index Optimisés pour Event Sourcing**

```javascript
// Index pour les requêtes par agrégat
db.events.createIndex({ "aggregateId": 1, "sequenceNumber": 1 });
db.events.createIndex({ "aggregateId": 1, "eventType": 1 });

// Index pour les requêtes temporelles
db.events.createIndex({ "timestamp": 1 });
db.events.createIndex({ "eventType": 1, "timestamp": 1 });

// Index pour les requêtes de corrélation
db.events.createIndex({ "metadata.correlationId": 1 });
db.events.createIndex({ "metadata.userId": 1 });
```

## 🔄 **Reconstruction de l'État**

### **Reconstruction Complète**

```javascript
// Reconstruire l'état complet d'un agrégat
async function reconstructAggregate(aggregateId) {
  const events = await db.events.find({
    aggregateId: aggregateId
  }).sort({ sequenceNumber: 1 });
  
  let aggregate = {
    id: aggregateId,
    version: 0,
    state: {}
  };
  
  for (const event of events) {
    aggregate = applyEvent(aggregate, event);
  }
  
  return aggregate;
}

// Appliquer un événement à l'agrégat
function applyEvent(aggregate, event) {
  switch (event.eventType) {
    case "InvoiceCreated":
      return {
        ...aggregate,
        version: event.sequenceNumber,
        state: {
          ...aggregate.state,
          invoiceNumber: event.eventData.invoiceNumber,
          customerId: event.eventData.customerId,
          amount: event.eventData.amount,
          currency: event.eventData.currency,
          items: event.eventData.items,
          status: "pending",
          createdAt: event.timestamp
        }
      };
    
    case "InvoicePaid":
      return {
        ...aggregate,
        version: event.sequenceNumber,
        state: {
          ...aggregate.state,
          status: "paid",
          paymentId: event.eventData.paymentId,
          paymentMethod: event.eventData.paymentMethod,
          paidAt: event.eventData.paidAt
        }
      };
    
    default:
      return aggregate;
  }
}
```

### **Reconstruction Incrémentale**

```javascript
// Reconstruire à partir d'une version spécifique
async function reconstructFromVersion(aggregateId, fromVersion) {
  const events = await db.events.find({
    aggregateId: aggregateId,
    sequenceNumber: { $gt: fromVersion }
  }).sort({ sequenceNumber: 1 });
  
  let aggregate = await getSnapshot(aggregateId, fromVersion);
  
  for (const event of events) {
    aggregate = applyEvent(aggregate, event);
  }
  
  return aggregate;
}
```

## 📊 **Snapshots pour Performance**

### **Création de Snapshots**

```javascript
// Créer un snapshot de l'agrégat
async function createSnapshot(aggregateId, version) {
  const aggregate = await reconstructAggregate(aggregateId);
  
  const snapshot = {
    _id: `${aggregateId}_${version}`,
    aggregateId: aggregateId,
    version: version,
    state: aggregate.state,
    timestamp: new Date()
  };
  
  await db.snapshots.replaceOne(
    { _id: snapshot._id },
    snapshot,
    { upsert: true }
  );
  
  return snapshot;
}

// Récupérer un snapshot
async function getSnapshot(aggregateId, version) {
  const snapshot = await db.snapshots.findOne({
    aggregateId: aggregateId,
    version: { $lte: version }
  }).sort({ version: -1 });
  
  if (snapshot) {
    return {
      id: aggregateId,
      version: snapshot.version,
      state: snapshot.state
    };
  }
  
  return {
    id: aggregateId,
    version: 0,
    state: {}
  };
}
```

### **Stratégie de Snapshot**

```javascript
// Créer des snapshots tous les N événements
const SNAPSHOT_FREQUENCY = 100;

async function shouldCreateSnapshot(aggregateId) {
  const eventCount = await db.events.countDocuments({
    aggregateId: aggregateId
  });
  
  return eventCount % SNAPSHOT_FREQUENCY === 0;
}
```

## 🔍 **Requêtes sur les Événements**

### **Requêtes Temporelles**

```javascript
// Tous les événements d'un type sur une période
async function getEventsByType(eventType, startDate, endDate) {
  return await db.events.find({
    eventType: eventType,
    timestamp: {
      $gte: startDate,
      $lte: endDate
    }
  }).sort({ timestamp: 1 });
}

// Événements par utilisateur
async function getEventsByUser(userId, limit = 100) {
  return await db.events.find({
    "metadata.userId": userId
  }).sort({ timestamp: -1 }).limit(limit);
}
```

### **Requêtes de Corrélation**

```javascript
// Tous les événements d'une corrélation
async function getEventsByCorrelation(correlationId) {
  return await db.events.find({
    "metadata.correlationId": correlationId
  }).sort({ timestamp: 1 });
}

// Tracer le flux d'une commande
async function traceCommandFlow(correlationId) {
  const events = await getEventsByCorrelation(correlationId);
  
  const flow = events.map(event => ({
    timestamp: event.timestamp,
    aggregateId: event.aggregateId,
    eventType: event.eventType,
    userId: event.metadata.userId
  }));
  
  return flow;
}
```

## ⚠️ **Défis et Solutions**

### **Gestion des Conflits**

```javascript
// Vérifier la version avant d'ajouter un événement
async function appendEvent(aggregateId, eventType, eventData, expectedVersion) {
  const session = await client.startSession();
  
  try {
    await session.withTransaction(async () => {
      // Vérifier la version actuelle
      const currentVersion = await db.events.findOne(
        { aggregateId: aggregateId },
        { sort: { sequenceNumber: -1 } }
      );
      
      if (currentVersion && currentVersion.sequenceNumber !== expectedVersion) {
        throw new Error(`Version conflict: expected ${expectedVersion}, got ${currentVersion.sequenceNumber}`);
      }
      
      // Ajouter l'événement
      const event = {
        _id: ObjectId(),
        aggregateId: aggregateId,
        aggregateType: "Invoice",
        eventType: eventType,
        eventVersion: 1,
        eventData: eventData,
        timestamp: new Date(),
        sequenceNumber: (currentVersion?.sequenceNumber || 0) + 1
      };
      
      await db.events.insertOne(event);
    });
  } finally {
    await session.endSession();
  }
}
```

### **Gestion des Erreurs**

```javascript
// Retry automatique en cas d'échec
async function appendEventWithRetry(aggregateId, eventType, eventData, expectedVersion, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      await appendEvent(aggregateId, eventType, eventData, expectedVersion);
      return;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      
      // Attendre avant de retry
      await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
    }
  }
}
```

### **Nettoyage des Anciens Événements**

```javascript
// Archiver les anciens événements
async function archiveOldEvents(olderThanDays = 365) {
  const cutoffDate = new Date();
  cutoffDate.setDate(cutoffDate.getDate() - olderThanDays);
  
  const oldEvents = await db.events.find({
    timestamp: { $lt: cutoffDate }
  }).toArray();
  
  // Archiver vers une collection séparée
  if (oldEvents.length > 0) {
    await db.events_archive.insertMany(oldEvents);
    await db.events.deleteMany({
      _id: { $in: oldEvents.map(e => e._id) }
    });
  }
}
```

## 🎯 **Critères d'Adoption**

### **Quand Utiliser Event Sourcing avec MongoDB**

- ✅ **Audit trail critique** : Conformité réglementaire requise
- ✅ **Debugging complexe** : Besoin de comprendre l'historique
- ✅ **Équipe très expérimentée** : Connaissance d'Event Sourcing
- ✅ **Budget important** : Complexité justifiée par les besoins

### **Quand Éviter Event Sourcing avec MongoDB**

- ❌ **Application simple** : Pas de besoin d'audit trail
- ❌ **Équipe junior** : Complexité trop élevée
- ❌ **Performance critique** : Latence < 10ms requise
- ❌ **Budget limité** : Complexité non justifiée

## 📈 **Métriques de Succès**

### **Performance**

```javascript
const performanceMetrics = {
  eventAppendLatency: "< 50ms",
  aggregateReconstruction: "< 200ms",
  snapshotCreation: "< 1s",
  eventQueryLatency: "< 100ms"
};
```

### **Monitoring**

```javascript
const monitoringMetrics = {
  eventsPerSecond: "> 1k",
  averageAggregateSize: "< 100 events",
  snapshotFrequency: "Every 100 events",
  archiveFrequency: "Daily"
};
```

## 🔄 **Migration vers Event Sourcing**

### **Étape 1 : Créer les Collections**

```javascript
// Créer les collections Event Sourcing
db.createCollection("events");
db.createCollection("snapshots");
db.createCollection("events_archive");
```

### **Étape 2 : Migrer les Données Existantes**

```javascript
// Migration des données existantes vers Event Sourcing
async function migrateToEventSourcing() {
  const existingDocuments = await db.billing_documents.find({});
  
  for (const doc of existingDocuments) {
    // Créer l'événement de migration
    await db.events.insertOne({
      _id: ObjectId(),
      aggregateId: doc._id,
      aggregateType: "Invoice",
      eventType: "InvoiceMigrated",
      eventVersion: 1,
      eventData: doc,
      timestamp: new Date(),
      sequenceNumber: 1
    });
  }
}
```

### **Étape 3 : Basculer Progressivement**

```javascript
// Feature flag pour basculer vers Event Sourcing
const useEventSourcing = await getFeatureFlag("mongodb_event_sourcing_enabled");

if (useEventSourcing) {
  // Utiliser Event Sourcing
  return await reconstructAggregate(aggregateId);
} else {
  // Utiliser l'ancienne collection
  return await db.billing_documents.findOne({ _id: aggregateId });
}
```

## 💡 **Conseils Pratiques**

### **Design des Événements**

1. **Immutables** : Jamais de modification après création
2. **Versionnés** : Support des évolutions de schéma
3. **Métadonnées** : CorrelationId, CausationId, UserId
4. **Granularité** : Un événement par action métier

### **Monitoring et Alertes**

1. **Lag de reconstruction** : < 200ms
2. **Taux d'erreur** : < 0.1%
3. **Performance** : Latence < 50ms
4. **Cohérence** : Vérification quotidienne

## 🎯 **Votre Prochaine Étape**

Maintenant que vous comprenez l'approche Event Sourcing avec MongoDB, quelle est votre situation ?

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre CQRS + Event Sourcing" 
    subtitle="Vous voulez combiner les deux approches"
    criteria="Audit trail critique,Debugging complexe,Équipe très expérimentée,Budget important"
    time="45-55 minutes"
    chapter="32"
    chapter-title="Stockage MongoDB - CQRS + Event Sourcing"
    chapter-url="/chapitres/stockage/mongodb/chapitre-32-stockage-mongodb-cqrs-event-sourcing/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux voir l'approche CQRS" 
    subtitle="Vous voulez comprendre la séparation des modèles"
    criteria="Équipes multiples,Cas d'usage très différents,Performance critique,Évolutivité maximale"
    time="35-45 minutes"
    chapter="30"
    chapter-title="Stockage MongoDB - Approche CQRS"
    chapter-url="/chapitres/stockage/mongodb/chapitre-30-stockage-mongodb-cqrs/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux revenir à l'approche CQS" 
    subtitle="Vous voulez une approche plus simple"
    criteria="Équipe expérimentée,Besoin d'optimiser les performances,Séparation des responsabilités importante,Évolutivité importante"
    time="30-40 minutes"
    chapter="29"
    chapter-title="Stockage MongoDB - Approche CQS"
    chapter-url="/chapitres/stockage/mongodb/chapitre-29-stockage-mongodb-cqs/"
  >}}
  
  {{< chapter-option 
    letter="D" 
    color="blue" 
    title="Je veux explorer d'autres types de stockage" 
    subtitle="Vous voulez voir les alternatives à MongoDB"
    criteria="Besoin de comparer les options,Choix de stockage à faire,Équipe en réflexion"
    time="30-40 minutes"
    chapter="10"
    chapter-title="Choix du Type de Stockage"
    chapter-url="/chapitres/fondamentaux/chapitre-10-choix-type-stockage/"
  >}}
{{< /chapter-nav >}}

**💡 Conseil** : Si vous n'êtes pas sûr, commencez par l'approche CQS (option C) pour bien comprendre la séparation des responsabilités, puis revenez à Event Sourcing quand vous serez prêt.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, continuez avec l'approche CQRS + Event Sourcing (option A).
