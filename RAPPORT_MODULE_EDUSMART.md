# 📘 RAPPORT DE MODULE - PROJET WEB
## Plateforme de Gestion Académique EduSmart

---

**Établissement:** ESPRIT - École Supérieure Privée d'Ingénierie et de Technologies  
**Module:** Développement Web Avancé  
**Année Académique:** 2025/2026  
**Date de Soumission:** 20 Février 2026  

**Réalisé par:** Rachid GHARBI  
**Email:** rachid.gharbi145@outlook.com  

---

## 📑 Table des Matières

1. [Introduction](#1-introduction)
2. [Objectifs du Projet](#2-objectifs-du-projet)
3. [Technologies Utilisées](#3-technologies-utilisées)
4. [Architecture du Projet](#4-architecture-du-projet)
5. [Modèle de Données](#5-modèle-de-données)
6. [Fonctionnalités Implémentées](#6-fonctionnalités-implémentées)
7. [Services Métier](#7-services-métier)
8. [Interface Utilisateur](#8-interface-utilisateur)
9. [Sécurité](#9-sécurité)
10. [Tests et Validation](#10-tests-et-validation)
11. [Améliorations Apportées](#11-améliorations-apportées)
12. [Conclusion](#12-conclusion)
13. [Annexes](#13-annexes)

---

## 1. Introduction

### 1.1 Contexte
Dans le cadre de la digitalisation des établissements d'enseignement supérieur, la gestion des documents académiques (bulletins, certifications, attestations) représente un enjeu majeur. Les processus traditionnels basés sur le papier sont lents, sujets aux erreurs et difficiles à vérifier.

### 1.2 Problématique
Comment créer une plateforme centralisée permettant:
- La gestion automatisée des bulletins et certifications
- La vérification instantanée de l'authenticité des documents
- L'analyse intelligente des performances étudiantes
- La communication multicanale avec les étudiants

### 1.3 Solution Proposée
**EduSmart** est une plateforme web complète développée avec Symfony 7, intégrant:
- Un système de workflow pour la validation des documents
- Une signature numérique HMAC pour l'authenticité
- Une intelligence artificielle pour l'analyse des performances
- Des notifications multicanales (Email, SMS)

---

## 2. Objectifs du Projet

### 2.1 Objectifs Fonctionnels
| # | Objectif | Statut |
|---|----------|--------|
| 1 | Gestion complète des utilisateurs (admin/étudiant) | ✅ Réalisé |
| 2 | Création et gestion des bulletins avec workflow | ✅ Réalisé |
| 3 | Émission de certifications vérifiables | ✅ Réalisé |
| 4 | Génération de PDF professionnels | ✅ Réalisé |
| 5 | Système de vérification par QR code | ✅ Réalisé |
| 6 | Notifications Email et SMS | ✅ Réalisé |
| 7 | Intelligence Artificielle pour analyse | ✅ Réalisé |
| 8 | Espace étudiant dédié | ✅ Réalisé |
| 9 | Boutique en ligne | ✅ Réalisé |
| 10 | Audit log complet | ✅ Réalisé |

### 2.2 Objectifs Techniques
- Architecture MVC avec Symfony 7
- Base de données relationnelle avec Doctrine ORM
- API RESTful pour les intégrations
- Interface responsive et moderne
- Sécurité renforcée (CSRF, hashage, HMAC)

---

## 3. Technologies Utilisées

### 3.1 Backend
| Technologie | Version | Usage |
|-------------|---------|-------|
| PHP | 8.2+ | Langage serveur |
| Symfony | 7.x | Framework MVC |
| Doctrine ORM | 3.x | Mapping objet-relationnel |
| Twig | 3.x | Moteur de templates |

### 3.2 Frontend
| Technologie | Version | Usage |
|-------------|---------|-------|
| Bootstrap | 5.3 | Framework CSS |
| Font Awesome | 6.x | Icônes |
| JavaScript | ES6+ | Interactivité |
| CSS3 | - | Styles personnalisés |

### 3.3 Services Externes
| Service | Usage |
|---------|-------|
| **Groq AI** | Intelligence artificielle (Llama 3.3 70B) |
| **Twilio** | Envoi de SMS |
| **Symfony Mailer** | Envoi d'emails |
| **DomPDF** | Génération de PDF |
| **Endroid QR Code** | Génération de QR codes |

### 3.4 Outils de Développement
- **Composer**: Gestion des dépendances PHP
- **Git**: Versioning
- **VS Code**: IDE
- **Symfony CLI**: Serveur de développement

---

## 4. Architecture du Projet

### 4.1 Structure MVC
```
┌─────────────────────────────────────────────────────────────────┐
│                         NAVIGATEUR                               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CONTRÔLEURS (src/Controller/)              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ BulletinCtrl │  │ CertifCtrl   │  │ StudentCtrl  │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      SERVICES (src/Service/)                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ AiService    │  │ PdfService   │  │ EmailService │          │
│  │ SmsService   │  │ HmacService  │  │ AuditService │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      ENTITÉS (src/Entity/)                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │ User         │  │ Bulletin     │  │ Certification│          │
│  │ ReportLine   │  │ AuditLog     │  │ Product      │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BASE DE DONNÉES (MySQL)                       │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Structure des Dossiers
```
Projet-web-2/
├── config/
│   ├── packages/          # Configuration bundles
│   ├── routes/            # Définition des routes
│   └── services.yaml      # Injection de dépendances
├── migrations/            # Migrations Doctrine
├── public/
│   ├── index.php          # Point d'entrée
│   └── uploads/           # Fichiers générés
├── src/
│   ├── Controller/
│   │   ├── Admin/         # Contrôleurs admin
│   │   ├── Api/           # API REST
│   │   └── Student/       # Contrôleurs étudiant
│   ├── Entity/            # Modèles de données
│   ├── Form/              # Formulaires
│   ├── Repository/        # Requêtes personnalisées
│   ├── Service/           # Logique métier
│   └── Twig/              # Extensions Twig
├── templates/
│   ├── admin/             # Vues administration
│   ├── bulletin/          # Vues bulletins
│   ├── certification/     # Vues certifications
│   └── student/           # Vues étudiant
└── var/
    ├── cache/             # Cache applicatif
    └── log/               # Fichiers de log
```

---

## 5. Modèle de Données

### 5.1 Diagramme Entité-Relation (Simplifié)
```
┌──────────────┐       ┌──────────────┐       ┌──────────────────┐
│     USER     │       │   BULLETIN   │       │  REPORT_CARD_LINE│
├──────────────┤       ├──────────────┤       ├──────────────────┤
│ id           │◄──┐   │ id           │◄──────│ id               │
│ email        │   │   │ student_id   │───────│ bulletin_id      │
│ name         │   │   │ academicYear │       │ subject          │
│ prenom       │   │   │ semester     │       │ grade            │
│ password     │   │   │ average      │       │ coefficient      │
│ role         │   │   │ mention      │       │ teacherComment   │
│ numtel       │   │   │ classRank    │       └──────────────────┘
│ createdAt    │   │   │ status       │
└──────────────┘   │   │ pdfPath      │
       │           │   │ revoked      │
       │           │   │ createdAt    │
       │           │   └──────────────┘
       │           │
       │           │   ┌──────────────┐
       │           └───│ CERTIFICATION│
       │               ├──────────────┤
       │               │ id           │
       └───────────────│ student_id   │
                       │ type         │
                       │ uniqueNumber │
                       │ verifyCode   │
                       │ hmacSignature│
                       │ qrCodePath   │
                       │ pdfPath      │
                       │ status       │
                       │ issuedAt     │
                       │ validUntil   │
                       │ revoked      │
                       └──────────────┘
```

### 5.2 Entité User
```php
#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private ?string $role = 'etudiant'; // 'admin' ou 'etudiant'

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numtel = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;
}
```

### 5.3 Entité Bulletin
```php
#[ORM\Entity(repositoryClass: BulletinRepository::class)]
class Bulletin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\Column(length: 20)]
    private ?string $academicYear = null; // "2025/2026"

    #[ORM\Column(length: 20)]
    private ?string $semester = null; // "Semestre 1", "Semestre 2", "Annuel"

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $average = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $mention = null;

    #[ORM\Column(nullable: true)]
    private ?int $classRank = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'Brouillon'; // Brouillon → Vérifié → Validé → Publié

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column]
    private bool $revoked = false;

    #[ORM\OneToMany(mappedBy: 'bulletin', targetEntity: ReportCardLine::class, cascade: ['persist', 'remove'])]
    private Collection $reportCardLines;
}
```

### 5.4 Entité Certification
```php
#[ORM\Entity(repositoryClass: CertificationRepository::class)]
class Certification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // SCOLARITE, REUSSITE, NOTES, DIPLOME, STAGE, PRESENCE

    #[ORM\Column(length: 50, unique: true)]
    private ?string $uniqueNumber = null;

    #[ORM\Column(length: 20)]
    private ?string $verificationCode = null;

    #[ORM\Column(length: 255)]
    private ?string $hmacSignature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $qrCodePath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfPath = null;

    #[ORM\Column(length: 20)]
    private ?string $status = 'ACTIVE'; // ACTIVE, REVOKED, EXPIRED

    #[ORM\Column]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column]
    private bool $revoked = false;
}
```

---

## 6. Fonctionnalités Implémentées

### 6.1 Authentification et Autorisation

#### 6.1.1 Système de Login
- Formulaire de connexion sécurisé
- Hashage des mots de passe (bcrypt)
- Protection CSRF
- Redirection selon le rôle

#### 6.1.2 Rôles et Permissions
| Rôle | Permissions |
|------|-------------|
| `ROLE_ADMIN` | Accès complet au dashboard admin, gestion bulletins/certifications/examens/utilisateurs |
| `ROLE_USER` | Accès espace étudiant, consultation bulletins/certifications, boutique, AI |

#### 6.1.3 Contrôle d'Accès
```yaml
# config/packages/security.yaml
access_control:
    - { path: ^/admin, roles: ROLE_ADMIN }
    - { path: ^/student, roles: ROLE_USER }
```

### 6.2 Gestion des Bulletins

#### 6.2.1 Workflow Automatique des Statuts
```
BROUILLON ──► VÉRIFIÉ ──► VALIDÉ ──► PUBLIÉ
     ▲                                  │
     └────────── RÉVOQUÉ ◄──────────────┘
```

**Important:** Le statut n'est plus modifiable manuellement. Il change uniquement via les boutons d'action:
- `Vérifier`: Brouillon → Vérifié
- `Valider`: Vérifié → Validé  
- `Publier`: Validé → Publié
- `Révoquer`: Tout statut → Révoqué (avec raison obligatoire)

#### 6.2.2 Fonctionnalités Bulletins
- Création avec lignes de notes (matières)
- Calcul automatique de la moyenne
- Attribution de la mention
- Génération PDF avec template EduSmart
- Envoi automatique par email lors de la génération PDF
- Envoi manuel SMS/Email
- Recherche dynamique en temps réel
- Tri par multiple critères

#### 6.2.3 Routes Bulletins
| Route | Méthode | Action |
|-------|---------|--------|
| `/admin/bulletin` | GET | Liste avec recherche dynamique |
| `/admin/bulletin/new` | GET/POST | Création |
| `/admin/bulletin/{id}` | GET | Détails |
| `/admin/bulletin/{id}/edit` | GET/POST | Modification |
| `/admin/bulletin/{id}/verify` | POST | Workflow: Vérifier |
| `/admin/bulletin/{id}/validate` | POST | Workflow: Valider |
| `/admin/bulletin/{id}/publish` | POST | Workflow: Publier |
| `/admin/bulletin/{id}/revoke` | POST | Workflow: Révoquer |
| `/admin/bulletin/{id}/generate-pdf` | POST | Générer PDF + Email auto |
| `/admin/bulletin/{id}/pdf` | GET | Télécharger PDF |
| `/admin/bulletin/{id}/send-sms` | POST | Envoyer SMS manuellement |
| `/admin/bulletin/{id}/send-email` | POST | Envoyer Email manuellement |

### 6.3 Gestion des Certifications

#### 6.3.1 Types de Certifications
| Code | Libellé |
|------|---------|
| `SCOLARITE` | Attestation de scolarité |
| `REUSSITE` | Certificat de réussite |
| `NOTES` | Relevé de notes |
| `DIPLOME` | Diplôme interne |
| `STAGE` | Attestation de stage |
| `PRESENCE` | Attestation de présence |

#### 6.3.2 Workflow Automatique
```
ACTIVE ──────► REVOKED (via bouton révocation)
   │
   └─────────► EXPIRED (automatique si validUntil dépassé)
```

**Important:** Le statut est défini à "ACTIVE" automatiquement à la création.

#### 6.3.3 Sécurité des Certifications
- **Numéro unique**: Généré automatiquement (format: CERT-YYYYMMDD-XXXXX)
- **Code de vérification**: 8 caractères alphanumériques
- **Signature HMAC**: SHA-256 pour garantir l'intégrité
- **QR Code**: Lien vers la page de vérification publique

#### 6.3.4 Vérification Publique
- Page `/verify`: Saisie du code de vérification
- Page `/verify/{code}`: Affichage du résultat
- API `/api/verify/{code}`: Réponse JSON pour intégrations

### 6.4 Intelligence Artificielle (Groq)

#### 6.4.1 Configuration
```env
# .env
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

#### 6.4.2 Service AiService
```php
class AiService
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';

    public function analyzeStudentPerformance(User $student): array
    {
        // Analyse des bulletins et génération de recommandations
    }

    public function chat(string $message, array $context = []): string
    {
        // Chat interactif avec contexte étudiant
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
```

#### 6.4.3 Fonctionnalités AI
| Fonctionnalité | Description |
|----------------|-------------|
| Analyse de performance | Évaluation complète des bulletins |
| Points forts/faibles | Identification des matières |
| Recommandations | Conseils personnalisés |
| Chatbot | Discussion interactive |

#### 6.4.4 Espace Étudiant AI
- Page dédiée `/student/ai`
- Analyse automatique des performances
- Chatbot pour questions/conseils
- Interface premium avec design moderne

### 6.5 Notifications

#### 6.5.1 Service Email
```php
class EmailService
{
    public function sendBulletinEmail(Bulletin $bulletin): void
    {
        // Envoi email avec PDF en pièce jointe
    }

    public function sendCertificationEmail(Certification $certification): void
    {
        // Envoi email avec PDF en pièce jointe
    }
}
```

#### 6.5.2 Service SMS (Twilio)
```php
class SmsService
{
    public function notifyBulletinReady(Bulletin $bulletin): array
    {
        // Envoi SMS notification
    }

    public function notifyCertificationReady(Certification $certification): array
    {
        // Envoi SMS notification
    }

    public function isConfigured(): bool
    {
        return !empty($this->twilioSid) && !empty($this->twilioToken);
    }
}
```

#### 6.5.3 Boutons de Notification (Interface)
Sur les pages de détails:
- **Bouton SMS** (violet): Visible si statut publié/actif ET étudiant a un numéro
- **Bouton Email** (bleu): Visible si statut publié/actif ET étudiant a un email

### 6.6 Génération PDF

#### 6.6.1 Template Bulletin
- En-tête EduSmart avec logo
- Informations étudiant
- Tableau des matières avec notes
- Moyenne générale et mention
- QR code de vérification
- Signatures

#### 6.6.2 Template Certification
- Design officiel EduSmart
- Informations certification
- QR code de vérification
- Signature numérique HMAC
- Date de validité

### 6.7 Recherche Dynamique

#### 6.7.1 Bulletins
```javascript
// Filtrage en temps réel
searchInput.addEventListener('input', function() {
    const query = this.value.toLowerCase();
    rows.forEach(row => {
        const matches = name.includes(query) || email.includes(query);
        row.style.display = matches ? '' : 'none';
    });
});
```

**Champs recherchés**: Nom, prénom, email, année académique

#### 6.7.2 Certifications
**Champs recherchés**: Nom, prénom, email, numéro unique, type

#### 6.7.3 Comportement
- Filtrage instantané pendant la frappe
- Compteur mis à jour dynamiquement
- Touche Entrée = soumission formulaire (recherche serveur)

---

## 7. Services Métier

### 7.1 Liste des Services
| Service | Responsabilité |
|---------|----------------|
| `AiService` | Intégration Groq AI |
| `AuditService` | Journalisation des actions |
| `BulletinWorkflowService` | Gestion workflow bulletins |
| `EmailService` | Envoi d'emails |
| `HmacService` | Signatures numériques |
| `PdfGeneratorService` | Génération PDF |
| `SmsService` | Envoi SMS via Twilio |
| `VerificationCodeService` | Génération codes |

### 7.2 Injection de Dépendances
```yaml
# config/services.yaml
services:
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
```

---

## 8. Interface Utilisateur

### 8.1 Layouts
| Layout | Usage |
|--------|-------|
| `base.html.twig` | Layout de base |
| `admin_layout.html.twig` | Dashboard administration |
| `student_layout.html.twig` | Espace étudiant |

### 8.2 Design System
- **Couleur principale**: `#3B49A2` (EduSmart Blue)
- **Couleur secondaire**: `#6366F1` (Indigo)
- **Gradients**: Headers et badges
- **Cards**: Effet glass avec ombres
- **Animations**: Fade-in, slide-in

### 8.3 Favicon
Logo "E" violette présent sur toutes les pages:
```html
<link rel="icon" href="data:image/svg+xml,...">
```

### 8.4 Navigation Étudiant
| Menu | Route |
|------|-------|
| Dashboard | `/student/dashboard` |
| Examens | `/student/exams` |
| Bulletins | `/student/bulletins` |
| Certifications | `/student/certifications` |
| Mon Analyse AI | `/student/ai` |
| Boutique | `/student/shop` |
| Mon Panier | `/student/shop/cart` |
| Mode Professeur* | `/admin/dashboard` |
| Déconnexion | `/logout` |

*Visible uniquement si l'utilisateur a `ROLE_ADMIN`

---

## 9. Sécurité

### 9.1 Authentification
- Hashage bcrypt des mots de passe
- Protection CSRF sur tous les formulaires
- Sessions sécurisées

### 9.2 Autorisation
- Contrôle d'accès par rôles
- Vérification des permissions dans les contrôleurs
- Firewall Symfony

### 9.3 Intégrité des Documents
- Signature HMAC-SHA256
- QR codes de vérification
- Audit log de toutes les actions

### 9.4 Audit Log
```php
class AuditService
{
    public function log(
        string $entityType,    // "Bulletin", "Certification"
        int $entityId,
        string $action,        // "CREATED", "UPDATED", "PUBLISHED", etc.
        ?User $user
    ): void;
}
```

---

## 10. Tests et Validation

### 10.1 Tests Manuels Effectués
| Test | Résultat |
|------|----------|
| Création bulletin | ✅ |
| Workflow statuts | ✅ |
| Génération PDF | ✅ |
| Envoi Email | ✅ |
| Envoi SMS | ✅ |
| Analyse AI | ✅ |
| Chatbot étudiant | ✅ |
| Vérification QR | ✅ |
| Recherche dynamique | ✅ |
| Contrôle d'accès | ✅ |

### 10.2 Validation Sécurité
- Tentative d'accès admin par étudiant: ✅ Bloqué (403)
- Bouton "Mode Professeur" masqué pour étudiants: ✅
- CSRF protection: ✅ Active

---

## 11. Améliorations Apportées

### 11.1 Modifications du 19/02/2026
| # | Amélioration | Fichiers Modifiés |
|---|--------------|-------------------|
| 1 | Migration Gemini → Groq AI | `AiService.php`, `.env` |
| 2 | Correction page AI étudiant | `StudentAiController.php` |
| 3 | Styling premium page AI | `student/ai/index.html.twig` |
| 4 | Ajout chatbot étudiant | `StudentAiController.php`, template |
| 5 | Ajout favicon EduSmart | `base.html.twig`, layouts |

### 11.2 Modifications du 20/02/2026
| # | Amélioration | Fichiers Modifiés |
|---|--------------|-------------------|
| 1 | Statut bulletin automatique | `BulletinType.php`, templates formulaires |
| 2 | Statut certification automatique | `CertificationType.php`, templates |
| 3 | Ajout bouton Email (bulletins) | `BulletinController.php`, `show.html.twig` |
| 4 | Ajout bouton Email (certifications) | `CertificationController.php`, `show.html.twig` |
| 5 | Recherche dynamique bulletins | `bulletin/index.html.twig` |
| 6 | Recherche dynamique certifications | `certification/index.html.twig` |
| 7 | Masquer "Mode Professeur" aux étudiants | `student_layout.html.twig` |
| 8 | Simplification header page AI | `student/ai/index.html.twig` |

### 11.3 Détail des Changements Majeurs

#### Workflow Statut Automatique
**Avant:** Le professeur pouvait choisir manuellement le statut dans le formulaire.
**Après:** Le statut change uniquement via les boutons de workflow (Vérifier, Valider, Publier).

**Avantage:** Assure la cohérence du processus de validation et évite les erreurs.

#### Bouton Email
**Ajout:** Bouton "Email" à côté du bouton "SMS" sur les pages de détails.
**Comportement:** Visible seulement si le document est publié/actif et que l'étudiant a un email.

#### Recherche Dynamique
**Fonctionnement:**
1. L'utilisateur tape dans le champ de recherche
2. Le filtrage s'effectue instantanément (côté client)
3. Le compteur se met à jour
4. Touche Entrée = recherche serveur (pour pagination)

---

## 12. Conclusion

### 12.1 Bilan
Le projet **EduSmart** répond à tous les objectifs fixés:
- ✅ Gestion complète des documents académiques
- ✅ Workflow de validation sécurisé
- ✅ Vérification d'authenticité par QR code
- ✅ Intelligence artificielle intégrée
- ✅ Notifications multicanales
- ✅ Interface moderne et responsive

### 12.2 Points Forts
- Architecture propre et maintenable (Symfony)
- Sécurité renforcée (HMAC, audit)
- UX soignée avec feedback temps réel
- AI accessible et gratuite (Groq)

### 12.3 Perspectives d'Évolution
- Intégration calendrier académique
- Application mobile
- Système de notes en temps réel
- Tableau de bord analytics avancé

---

## 13. Annexes

### 13.1 Configuration Environnement
```env
# .env
APP_ENV=dev
APP_SECRET=your-secret-key
DATABASE_URL="mysql://user:pass@127.0.0.1:3306/edusmart"
MAILER_DSN=smtp://localhost:1025
TWILIO_SID=your-twilio-sid
TWILIO_AUTH_TOKEN=your-twilio-token
TWILIO_PHONE_NUMBER=+1234567890
GROQ_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxx
```

### 13.2 Commandes Utiles
```bash
# Installation
composer install

# Base de données
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Cache
php bin/console cache:clear

# Serveur
symfony server:start
```

### 13.3 Captures d'Écran
*[À ajouter selon besoin]*

---

**Fin du Rapport**

*Document généré le 20 Février 2026*
*Projet EduSmart - Version 2.0*
