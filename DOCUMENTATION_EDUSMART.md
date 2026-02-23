# 📚 Documentation Complète - EduSmart

## 🎯 Présentation du Projet

**EduSmart** est une plateforme de gestion académique complète développée avec **Symfony 7.x** et **PHP 8+**. Elle permet la gestion des étudiants, bulletins, certifications, examens et intègre une intelligence artificielle pour l'analyse des performances.

---

## 🏗️ Architecture Technique

### Stack Technologique
| Composant | Technologie |
|-----------|-------------|
| **Backend** | Symfony 7.x, PHP 8+ |
| **Base de données** | Doctrine ORM (MySQL/PostgreSQL) |
| **Templates** | Twig |
| **Frontend** | Bootstrap 5, CSS3 personnalisé, JavaScript vanilla |
| **AI Provider** | Groq (Llama 3.3 70B) via API OpenAI-compatible |
| **Email** | Symfony Mailer |
| **SMS** | Twilio |
| **PDF** | DomPDF |
| **QR Code** | Endroid QR Code |

### Structure des Dossiers
```
Projet-web-2/
├── assets/                 # Assets frontend (JS, CSS)
├── bin/                    # Binaires Symfony (console, phpunit)
├── config/                 # Configuration Symfony
│   ├── packages/           # Configuration des bundles
│   ├── routes/             # Routes
│   └── services.yaml       # Services DI
├── migrations/             # Migrations Doctrine
├── public/                 # Point d'entrée web
│   └── uploads/            # Fichiers uploadés (bulletins, certifications, produits)
├── src/
│   ├── Command/            # Commandes console
│   ├── Controller/         # Contrôleurs
│   │   ├── Admin/          # Contrôleurs administration
│   │   ├── Api/            # API REST
│   │   └── Student/        # Contrôleurs espace étudiant
│   ├── Entity/             # Entités Doctrine
│   ├── Form/               # Formulaires Symfony
│   ├── Repository/         # Repositories Doctrine
│   ├── Service/            # Services métier
│   └── Twig/               # Extensions Twig
├── templates/              # Templates Twig
│   ├── admin/              # Templates administration
│   ├── bulletin/           # Templates bulletins
│   ├── certification/      # Templates certifications
│   ├── student/            # Templates espace étudiant
│   └── *.html.twig         # Layouts principaux
├── tests/                  # Tests PHPUnit
├── translations/           # Fichiers de traduction
├── var/                    # Cache et logs
└── vendor/                 # Dépendances Composer
```

---

## 👥 Gestion des Utilisateurs

### Rôles Disponibles
| Rôle | Description | Accès |
|------|-------------|-------|
| `ROLE_ADMIN` | Administrateur/Professeur | Dashboard admin, gestion complète |
| `ROLE_USER` | Étudiant | Espace étudiant uniquement |

### Entité User (`src/Entity/User.php`)
```php
- id: int (auto-increment)
- email: string (unique)
- name: string (nom de famille)
- prenom: string
- password: string (hashé)
- role: string ('admin' ou 'etudiant')
- numtel: ?string (numéro de téléphone)
- createdAt: DateTimeImmutable
- updatedAt: ?DateTimeImmutable
```

### Sécurité (`config/packages/security.yaml`)
- Authentification par formulaire de login
- Hashage des mots de passe avec `password_hashers`
- Firewall principal avec `form_login`
- Contrôle d'accès par rôles

---

## 📋 Gestion des Bulletins

### Entité Bulletin (`src/Entity/Bulletin.php`)
```php
- id: int
- student: User (ManyToOne)
- academicYear: string (ex: "2025/2026")
- semester: string ("Semestre 1", "Semestre 2", "Annuel")
- average: float (moyenne générale)
- mention: string ("Très Bien", "Bien", "Assez Bien", "Passable", "Insuffisant")
- classRank: ?int (rang dans la classe)
- status: string ("Brouillon", "Vérifié", "Validé", "Publié")
- pdfPath: ?string (chemin vers le PDF généré)
- revoked: bool
- revokedAt: ?DateTimeImmutable
- revokedBy: ?User
- revokedReason: ?string
- reportCardLines: Collection<ReportCardLine>
- createdAt: DateTimeImmutable
- updatedAt: ?DateTimeImmutable
```

