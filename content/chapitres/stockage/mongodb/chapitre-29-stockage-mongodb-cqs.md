---
title: "Chapitre 29 : Stockage MongoDB - Approche CQS"
weight: 29
draft: true
---

# Chapitre 29 : Stockage MongoDB - Approche CQS

## 🎯 **Objectif de ce Chapitre**

Dans ce chapitre, vous allez découvrir comment implémenter le pattern CQS (Command Query Separation) avec MongoDB, en séparant clairement les opérations de lecture et d'écriture pour optimiser les performances et la maintenabilité.

## 📖 **Mon Expérience avec Gyroscops**

Avec Gyroscops, nous avions un défi particulier : nos utilisateurs consultaient massivement les données de facturation (lectures) mais les mises à jour étaient rares (écritures). MongoDB nous permettait de créer des collections optimisées pour chaque type d'opération.

### **Le Problème Initial**

Notre collection `billing_documents` était devenue un goulot d'étranglement :
- Les requêtes de lecture représentaient 95% du trafic
- Les écritures bloquaient les lectures
- Les index étaient optimisés pour les écritures, pas les lectures

### **La Découverte CQS avec MongoDB**

J'ai découvert que MongoDB permettait de créer des collections spécialisées :
- **Collection de commandes** : Optimisée pour les écritures
- **Collection de requêtes** : Optimisée pour les lectures
- **Synchronisation** : Via des projections en temps réel

## 🏗️ **Architecture CQS avec MongoDB**

### **Séparation des Collections**

```javascript
// Collection de commandes (écritures)
db.billing_commands.insertOne({
  _id: ObjectId(),
  type: "invoice_created",
  data: {
    invoiceId: "inv_123",
    amount: 1500.00,
    currency: "EUR",
    customerId: "cust_456"
  },
  timestamp: new Date(),
  version: 1
});

// Collection de requêtes (lectures)
db.billing_queries.insertOne({
  _id: "inv_123",
  invoiceNumber: "INV-2024-001",
  amount: 1500.00,
  currency: "EUR",
  customer: {
    id: "cust_456",
    name: "Acme Corp",
    email: "billing@acme.com"
  },
  status: "paid",
  createdAt: new Date(),
  updatedAt: new Date()
});
```

### **Index Optimisés par Collection**

```javascript
// Index pour les commandes (écritures)
db.billing_commands.createIndex({ "timestamp": 1 });
db.billing_commands.createIndex({ "type": 1, "timestamp": 1 });

// Index pour les requêtes (lectures)
db.billing_queries.createIndex({ "customer.id": 1, "status": 1 });
db.billing_queries.createIndex({ "amount": 1, "currency": 1 });
db.billing_queries.createIndex({ "createdAt": -1 });
```

## 🔄 **Synchronisation des Collections**

### **Projection en Temps Réel**

```javascript
// Change Stream pour synchroniser les collections
const changeStream = db.billing_commands.watch([
  { $match: { "operationType": "insert" } }
]);

changeStream.on('change', (change) => {
  const command = change.fullDocument;
  
  // Transformer la commande en requête
  const query = transformCommandToQuery(command);
  
  // Insérer dans la collection de requêtes
  db.billing_queries.replaceOne(
    { _id: query._id },
    query,
    { upsert: true }
  );
});
```

### **Transformation des Données**

```javascript
function transformCommandToQuery(command) {
  switch (command.type) {
    case "invoice_created":
      return {
        _id: command.data.invoiceId,
        invoiceNumber: generateInvoiceNumber(command.data),
        amount: command.data.amount,
        currency: command.data.currency,
        customer: enrichCustomerData(command.data.customerId),
        status: "pending",
        createdAt: command.timestamp,
        updatedAt: command.timestamp
      };
    
    case "invoice_paid":
      return {
        _id: command.data.invoiceId,
        status: "paid",
        paidAt: command.timestamp,
        updatedAt: command.timestamp
      };
    
    default:
      throw new Error(`Unknown command type: ${command.type}`);
  }
}
```

## 📊 **Avantages de CQS avec MongoDB**

### **Performance Optimisée**

```javascript
// Lecture ultra-rapide (collection optimisée)
const invoices = await db.billing_queries.find({
  "customer.id": "cust_456",
  "status": "paid"
}).sort({ "createdAt": -1 }).limit(10);

// Écriture atomique (collection de commandes)
const result = await db.billing_commands.insertOne({
  type: "invoice_created",
  data: invoiceData,
  timestamp: new Date(),
  version: 1
});
```

### **Scalabilité Horizontale**

```javascript
// Sharding par type d'opération
sh.shardCollection("gyroscops.billing_commands", { "type": 1 });
sh.shardCollection("gyroscops.billing_queries", { "customer.id": 1 });
```

## ⚠️ **Défis et Solutions**

### **Cohérence Éventuelle**

```javascript
// Vérification de cohérence
async function checkConsistency(invoiceId) {
  const command = await db.billing_commands.findOne({
    "data.invoiceId": invoiceId
  });
  
  const query = await db.billing_queries.findOne({
    _id: invoiceId
  });
  
  if (!command || !query) {
    throw new Error(`Inconsistency detected for invoice ${invoiceId}`);
  }
  
  return { command, query };
}
```

