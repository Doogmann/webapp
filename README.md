# Inlämningsuppgift 2 – PHP-FPM + Nginx i Docker (Azure + CI/CD)

# Skapa och kör en enkel webapp i Docker, publicera images på Docker Hub, och deploya som multi-container till Azure Web App for Containers. Bygg & deploy sker automatiskt via GitHub Actions.

# Innehåll:

# Arkitektur & mapstruktur
# Förkrav
# Köra lokalt
# Bygga & pusha images till Docker Hub
# CI/CD med Github Actions
# Deploy till Azure (CLI)
# Verifiering 
# Säkerhet i lösningen
# Felsökning

# Arkitektur & mapstruktur
# Stack: PHP 8.2 (FPM) + Nginx.
# Infrastruktur: Docker, Docker Hub, Azure Web App for Containers.
# CI/CD: GitHub Actions (bygger & pushar images + deployar compose till Azure).

# webapp/
├─ public/
│  └─ index.php              # routing + sidor (/ , /contact, /health)
├─ src/
│  ├─ bootstrap.php          # sessions, säkerhetsheaders, CSRF, helpers
│  └─ views.php              # enkel layout/“templating”
├─ nginx/
│  ├─ nginx.conf             # global nginx (rate limit m.m.)
│  └─ default.conf           # serverblock, PHP-FPM, headers
├─ php.ini                   # säkra PHP-defaults
├─ php.Dockerfile            # bygger PHP-FPM-bilden
├─ nginx.Dockerfile          # bygger Nginx-bilden
├─ docker-compose.yml        # lokal utveckling (bygger images)
├─ docker-compose.registry.yml  # pekar på publicerade images i registry
└─ .github/workflows/build-push-deploy.yml  # CI/CD

# Endpoints

/ – startsida

/contact – formulär (CSRF-skyddat)

/health – hälsa (OK)

/info – PHP-info (endast om APP_DEBUG_INFO=1 i miljön)

# Förkrav

# Docker & Docker Compose v2

# Git + GitHub-konto + repo

# Docker Hub-konto (t.ex. dogman11 eller doogmann)

# Azure CLI (az) + en Azure-subscription

# I guiderna nedan använder vi variabeln NS för ditt Docker Hub-namespace.
Sätt den en gång och använd samma överallt:
# Välj ETT konto och håll dig till det:
export NS="dogman11"      
# webapp
