# Déploiement sur Laravel Cloud

## Réglages de l’environnement

- Dépôt : `servicedevfac/G-Caisse`
- Branche : `main`
- Runtime : PHP 8.4
- Build : `composer install --no-dev --prefer-dist --optimize-autoloader`
- Déploiement : `php artisan migrate --force`
- Health check : `/up`

Le projet ne possède pas de `package.json`. Ne pas ajouter `npm run build` à la commande de build.

## Ressources

Dans le canvas Infrastructure, attacher une base **Laravel MySQL** dans la même région que l’application. Laravel Cloud injecte automatiquement `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` et `DB_PASSWORD`.

Variables applicatives recommandées :

```dotenv
APP_NAME="CaisseFlow"
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=fr
APP_TIMEZONE=UTC
LOG_CHANNEL=stderr
SESSION_DRIVER=cookie
CACHE_STORE=file
CAISSE_CURRENCY=XOF
```

Laravel Cloud crée et injecte `APP_KEY`. Ne jamais copier le fichier `.env` local dans Cloud.

## Stockage des documents

Dans le canvas **Environment**, ajouter un espace **Object Storage** privé dans la même région que l'application. Le connecter sous le nom `private` et le laisser défini comme disque par défaut. Laravel Cloud injecte sa configuration et CaisseFlow utilise automatiquement ce stockage persistant.

Si le bucket n'est pas défini comme disque par défaut, préciser son nom manuellement :

```dotenv
DOCUMENTS_DISK=nom_du_disque
```

Le fichier lui-même reste dans Object Storage. MySQL conserve uniquement son nom, sa taille, son type, son auteur et son chemin privé.

## Premier administrateur

Après le premier déploiement :

Dans les commandes de l’environnement Cloud, exécuter :

```bash
php artisan caisse:admin responsable@entreprise.com
```

## Transfert facultatif de la base locale

Pour conserver les données locales, activer temporairement l’endpoint public de la base Cloud, exporter MySQL localement puis importer le dump avec les identifiants affichés dans **Connection details**. Le dump contient des données sensibles : il est ignoré par Git et doit être supprimé après vérification. Désactiver ensuite l’endpoint public.
