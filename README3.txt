# WebApp – PHP-FPM + Nginx i Docker (Docker Hub, GitHub Actions, Azure)

## En liten men robust webapp i PHP 8.2 (FPM) bakom Nginx, paketerad i Docker, publicerad till Docker Hub och deployad till Azure Web App for Containers via GitHub Actions. Innehåller grundläggande säkerhet (CSP, HSTS-nära headers, CSRF-skydd, rate limiting m.m.).

## Innehåll

- Arkiterktur 
- Katalogstruktur 
- Krav
- Köra lokalt (Docker Compose)
- Bygga och Pusha till Docker Hub
- Köra från registry lokalt
- CI/CD med Github Actions
- Azure - skapa resurser och deploya Compose
- Säkerhet
- Felsökning
- Licens

# Arkitektur

- php: PHP-FPM container (kör appkoden).

- nginx: Nginx container som reverse proxy 
- (lyssnar på port 80) och skickar PHP-requests till php:9000.

# Katalogstruktur

## webapp/
├─ public/
│  └─ index.php              # routing: /, /contact (CSRF), /health
├─ src/
│  ├─ bootstrap.php          # sessions, säkerhetsheaders, CSRF
│  └─ views.php              # minimal templating/layout
├─ nginx/
│  ├─ nginx.conf             # gzip, rate limit, includes
│  └─ default.conf           # serverblock + fastcgi till php:9000
├─ php.ini                   # PHP-hardening
├─ php.Dockerfile            # FROM php:8.2-fpm-alpine
├─ nginx.Dockerfile          # FROM nginx:1.25-alpine
├─ docker-compose.yml        # lokal dev (bygger images)
├─ docker-compose.registry.yml   # kör från Docker Hub
└─ .github/workflows/build-push-deploy.yml  # CI/CD → Docker Hub + Azure


# Krav

## 
- Docker Desktop / Docker Engine + Compose v2
- Git + GitHub-konto (Doogmann/webapp)
- Docker Hub-konto (dogman11)
- Azure-konto med en subskription

# Köra lokalt (Docker Compose)

## Bygg och starta containers
- docker compose build
- docker compose up

# Appen
# http://localhost:8080
# http://localhost:8080/health

# Stoppa
- docker compose down

## Obs: nginx/default.conf använder fastcgi_pass php:9000; - tjänstnamnet i Compose måste vara php.

# Bygga & pusha till Docker Hub

1. Logga in (använd Docker Hub Access Token med Read & Write):

## docker logout
echo '<DOCKERHUB_RW_TOKEN>' | docker login -u -  dogman11 --password-stdin

# 2. Bygg & pusha:

## docker build -f php.Dockerfile   -t dogman11/webapp-php:latest .

docker build -f nginx.Dockerfile -t dogman11/webapp-nginx:latest .

docker push dogman11/webapp-php:latest
docker push dogman11/webapp-nginx:latest

## Köra från registry lokalt
docker-compose.registry.yml förutsätter att images finns i Docker Hub:

services:
  php:
    image: dogman11/webapp-php:latest
  nginx:
    image: dogman11/webapp-nginx:latest
    depends_on: [php]
    ports:
      - "8080:80"

# Starta:
docker compose -f docker-compose.registry.yml up

# CI/CD med GitHub Actions

## Workflow: .github/workflows/build-push-deploy.yml

Bygger och pushar webapp-php + webapp-nginx till Docker Hub (latest + commit-SHA).

Renderar compose till att peka på commit-taggarna.

Loggar in i Azure och deployar till Web App.

Secrets (repo - Settings - Secrets and variables - Actions)

Secret	                        Värde
DOCKERHUB_USERNAME	            dogman11
DOCKERHUB_TOKEN	Docker Hub      Read & Write token
AZURE_WEBAPP_NAME	           webapp-docker-b17c83
AZURE_CREDENTIALS	            Hela JSON från az ad sp create-for-rbac --sdk-auth

Använder azure/login@v2 med creds (JSON).

Kör pipeline:

Push till main eller Actions → Build, Push & Deploy (WebApp Docker) → Run workflow.