### Workflow des Statuts (Automatique)
```
┌─────────────┐    Vérifier    ┌──────────┐    Valider    ┌─────────┐    Publier    ┌─────────┐
│  Brouillon  │ ────────────► │  Vérifié │ ────────────► │  Validé │ ────────────► │  Publié │
└─────────────┘                └──────────┘                └─────────┘                └─────────┘
      ▲                                                                                    │
      │                              Révoquer (avec raison)                                │
      └────────────────────────────────────────────────────────────────────────────────────┘
```

**⚠️ Important**: Le statut n'est plus modifiable manuellement dans le formulaire. Il change uniquement via les boutons de workflow (Vérifier, Valider, Publier, Révoquer).

### Routes Bulletins
| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/bulletin` | GET | Liste des bulletins |
| `/admin/bulletin/new` | GET/POST | Créer un bulletin |
| `/admin/bulletin/{id}` | GET | Détails d'un bulletin |
| `/admin/bulletin/{id}/edit` | GET/POST | Modifier un bulletin |
| `/admin/bulletin/{id}/verify` | POST | Passer en "Vérifié" |
| `/admin/bulletin/{id}/validate` | POST | Passer en "Validé" |
| `/admin/bulletin/{id}/publish` | POST | Passer en "Publié" |
| `/admin/bulletin/{id}/revoke` | POST | Révoquer le bulletin |
| `/admin/bulletin/{id}/generate-pdf` | POST | Générer le PDF |
| `/admin/bulletin/{id}/pdf` | GET | Télécharger le PDF |
| `/admin/bulletin/{id}/send-sms` | POST | Envoyer SMS à l'étudiant |
| `/admin/bulletin/{id}/send-email` | POST | Envoyer Email à l'étudiant |

### Recherche Dynamique
La liste des bulletins dispose d'une **recherche dynamique** en temps réel:
- Filtrage instantané pendant la frappe (nom, prénom, email, année)
- Compteur mis à jour dynamiquement
- Soumission du formulaire avec la touche Entrée pour une recherche serveur

---

## 🎓 Gestion des Certifications

### Entité Certification (`src/Entity/Certification.php`)
```php
- id: int
- student: User (ManyToOne)
- type: string ("SCOLARITE", "REUSSITE", "NOTES", "DIPLOME", "STAGE", "PRESENCE")
- uniqueNumber: string (numéro unique généré)
- verificationCode: string (code de vérification)
- hmacSignature: string (signature HMAC pour sécurité)
- qrCodePath: ?string (chemin vers le QR code)
- pdfPath: ?string (chemin vers le PDF)
- status: string ("ACTIVE", "REVOKED", "EXPIRED")
- issuedAt: DateTimeImmutable
- validUntil: ?DateTimeImmutable
- revoked: bool
- revokedAt: ?DateTimeImmutable
- revokedBy: ?User
- revokedReason: ?string
```

### Types de Certifications
| Code | Label |
|------|-------|
| `SCOLARITE` | Attestation de scolarité |
| `REUSSITE` | Certificat de réussite |
| `NOTES` | Relevé de notes |
| `DIPLOME` | Diplôme interne |
| `STAGE` | Attestation de stage |
| `PRESENCE` | Attestation de présence |

### Workflow des Statuts (Automatique)
```
┌────────┐                    ┌─────────┐
│ ACTIVE │ ──── Révoquer ───► │ REVOKED │
└────────┘                    └─────────┘
     │
     │ (expiration automatique)
     ▼
