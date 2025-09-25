# WebApp – PHP-FPM + Nginx i Docker (Docker Hub, GitHub Actions, Azure)

Bygg en enkel PHP-webapp med **PHP-FPM + Nginx**, publicera images till **Docker Hub**, och deploya till **Azure Web App for Containers**. CI/CD med **GitHub Actions**.

## Innehåll
- [Översikt](#översikt)
- [Förkrav](#förkrav)
- [Projektstruktur](#projektstruktur)
- [Säkerhet i appen](#säkerhet-i-appen)
- [Köra lokalt](#köra-lokalt)
- [Bygga & pusha till Docker Hub](#bygga--pusha-till-docker-hub)
- [Git & GitHub](#git--github)
- [CI/CD med GitHub Actions](#cicd-med-github-actions)
- [Deploy till Azure (CLI)](#deploy-till-azure-cli)
- [Verifiering](#verifiering)
- [Felsökning](#felsökning)

---

## Översikt

**Stack:** PHP 8.2 (FPM) + Nginx (Alpine-baserade images)  
**Registry:** Docker Hub (`dogman11`)  
**Hosting:** Azure Web App for Containers (multi-container/compose)  
**CI/CD:** GitHub Actions (bygger → pushar → deployar)

---

## Förkrav

- **Docker Desktop** (Linux containers, WSL2) + Docker Compose v2
- **Git** + konto på **GitHub** (`doogmann`)
- **Docker Hub**-konto `dogman11` med **Access Token (Read/Write)**
- **Azure CLI (`az`)** och en aktiv Azure-subscription

> **Git Bash på Windows:** sätt dessa env-variabler i din session när du kör Azure-kommandon, så förstörs inte `/subscriptions/...`-sökvägar:
> ```bash
> export MSYS_NO_PATHCONV=1
> export MSYS2_ARG_CONV_EXCL="*"
> ```

---

## Projektstruktur

```
webapp/
├─ public/
│  └─ index.php              # routing + sidor: /, /contact, /health
├─ src/
│  ├─ bootstrap.php          # sessioner, säkerhetsheaders, CSRF, helpers
│  └─ views.php              # minimal templating/layout
├─ nginx/
│  ├─ nginx.conf             # global Nginx (gzip, rate limit)
│  └─ default.conf           # serverblock, PHP-FPM, headers
├─ php.ini                   # PHP-hardening
├─ php.Dockerfile            # PHP-FPM image
├─ nginx.Dockerfile          # Nginx image
├─ docker-compose.yml        # lokal utveckling (bygger images)
├─ docker-compose.registry.yml   # kör från Docker Hub
└─ .github/workflows/build-push-deploy.yml  # CI/CD
```

**Endpoints**
- `/` – startsida
- `/contact` – formulär (CSRF-skyddat)
- `/health` – hälsa (`OK`)
- `/info` – PHP-info (endast om `APP_DEBUG_INFO=1` i miljön)

---

## Säkerhet i appen

- **HTTP-säkerhetsheaders**: `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`.
- **Sessionsäkert**: `Secure`, `HttpOnly`, `SameSite=Lax`.
- **CSRF-skydd** för POST.
- **Rate limiting** i Nginx (5 req/s per IP, burst 10).
- **Blockera dolda filer** (`/.git`, `.env`).
- **Uploads**-exempel: inga PHP-exekveringar i `/uploads`.
- **PHP-hardening**: stäng farliga funktioner, begränsa body/upload, göm `expose_php`.

---

## Köra lokalt

1) Bygg och kör:
```bash
docker compose build
docker compose up
```
Öppna `http://localhost:8080` och `http://localhost:8080/health`.

2) Stoppa:
```bash
docker compose down
```

> `docker-compose.yml` bygger och taggar lokala images som `dogman11/webapp-php:latest` och `dogman11/webapp-nginx:latest`. Justera namespace i filen om du vill.

---

## Bygga & pusha till Docker Hub

1) Logga in som **dogman11** med en **Access Token (Read/Write)**:
```bash
docker logout
docker login -u dogman11
```

2) Pusha images (om du byggt via compose):
```bash
docker push dogman11/webapp-php:latest
docker push dogman11/webapp-nginx:latest
```

3) (Valfritt) Testa att dra:
```bash
docker pull dogman11/webapp-php:latest
docker pull dogman11/webapp-nginx:latest
```

4) Kör endast från registry (utan källkod):
`docker-compose.registry.yml`
```yaml
services:
  php:
    image: dogman11/webapp-php:latest
  nginx:
    image: dogman11/webapp-nginx:latest
    depends_on: [php]
    ports:
      - "8080:80"
```
Starta:
```bash
docker compose -f docker-compose.registry.yml up
```

---

## Git & GitHub

```bash
git init
git add .
git commit -m "Initial PHP-FPM + Nginx setup"
git branch -M main
git remote add origin https://github.com/doogmann/webapp.git
git push -u origin main
```

---

## CI/CD med GitHub Actions

### 1) GitHub Secrets

Repo → **Settings → Secrets and variables → Actions**:

**Docker Hub**
- `DOCKERHUB_USERNAME` = `dogman11`
- `DOCKERHUB_TOKEN` = *Access Token (RW)*

**Azure (ny metod – 4 separata secrets)**
- `AZURE_CLIENT_ID`
- `AZURE_TENANT_ID`
- `AZURE_SUBSCRIPTION_ID`
- `AZURE_CLIENT_SECRET`

> Skapa service principal och rolltilldelning (Git Bash):
> ```bash
> export MSYS_NO_PATHCONV=1
> export MSYS2_ARG_CONV_EXCL="*"
> AZ_SUB="DIN-SUBSCRIPTION-ID"
> AZ_RG="rg-webapp-docker"
> az login
> az account set --subscription "$AZ_SUB"
> # Contributor på RG-scope
> SP_APPID=$(az ad sp create-for-rbac >   --name "gh-actions-webapp" >   --role Contributor >   --scopes "/subscriptions/$AZ_SUB/resourceGroups/$AZ_RG" >   --query appId -o tsv)
> SP_SECRET=$(az ad sp credential reset --name "$SP_APPID" --years 1 --query password -o tsv)
> SP_TENANT=$(az ad sp show --id "$SP_APPID" --query appOwnerTenantId -o tsv)
> # Lägg in i secrets:
> # AZURE_CLIENT_ID=$SP_APPID, AZURE_TENANT_ID=$SP_TENANT, AZURE_SUBSCRIPTION_ID=$AZ_SUB, AZURE_CLIENT_SECRET=$SP_SECRET
> ```

**Web App-namn**
- `AZURE_WEBAPP_NAME` = t.ex. `webapp-docker-xxxxxx` (ditt faktiska appnamn i Azure)

### 2) Workflow (sammandrag)

Fil: `.github/workflows/build-push-deploy.yml`  
- Bygger & pushar images till `dogman11/webapp-php` & `dogman11/webapp-nginx` med taggar: `latest` **och** `commit-sha`.
- Renderar en compose som pekar på **commit-sha-taggarna**.
- Loggar in i Azure och deployar compose till `AZURE_WEBAPP_NAME`.

> Efter varje push till `main` sker build → push → deploy automatiskt.

---

## Deploy till Azure (CLI)

### 1) Skapa resursgrupp, plan och webapp (engångs)

```bash
# Git Bash – glöm inte:
export MSYS_NO_PATHCONV=1
export MSYS2_ARG_CONV_EXCL="*"

AZ_SUB="9bbad496-1ab4-48d5-9841-37d98a511320"
AZ_LOC="westeurope"
AZ_RG="rg-webapp-docker"
AZ_PLAN="asp-webapp-docker"
AZ_APP="webapp-docker-<valfritt-unikt>"

az login
az account set --subscription "$AZ_SUB"
az provider register --namespace Microsoft.Web --wait

az group create -n "$AZ_RG" -l "$AZ_LOC" -o table
az appservice plan create -g "$AZ_RG" -n "$AZ_PLAN" --is-linux --sku B1 -o table
az webapp create -g "$AZ_RG" -p "$AZ_PLAN" -n "$AZ_APP" --runtime "DOTNET|8.0" -o table
```

### 2) Sätt app-settings (valfritt)
```bash
az webapp config appsettings set   -g "$AZ_RG" -n "$AZ_APP"   --settings APP_NAME="WebApp" APP_DEBUG_INFO=0
```

### 3) Manuell deploy av compose (om du vill testa utan Actions)
Skapa `azure.compose.yml`:
```yaml
services:
  php:
    image: dogman11/webapp-php:latest
  nginx:
    image: dogman11/webapp-nginx:latest
    depends_on:
      - php
```
Applicera:
```bash
az webapp config container set   -g "$AZ_RG" -n "$AZ_APP"   --multicontainer-config-type compose   --multicontainer-config-file azure.compose.yml
az webapp restart -g "$AZ_RG" -n "$AZ_APP"
```

---

## Verifiering

**Lokal**
- `http://localhost:8080/` → ny layout
- `http://localhost:8080/health` → `OK`

**Azure**
```bash
HOST=$(az webapp show -g "$AZ_RG" -n "$AZ_APP" --query defaultHostName -o tsv)
echo "https://$HOST"
```
- `https://<HOST>/` → ny layout  
- `https://<HOST>/health` → `OK`

**Headers (säkerhet)**
```bash
curl -sI https://$HOST | egrep 'Content-Security-Policy|X-Frame-Options|Referrer-Policy|Permissions-Policy|X-Content-Type-Options'
```

---

## Felsökning

**Docker Desktop: “pipe not found” / engine down**  
→ Starta Docker Desktop (Linux containers), kolla `docker version`.

**Compose-varning: `version` är obsolete**  
→ Ta bort `version:`-raden i compose-filer.

**`host not found in upstream "php"`**  
→ Nginx hittar inte PHP-FPM – kör **båda** tjänsterna via compose och ha `fastcgi_pass php:9000;`.

**Docker Hub: `insufficient_scope` / `authentication required`**  
→ Logga in som **dogman11** med **RW Access Token**. Repo-namn **lowercase**.  
  Retagga och pusha:  
  `docker tag webapp-php:latest dogman11/webapp-php:latest && docker push dogman11/webapp-php:latest`

**Azure visar gammal version**  
→ Peka compose på **commit-sha** i Actions (workflowet gör detta). Alternativt bumpa taggen och uppdatera compose.

**`MissingSubscription` i Git Bash**  
→ Du glömde `MSYS_NO_PATHCONV=1` (se Förkrav). Säkerställ även `az account set --subscription <id>`.

---

## Licens / Övrigt
Detta repo är för kurs/labb. Använd på egen risk. För IaC: komplettera gärna med Bicep/Terraform för RG/Plan/App och kör deployment via pipelines.

---

**Kontakt**  
Frågor/förbättringar? Öppna en issue i GitHub-repot. 🚀
