Spécifications Fonctionnelles : CRM Simple
1. Objectif du Projet
   Développer une application web CRM (Customer Relationship Management) simple pour une équipe de 3 personnes. L'application doit permettre le suivi des comptes clients/prospects, des actions à mener et de l'historique des interactions, tout en étant sécurisée et facile à utiliser.

2. Stack Technique
   Framework Backend : Symfony 7

Base de Données : PostgreSQL

Moteur de Templates : Twig

Framework CSS : Bootstrap 5 (intégré via CDN, sans Node.js/npm)

JavaScript : JavaScript natif (Vanilla JS) ou Stimulus pour l'interactivité.

3. Modèle de Données (Entités Doctrine)
   L'application s'articulera autour de quatre entités principales.

3.1. Entité User
Objectif : Représente un utilisateur de l'application (les 3 membres de l'équipe).

Champs :

id (Clé primaire, auto-incrémenté)

name (string, non nullable) : Nom complet de l'utilisateur.

email (string, unique, non nullable) : Utilisé comme identifiant de connexion.

roles (json, non nullable) : Rôles de sécurité (ex: ['ROLE_USER']).

password (string, non nullable) : Mot de passe hashé.

is_2fa_enabled (boolean, default: false) : Indique si la 2FA est activée.

secret_2fa (string, nullable) : Le secret pour l'authentification TOTP.

3.2. Entité Account
Objectif : Représente un client ou un prospect.

Champs :

id (Clé primaire, auto-incrémenté)

name (string, non nullable) : Nom de la société.

contact (string, non nullable) : Nom de la personne de contact.

priority (string, non nullable) : Niveau de priorité (ex: 'Haute', 'Moyenne', 'Basse').

nextStep (string, nullable) : Description de la prochaine action à mener.

Relations :

owner (ManyToOne vers User) : Le responsable du compte. Non nullable.

3.3. Entité Action
Objectif : Représente une tâche ou une interaction spécifique liée à un compte.

Champs :

id (Clé primaire, auto-incrémenté)

title (string, non nullable) : Titre de l'action (ex: "Appel de suivi").

type (string, non nullable) : Type d'interaction (ex: 'Appel', 'Email', 'RDV').

summary (text, nullable) : Résumé de l'interaction.

nextStepDate (datetime, nullable) : Date de la prochaine étape.

createdAt (datetime, non nullable) : Date de création de l'action.

Relations :

owner (ManyToOne vers User) : Le responsable de l'action. Non nullable.

account (ManyToOne vers Account) : Le compte auquel l'action est rattachée. Non nullable.

3.4. Entité History
Objectif : Trace un enregistrement de modification ou une note sur une Action.

Champs :

id (Clé primaire, auto-incrémenté)

note (text, non nullable) : La note ou le résumé de l'historique.

createdAt (datetime, non nullable) : Date de création de l'entrée d'historique.

Relations :

author (ManyToOne vers User) : L'utilisateur qui a créé l'entrée. Non nullable.

action (ManyToOne vers Action) : L'action concernée. Non nullable.

4. Fonctionnalités
   4.1. Sécurité et Authentification
   Connexion : Une page de connexion standard (/login) avec email et mot de passe.

Authentification à Deux Facteurs (2FA) : Après une connexion réussie, si la 2FA est activée pour l'utilisateur, il est redirigé vers une page pour entrer un code TOTP (Time-based One-Time Password) généré par une application d'authentification (ex: Google Authenticator).

Déconnexion : Un bouton de déconnexion doit être présent.

Protection des pages : Toutes les pages, à l'exception de la connexion, doivent être accessibles uniquement aux utilisateurs authentifiés.

4.2. Interface et Navigation
L'application utilise une disposition à deux colonnes :

Une barre de navigation verticale fixe à gauche contenant les liens principaux : "Comptes" et "Utilisateurs".

Une zone de contenu principale à droite qui affiche la page sélectionnée.

Un en-tête simple affiche le nom du CRM et un message de bienvenue avec le nom de l'utilisateur connecté.

4.3. Module "Comptes" (Tableau de bord)
Route : /dashboard

Vue par défaut de l'application après connexion.

Affichage : Un tableau listant tous les Account.

Colonnes du tableau : Client, Contact, Responsable, Priorité, Prochaine Étape.

Interactivité :

Un clic sur une ligne de compte doit dynamiquement afficher la section "Actions" pour ce compte en dessous, sans rechargement de page.

4.4. Module "Actions & Historique"
Cette section n'a pas de route directe, elle est chargée dynamiquement dans la page du tableau de bord.

Affichage des Actions : Un tableau listant les Action liées au compte sélectionné.

Colonnes : Action, Responsable, Type, Résumé, Date Prochaine Étape.

Affichage de l'Historique :

Un clic sur une ligne d'action doit dynamiquement afficher la section "Historique" pour cette action en dessous.

L'historique est une simple liste <ul> affichant la date, l'auteur et la note de chaque entrée History.

4.5. Module "Utilisateurs"
Route : /users

Affichage : Un tableau listant tous les User.

Colonnes : ID, Nom, Email.

Fonctionnalité d'ajout : Un formulaire simple sur la même page pour ajouter un nouvel utilisateur.

Champs du formulaire : Nom, Email, Mot de passe.

5. Points Techniques Spécifiques
   Création d'utilisateur en ligne de commande : Prévoir une commande Symfony (app:create-user) pour créer le premier utilisateur administrateur.

Pas de build front-end : L'intégration de Bootstrap 5 se fera via un lien CDN dans le template de base (base.html.twig). Le JavaScript pour l'interactivité sera écrit en Vanilla JS ou Stimulus et inclus via des balises <script>.