┌─────────┐
│ EXPIRED │
└─────────┘
```

**⚠️ Important**: Le statut est défini automatiquement à "ACTIVE" lors de la création. Il peut être révoqué via le bouton de révocation.

### Routes Certifications
| Route | Méthode | Description |
|-------|---------|-------------|
| `/admin/certification` | GET | Liste des certifications |
| `/admin/certification/new` | GET/POST | Créer une certification |
| `/admin/certification/{id}` | GET | Détails |
| `/admin/certification/{id}/edit` | GET/POST | Modifier |
| `/admin/certification/{id}/revoke` | POST | Révoquer |
| `/admin/certification/{id}/generate-pdf` | POST | Générer PDF |
| `/admin/certification/{id}/send-sms` | POST | Envoyer SMS |
| `/admin/certification/{id}/send-email` | POST | Envoyer Email |

### Vérification Publique
| Route | Description |
|-------|-------------|
| `/verify` | Page de vérification publique |
| `/verify/{code}` | Vérification par code |
| `/api/verify/{code}` | API de vérification JSON |

### Recherche Dynamique
La liste des certifications dispose d'une **recherche dynamique** en temps réel:
- Filtrage instantané (nom, prénom, email, numéro unique, type)
- Compteur mis à jour dynamiquement
- Soumission avec Entrée

---

## 🤖 Intelligence Artificielle (Groq AI)

### Configuration
Le service AI utilise **Groq** avec le modèle **Llama 3.3 70B Versatile** (gratuit).

**Variables d'environnement (`.env`):**
```env
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Service AI (`src/Service/AiService.php`)
```php
class AiService
{
    // Constructeur — injecte BulletinRepository, CertificationRepository, UserRepository, GradeRepository, CourseRepository
    
    // === ANALYSE ===
    public function analyzeStudentPerformance(User $student): array  // Analyse individuelle
    public function compareStudents(array $studentIds): array        // Comparaison de 2+ étudiants
    public function generateRecommendations(User $student): array    // Recommandations personnalisées
    public function analyzeClassTrends(string $year, string $semester): array // Tendances de classe
    
    // === CHATBOT ===
    public function chat(User $student, string $message, array $history): array // Chat spécifique étudiant
    public function chatGeneral(string $message, array $history): array         // Chat général (indépendant)
    
    // === PRÉDICTION & ANOMALIES (Nouveau 22/02/2026) ===
    public function predictSuccess(): array            // Prédiction de réussite pour tous les étudiants
    public function detectAnomalies(): array            // Détection d'anomalies dans les notes
    
    // === CERTIFICATIONS AI (Nouveau 22/02/2026) ===
    public function auditCertifications(): array        // Bilan complet des certifications émises
    public function suggestCertifications(): array      // Suggestions de certifications éligibles
    
    // === MÉTHODES PRIVÉES ===
    private function callAI(string $prompt, string $systemPrompt): string  // Appel Groq avec retry/rate-limit
    private function gatherDatabaseContext(): string    // Contexte DB complet pour le chatbot
    private function prepareStudentData(User $student): array
    private function prepareClassData(array $bulletins): array
    private function calculateProgression(array $averages): array
    private function calculateStdDev(array $values): float
}
```

### Fonctionnalités AI

#### 1. Analyse Individuelle (Admin)
- Analyse automatique des bulletins de l'étudiant
- Identification des points forts et faiblesses
- Recommandations personnalisées
- Score global sur 100

#### 2. Tendances de Classe (Admin)
- Analyse globale par année académique / semestre
- Moyenne de classe, écart-type, plus haute/basse notes
- Recommandations pour la classe

#### 3. Comparaison d'Étudiants (Admin)
- Comparaison de 2+ étudiants sélectionnés
- Analyse comparative des performances

#### 4. Chatbot AI Général (Admin)
- **Indépendant** de la sélection d'étudiant (découplé le 22/02/2026)
- **Contexte base de données** : Le chatbot a accès à toutes les données réelles (étudiants, bulletins, certifications, notes, cours) via `gatherDatabaseContext()`
- Historique de conversation maintenu
- Restreint aux questions éducatives

#### 5. Prédiction de Réussite (Nouveau 22/02/2026)
- Analyse l'ensemble des bulletins de tous les étudiants
- Prédit la probabilité de réussite (en %)
- Indique le niveau de risque : ✅ Faible / ⚠️ Moyen / 🔴 Élevé
- Tendance : En progression / Stable / En régression
- Actions prioritaires recommandées
- Résumé global de la classe

#### 6. Détection d'Anomalies (Nouveau 22/02/2026)
- Audit automatique de tous les bulletins
- Détecte les écarts suspects (CC élevé mais Exam bas)
- Identifie les chutes brutales entre périodes
- Signale les incohérences (moyennes, mentions)
- Repère les notes extrêmes (0 ou 20)
- Patterns suspects (notes identiques sur plusieurs modules)

