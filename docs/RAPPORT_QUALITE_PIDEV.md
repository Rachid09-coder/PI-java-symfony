# Rapport qualité – PIDEV Sprint WEB (semaine du 02/03/2026)

Ce document décrit comment produire les **snapshots terminal** pour le suivi noté : tests unitaires et analyse statique PHPStan (avant / après optimisation).

---

## Commandes à lancer dans le terminal

Toutes les commandes sont à exécuter à la **racine du projet** (`PI-java-symfony`).

### 1. Snapshot « Défauts » (analyse statique PHPStan)

Affiche uniquement les **défauts détectés par PHPStan** (types, arguments, etc.) :

```bash
composer report:flaws
```

**À faire :**  
- **Avant optimisation** : lancer la commande → faire une **capture d’écran** du terminal (liste des erreurs).  
- **Après optimisation** : corriger le code selon les messages PHPStan, relancer `composer report:flaws` → refaire une **capture** (moins d’erreurs ou 0).

---

### 2. Snapshot « Rapport qualité complet »

Affiche **d’abord les défauts PHPStan**, puis **les tests unitaires** (PHPUnit) :

```bash
composer report:quality
```

Utile pour une seule capture qui montre à la fois l’analyse statique et les tests.

---

### 3. Tests unitaires seuls (liste lisible)

```bash
composer test:unit
```

Affiche la liste des tests avec le format **testdox** (noms de tests en clair). Idéal pour une capture « tous les tests passent ».

---

### 4. PHPStan seul (sans script)

Si vous voulez lancer PHPStan directement :

```bash
vendor\bin\phpstan analyse
```

Sous Linux/Mac : `vendor/bin/phpstan analyse`.

---

## Contenu du rapport à rendre

1. **Tests unitaires**  
   - Capture du terminal après `composer test:unit` (ou `composer report:quality`).  
   - Montrer que les tests existent et passent.

2. **Analyse statique PHPStan**  
   - **Snapshot « avant »** : sortie de `composer report:flaws` avec les erreurs affichées.  
   - **Snapshot « après »** : même commande après correction du code, montrant la réduction ou la disparition des erreurs.

3. **Résumé court**  
   - Nombre d’erreurs PHPStan avant / après.  
   - Nombre de tests unitaires et statut (OK / échecs).

---

## Fichiers concernés

| Fichier / dossier        | Rôle |
|--------------------------|------|
| `phpstan.neon.dist`      | Configuration PHPStan (niveau 6, dossiers `src/` et `tests/`) |
| `scripts/phpstan-flaws.php` | Script qui affiche le bandeau « Défauts PHPStan » puis lance PHPStan |
| `scripts/quality-report.php` | Script qui enchaîne PHPStan puis PHPUnit pour le rapport complet |
| `tests/Unit/`            | Tests unitaires (services et entités) |
| `composer report:flaws`  | Commande pour le snapshot « défauts » |
| `composer report:quality` | Commande pour le snapshot « rapport qualité » |

---

## Résumé des commandes

| Commande                | Usage pour le rapport |
|-------------------------|------------------------|
| `composer report:flaws` | Snapshot des **défauts** (avant / après optimisation) |
| `composer report:quality` | Snapshot **complet** (PHPStan + tests) |
| `composer test:unit`   | Snapshot des **tests unitaires** (liste testdox) |