# Azure – skapa resurser och deploya Compose

Exempelvärden:
RG: rg-webapp-docker · Plan: asp-webapp-docker (Linux) · 
App: webapp-docker-b17c83 · Location: northeurope

# Git Bash på Windows – undvik path-konv.
export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL="*"

AZ_SUB="subscription-id"
AZ_LOC="northeurope"
AZ_RG="rg-webapp-docker"
AZ_PLAN="asp-webapp-docker"
AZ_APP="webapp-docker-b17c83"

az login
az account set --subscription "$AZ_SUB"
az provider register --namespace Microsoft.Web --wait

# Resursgrupp + plan + app (utan runtime, vi kör containers)
az group create -n "$AZ_RG" -l "$AZ_LOC"
az appservice plan create -g "$AZ_RG" -n "$AZ_PLAN" --is-linux --sku B1
az webapp create -g "$AZ_RG" -p "$AZ_PLAN" -n "$AZ_APP"

# (Om Docker Hub-repon är privata – sätt registry-credentials på appen)
az webapp config appsettings set \
  -g "$AZ_RG" -n "$AZ_APP" \
  --settings DOCKER_REGISTRY_SERVER_URL="https://index.docker.io/v1/" \
             DOCKER_REGISTRY_SERVER_USERNAME="dogman11" \
             DOCKER_REGISTRY_SERVER_PASSWORD="<DOCKERHUB_RW_TOKEN>"

# Manuell deploy av compose (valfritt, CI/CD gör detta annars)
az webapp config container set \
  -g "$AZ_RG" -n "$AZ_APP" \
  --multicontainer-config-type compose \
  --multicontainer-config-file docker-compose.registry.yml

az webapp restart -g "$AZ_RG" -n "$AZ_APP"

# Hitta URL
az webapp show -g "$AZ_RG" -n "$AZ_APP" --query defaultHostName -o tsv


# Säkerhet

HTTP-headers (PHP + Nginx):
Content-Security-Policy, X-Frame-Options: DENY, X-Content-Type-Options: nosniff,
Referrer-Policy: strict-origin-when-cross-origin, Permissions-Policy.

Sessions: Secure, HttpOnly, SameSite=Lax (se bootstrap.php/php.ini).

CSRF: slumpad token i session + hash_equals vid POST /contact.

Rate limiting (Nginx): 5 req/s per IP, burst 10.

Blockerar dolda filer (/.git, .env) samt ingen PHP-exekvering i /uploads.

PHP-hardening (php.ini): expose_php=0, display_errors=0, disable_functions=exec,system,... m.m.

Små images (Alpine), separata containers för Nginx/PHP = best practice.


# Felsökning

Docker push: insufficient scopes
- Token på Docker Hub är inte Read & Write eller saknar repo-scope. Skapa ny RW-token, uppdatera DOCKERHUB_TOKEN.

BuildKit: failed to fetch oauth token
- Lägg in explicit login till docker.io i workflowet, använd pull: true och base images med docker.io/library/....

Azure login i Actions: SERVICE_PRINCIPAL ... Not all values are present
- Använd azure/login@v2 med creds: ${{ secrets.AZURE_CREDENTIALS }} där AZURE_CREDENTIALS är hela JSON från az ad sp create-for-rbac --sdk-auth.

Microsoft.Web not registered

az provider register --namespace Microsoft.Web --wait

## Planen finns inte / Appen ger 502

Skapa plan + app utan --runtime.

Säkerställ att compose deployats och att Nginx lyssnar på 80 i containern.

Taila loggar:
az webapp log tail -g "$AZ_RG" -n "$AZ_APP"

Nginx: host not found in upstream "php"
- Tjänstnamnet i Compose måste vara php (matchar fastcgi_pass php:9000;).

### Snabbkommandon

Lokalt
docker compose up

Docker Hub
echo '<TOKEN>' | docker login -u dogman11 --password-stdin
docker compose build && docker compose push

Actions manuellt
GitHub - Actions - Build, Push & Deploy - Run workflow