#### 7. Bilan Global des Certifications (Nouveau 22/02/2026)
- Audit complet de toutes les certifications émises
- Statistiques : total, répartition par type et par statut
- Couverture étudiante (% d'étudiants certifiés)
- Cohérence certifications de réussite vs moyennes
- Alertes : certifications révoquées, expirées, étudiants sans certification
- Indicateurs de qualité

#### 8. Suggestions de Certifications (Nouveau 22/02/2026)
- Analyse chaque étudiant par rapport aux 6 types de certifications
- Compare les certifications existantes vs éligibles
- Priorité : Haute / Moyenne / Basse
- Justification basée sur les notes et la moyenne
- Plan d'action global pour les certifications manquantes

#### 9. Analyse AI Étudiant
L'espace étudiant dispose d'une page dédiée (`/student/ai`) avec:
- **Analyse de Performance**: Analyse complète des bulletins
- **Chatbot AI**: Discussion interactive pour conseils personnalisés
- Interface premium avec design moderne

### Routes AI
| Route | Méthode | Accès | Description |
|-------|---------|-------|-------------|
| `/admin/ai/` | GET | Admin | Page principale analyse AI |
| `/admin/ai/analyze/{studentId}` | GET | Admin | Analyser un étudiant |
| `/admin/ai/recommendations/{studentId}` | GET | Admin | Recommandations personnalisées |
| `/admin/ai/class-analysis` | GET | Admin | Tendances de classe (params: academic_year, semester) |
| `/admin/ai/compare` | POST | Admin | Comparer des étudiants (body: student_ids[]) |
| `/admin/ai/chat` | POST | Admin | Chat AI général (body: message, history) |
| `/admin/ai/predict-success` | GET | Admin | Prédiction de réussite (tous les étudiants) |
| `/admin/ai/detect-anomalies` | GET | Admin | Détection d'anomalies (tous les bulletins) |
| `/admin/ai/certification-overview` | GET | Admin | Bilan global des certifications |
| `/admin/ai/suggest-certifications` | GET | Admin | Suggestions de certifications éligibles |
| `/student/ai` | GET | Étudiant | Page AI étudiant |
| `/student/ai/analyze` | GET | Étudiant | Lancer l'analyse |
| `/student/ai/chat` | POST | Étudiant | Endpoint chatbot |

---

## 📧 Services de Notification

### EmailService (`src/Service/EmailService.php`)
```php
class EmailService
{
    // Envoi d'email pour bulletin
    public function sendBulletinEmail(Bulletin $bulletin): void
    
    // Envoi d'email pour certification
    public function sendCertificationEmail(Certification $certification): void
}
```

**Configuration (`.env`):**
```env
MAILER_DSN=smtp://user:pass@smtp.example.com:587
```

### SmsService (`src/Service/SmsService.php`)
```php
class SmsService
{
    // Notification bulletin prêt
    public function notifyBulletinReady(Bulletin $bulletin): array
    
    // Notification certification prête
    public function notifyCertificationReady(Certification $certification): array
    
    // Vérifie si Twilio est configuré
    public function isConfigured(): bool
}
```

**Configuration (`.env`):**
```env
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_PHONE_NUMBER=+1234567890
```

### Boutons de Notification
Sur les pages de détails des bulletins et certifications:
- **Bouton SMS** (violet): Visible si statut publié/actif ET étudiant a un numéro
- **Bouton Email** (bleu): Visible si statut publié/actif ET étudiant a un email

---

## 📄 Génération PDF

### PdfGeneratorService (`src/Service/PdfGeneratorService.php`)
```php
class PdfGeneratorService
{
    // Génération PDF bulletin avec template EduSmart
    public function generateBulletinPdf(Bulletin $bulletin, string $baseUrl): string
    
    // Génération PDF certification avec QR code
    public function generateCertificationPdf(Certification $certification): string
}
```

### Caractéristiques des PDF
- **Design EduSmart** premium avec logo et couleurs métier
- **QR Code** intégré pour vérification rapide
- **Signature HMAC** pour authenticité
- **Code de vérification** unique

---

## 🔐 Sécurité et Audit

### HmacService (`src/Service/HmacService.php`)
Génère des signatures HMAC pour l'authenticité des documents:
```php
public function generateSignature(string $data): string
public function verifySignature(string $data, string $signature): bool
```

### AuditService (`src/Service/AuditService.php`)
Enregistre toutes les actions sur les documents:
```php
public function log(string $entityType, int $entityId, string $action, ?User $user): void
```

### Entité AuditLog
```php
- id: int
- entityType: string ("Bulletin", "Certification")
- entityId: int
- action: string ("CREATED", "UPDATED", "VERIFIED", "VALIDATED", "PUBLISHED", "REVOKED", "DELETED")
- performedBy: ?User
- performedAt: DateTimeImmutable
- details: ?array (JSON)
```

---

## 🛒 Boutique (Shop)

### Entités
- **Product**: Produits disponibles (merchandising EduSmart)
- **Category**: Catégories de produits
- **Cart/CartItem**: Panier d'achat

### Routes Boutique Étudiant
| Route | Description |
|-------|-------------|
| `/student/shop` | Catalogue produits |
| `/student/shop/product/{id}` | Détail produit |
| `/student/shop/cart` | Panier |
| `/student/shop/cart/add/{id}` | Ajouter au panier |

---

## 📝 Examens

### Entité Exam
```php
- id: int
- title: string
- description: ?string
- date: DateTimeImmutable
- duration: int (minutes)
- coefficient: float
- course: ?Course
- students: Collection<User>
```

### Routes Examens
| Route | Description |
|-------|-------------|
| `/admin/exam` | Gestion des examens |
| `/student/exams` | Examens de l'étudiant |

---

## � Mode Sombre (Dark Mode)

### Implémentation
Le mode sombre est disponible dans le layout administration (`admin_layout.html.twig`) via un toggle thème.

### Stockage
Le thème est sauvegardé en `localStorage` sous la clé `admin-theme`.

### Couverture CSS
Les overrides `[data-theme="dark"]` couvrent:
- `.text-dark` → `#E2E8F0`
- Titres `h1`-`h6` → `#F1F5F9`
- Paragraphes et labels → `#CBD5E1`
- Formulaires (`.form-control`, `.form-select`) → fond sombre + texte clair
- Tableaux → lignes alternées sombres
- Cartes et modals → fonds `#1E293B`
- Chat (`.chat-container`, `.bubble`) → fonds adaptés
- Badges, dropdowns, couleurs inline hardcodées
- Alertes et inputs de recherche

---

## �🎨 Interface Utilisateur

### Layouts Principaux
| Fichier | Usage |
|---------|-------|
| `base.html.twig` | Layout de base |
| `admin_layout.html.twig` | Layout administration |
| `student_layout.html.twig` | Layout espace étudiant |

### Design System
- **Couleurs principales**: `#3B49A2` (EduSmart Blue), `#6366F1` (Indigo)
- **Gradients**: Utilisés pour les headers et badges
- **Animations**: Fade-in, slide-in pour les listes
- **Cards Premium**: Avec effet glass et ombres douces

### Favicon
Le favicon EduSmart (lettre "E" violette) est présent sur toutes les pages:
```html
<link rel="icon" href="data:image/svg+xml,<svg>...</svg>">
```

### Navigation Étudiant
La sidebar étudiant affiche:
- Dashboard
- Examens
- Bulletins
- Certifications
- Mon Analyse AI
- Boutique
- Mon Panier
- **Mode Professeur** (visible uniquement si `ROLE_ADMIN`)
- Déconnexion

---

## ⚙️ Configuration

### Variables d'Environnement (`.env`)
```env
# Application
APP_ENV=dev
APP_SECRET=your-secret-key

# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/edusmart?serverVersion=8.0"

# Mailer
MAILER_DSN=smtp://localhost:1025

# Twilio SMS
TWILIO_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_PHONE_NUMBER=+1234567890

# AI (Groq - Gratuit)
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Commandes Console Utiles
```bash
# Vider le cache
php bin/console cache:clear

# Migrations
php bin/console doctrine:migrations:migrate

# Créer un utilisateur admin
php bin/console app:create-admin

# Lancer le serveur
symfony server:start
```

---

## 📊 Résumé des Fonctionnalités

### ✅ Fonctionnalités Implémentées
| Fonctionnalité | Status |
|----------------|--------|
| Authentification multi-rôles | ✅ |
| Gestion des bulletins avec workflow | ✅ |
| Gestion des certifications | ✅ |
| Génération PDF professionnels | ✅ |
| QR Codes et vérification | ✅ |
| Signature HMAC | ✅ |
| Audit Log complet | ✅ |
| Notifications Email | ✅ |
| Notifications SMS (Twilio) | ✅ |
| Intelligence Artificielle (Groq) | ✅ |
| Chatbot AI étudiant | ✅ |
| Recherche dynamique | ✅ |
| Boutique en ligne | ✅ |
| Gestion des examens | ✅ |
| Interface responsive premium | ✅ |
| Favicon EduSmart | ✅ |
| Mode sombre (Dark Mode) | ✅ |
| Chatbot AI découplé avec contexte DB | ✅ |
| Prédiction de réussite AI | ✅ |
| Détection d'anomalies AI | ✅ |
| Bilan global certifications AI | ✅ |
| Suggestions certifications AI | ✅ |

### 🔄 Modifications Récentes (20/02/2026)
1. **Statut automatique**: Le champ statut a été retiré des formulaires bulletin/certification. Il change uniquement via les boutons workflow.
2. **Bouton Email**: Ajout d'un bouton Email à côté du bouton SMS pour les bulletins et certifications.
3. **Recherche dynamique**: Filtrage en temps réel sur les listes bulletins et certifications.
4. **Correction Mode Professeur**: Le bouton n'apparaît plus pour les étudiants.
5. **Simplification header AI**: Retrait du logo redondant de la page AI étudiant.

### 🔄 Modifications Récentes (22/02/2026)
1. **Chatbot AI découplé**: Le chatbot n'est plus lié à la sélection d'un étudiant. Nouvelle méthode `chatGeneral()` indépendante, route changée de `/chat/{studentId}` à `/chat`.
2. **Contexte base de données dans le chatbot**: Le chatbot a désormais accès à toutes les données réelles de la plateforme (étudiants, bulletins, certifications, notes, cours) via `gatherDatabaseContext()`. Il peut répondre à des questions comme "Quels étudiants ont la meilleure moyenne ?".
3. **Correction dark mode**: Correction complète du texte illisible en mode sombre. Ajout de 200+ lignes de CSS dans `admin_layout.html.twig` sous `[data-theme="dark"]` couvrant : `.text-dark`, titres, paragraphes, formulaires, tableaux, cartes, chat, badges, modals, dropdowns, couleurs inline.
4. **Prédiction de Réussite AI**: Nouvelle fonctionnalité qui analyse tous les étudiants et prédit leur probabilité de réussite avec niveau de risque et tendance.
5. **Détection d'Anomalies AI**: Audit automatique de tous les bulletins pour détecter les incohérences, écarts suspects, chutes brutales et patterns anormaux.
6. **Bilan Global des Certifications AI**: Audit complet des certifications émises — statistiques par type/statut, couverture étudiante, cohérence avec les moyennes, alertes.
7. **Suggestions de Certifications AI**: Recommandation des certifications éligibles pour chaque étudiant en comparant avec les 6 types disponibles (SCOLARITE, REUSSITE, NOTES, DIPLOME, STAGE, PRESENCE).
8. **Interface AI enrichie**: 2 nouvelles cartes premium sur la page Analyse AI ("Prédiction & Anomalies" et "Outils Certifications") avec boutons stylisés et fonctions d'affichage dédiées.

---

## 🚀 Déploiement

### Prérequis
- PHP 8.1+
- Composer
- MySQL 8.0+ ou PostgreSQL
- Node.js (pour les assets)

### Installation
```bash
# Cloner le projet
git clone <repository>
cd Projet-web-2

# Installer les dépendances
composer install

# Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos paramètres

# Créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Lancer le serveur
symfony server:start
```

---

## 📞 Support

Pour toute question technique concernant EduSmart:
- Consultez cette documentation
- Vérifiez les logs dans `var/log/`
- Utilisez le mode debug Symfony (`APP_ENV=dev`)

---

**Documentation mise à jour le 22 Février 2026**
**Version EduSmart: 2.1**