### **Gestion des Erreurs**

```javascript
// Retry automatique en cas d'échec
async function syncWithRetry(command, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      await syncCommandToQuery(command);
      return;
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
    }
  }
}
```

## 🎯 **Critères d'Adoption**

### **Quand Utiliser CQS avec MongoDB**

- ✅ **Lectures/écritures très différentes** : 80/20 ou plus
- ✅ **Performance critique** : Latence < 100ms requise
- ✅ **Équipe expérimentée** : Connaissance de MongoDB avancée
- ✅ **Évolutivité importante** : Croissance prévue > 10x

### **Quand Éviter CQS avec MongoDB**

- ❌ **Application simple** : Peu de trafic, équipe junior
- ❌ **Cohérence forte requise** : Données financières critiques
- ❌ **Développement rapide** : MVP, prototypage
- ❌ **Budget limité** : Complexité supplémentaire non justifiée

## 📈 **Métriques de Succès**

### **Performance**

```javascript
// Métriques de performance
const metrics = {
  readLatency: "< 50ms",
  writeLatency: "< 100ms",
  throughput: "> 10k ops/sec",
  consistency: "99.9%"
};
```

### **Monitoring**

```javascript
// Dashboard de monitoring
const dashboard = {
  commandsPerSecond: monitorCommands(),
  queriesPerSecond: monitorQueries(),
  syncLag: monitorSyncLag(),
  errorRate: monitorErrors()
};
```

## 🔄 **Migration depuis l'Approche Classique**

### **Étape 1 : Créer les Collections CQS**

```javascript
// Créer les nouvelles collections
db.createCollection("billing_commands");
db.createCollection("billing_queries");

// Créer les index optimisés
createOptimizedIndexes();
```

### **Étape 2 : Migrer les Données Existantes**

```javascript
// Migration des données existantes
async function migrateToCQS() {
  const existingData = await db.billing_documents.find({});
  
  for (const doc of existingData) {
    // Créer la commande
    await db.billing_commands.insertOne({
      type: "migration",
      data: doc,
      timestamp: new Date(),
      version: 1
    });
    
    // Créer la requête
    await db.billing_queries.insertOne(transformToQuery(doc));
  }
}
```

### **Étape 3 : Basculer Progressivement**

```javascript
// Feature flag pour basculer progressivement
const useCQS = await getFeatureFlag("mongodb_cqs_enabled");

if (useCQS) {
  // Utiliser les collections CQS
  return await queryFromCQSCollection();
} else {
  // Utiliser l'ancienne collection
  return await queryFromLegacyCollection();
}
```

## 💡 **Conseils Pratiques**

### **Design des Collections**

1. **Commandes** : Structure simple, optimisée pour l'écriture
2. **Requêtes** : Structure riche, optimisée pour la lecture
3. **Index** : Différents pour chaque collection
4. **Sharding** : Stratégies différentes selon l'usage

### **Monitoring et Alertes**

1. **Lag de synchronisation** : < 1 seconde
2. **Taux d'erreur** : < 0.1%
3. **Performance** : Latence < 100ms
4. **Cohérence** : Vérification quotidienne

## 🎯 **Votre Prochaine Étape**

Maintenant que vous comprenez l'approche CQS avec MongoDB, quelle est votre situation ?

{{< chapter-nav >}}
  {{< chapter-option 
    letter="A" 
    color="green" 
    title="Je veux comprendre l'approche CQRS complète" 
    subtitle="Vous voulez voir comment ajouter CQRS à CQS"
    criteria="Équipe très expérimentée,Besoin de découplage complet,Architecture distribuée,Évolutivité maximale"
    time="35-45 minutes"
    chapter="30"
    chapter-title="Stockage MongoDB - Approche CQRS"
    chapter-url="/chapitres/stockage/mongodb/chapitre-30-stockage-mongodb-cqrs/"
  >}}
  
  {{< chapter-option 
    letter="B" 
    color="yellow" 
    title="Je veux voir l'approche Event Sourcing" 
    subtitle="Vous voulez comprendre comment stocker les événements"
    criteria="Audit trail critique,Debugging complexe,Équipe très expérimentée,Budget important"
    time="40-50 minutes"
    chapter="31"
    chapter-title="Stockage MongoDB - Event Sourcing"
    chapter-url="/chapitres/stockage/mongodb/chapitre-31-stockage-mongodb-event-sourcing/"
  >}}
  
  {{< chapter-option 
    letter="C" 
    color="red" 
    title="Je veux revenir à l'approche classique" 
    subtitle="Vous voulez une approche plus simple"
    criteria="Application simple,Équipe junior,Développement rapide,Cohérence forte requise"
    time="25-35 minutes"
    chapter="28"
    chapter-title="Stockage MongoDB - Approche Classique"
    chapter-url="/chapitres/stockage/mongodb/chapitre-28-stockage-mongodb-classique/"
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

**💡 Conseil** : Si vous n'êtes pas sûr, commencez par l'approche classique (option C) pour bien comprendre MongoDB, puis revenez à CQS quand vous serez prêt.

**🔄 Alternative** : Si vous voulez tout voir dans l'ordre, continuez avec l'approche CQRS (option A).
