# ChatSystem — Developer Guide

## Table of Contents

- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [How It Works](#how-it-works)
- [Getting Started](#getting-started)
- [Environment Setup](#environment-setup)
- [Installing Laravel (inside container)](#installing-laravel-inside-container)
- [Installing Vue.js (inside container)](#installing-vuejs-inside-container)
- [Running the Dev Servers](#running-the-dev-servers)
- [Common Docker Commands](#common-docker-commands)
- [Ports](#ports)
- [Cross-OS Compatibility](#cross-os-compatibility)

---

## Project Structure

```
ChatSystem/
├── compose.yaml                        # Docker Compose configuration
├── .gitattributes                      # Enforces LF line endings and case sensitivity
├── .gitignore                          # Ignores OS/editor junk files
├── .editorconfig                       # Enforces consistent formatting across editors
├── docker/
│   ├── laravel/
│   │   ├── Dockerfile                  # PHP 8.4-cli + Composer 2.9 image
│   │   └── Dockerfile.dockerignore    # Build context exclusions for laravel-service
│   └── vuejs/
│       ├── Dockerfile                  # Node 24.12.0 (Alpine) image
│       └── Dockerfile.dockerignore    # Build context exclusions for vuejs-service
├── laravel-app/                        # Laravel source code (mounted into laravel-container)
└── vuejs-app/                          # Vue.js source code (mounted into vuejs-container)
```

---

## Requirements

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (includes Docker Compose v2)
- No other tools needed — PHP, Composer, Node, and npm all run **inside the containers**

---

## How It Works

Both containers are kept alive with `sleep infinity` so you can shell into them and run commands manually. The `laravel-app/` and `vuejs-app/` folders on your host are mounted as volumes, so any files you create inside the container are immediately visible on your host (and vice versa).

| Container | Image | Volume mount |
|---|---|---|
| `laravel-container` | `php:8.4-cli` + Composer 2.9 | `./laravel-app` → `/var/www/html` |
| `vuejs-container` | `node:24.12.0-alpine` | `./vuejs-app` → `/app` |

---

## Getting Started

### 1. Build and start the containers

```bash
docker compose up --build
```

> Use `docker compose up --build -d` to run in the background (detached mode).

### 2. Verify containers are running

```bash
docker compose ps
```

---

## Installing Laravel (inside container)

Shell into the Laravel container:

```bash
docker exec -it laravel-container bash
```

OR, if you prefer using the `laravel-service` service defined in your `compose.yaml`:

```bash
docker compose exec laravel-service bash
```

Create a new Laravel project into the current working directory:

```bash
composer create-project laravel/laravel .
```

> This installs Laravel into `/var/www/html` inside the container, which maps to `./laravel-app` on your host.

---

## Installing Vue.js (inside container)

Shell into the Vue.js container:

```bash
docker exec -it vuejs-container sh
```

OR, if you prefer using the `vuejs-service` service defined in your `compose.yaml`:

```bash
docker compose exec vuejs-service sh
```

Scaffold a new Vue project:

```bash
npm create vue@latest .
```

Install dependencies:

```bash
npm install
```

> This installs Vue.js into `/app` inside the container, which maps to `./vuejs-app` on your host.

---

## Running the Dev Servers

### Laravel

Inside the `laravel-container`:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Access at: **http://localhost:8000**

### Vue.js

Inside the `vuejs-container`:

```bash
npm run dev -- --host=0.0.0.0 --port=5173
```

Access at: **http://localhost:5173**

---

## Common Docker Commands

| Action | Command |
|---|---|
| Build and start all containers | `docker compose up --build` |
| Start in background (detached) | `docker compose up -d` |
| Stop all containers | `docker compose down` |
| Stop and remove volumes | `docker compose down -v` |
| View running containers | `docker compose ps` |
| View container logs | `docker compose logs` |
| Follow logs live | `docker compose logs -f` |
| Follow logs for one service | `docker compose logs -f laravel-service` |
| Shell into Laravel container | `docker compose exec laravel-service bash` |
| Shell into Vue.js container | `docker compose exec vuejs-service sh` |
| Rebuild a single service | `docker compose build laravel-service` |
| Restart a single service | `docker compose restart laravel-service` |
| Remove stopped containers | `docker compose rm` |

---

## Ports

| Service | Container port | Host port | URL |
|---|---|---|---|
| Laravel | 8000 | 8000 | http://localhost:8000 |
| Vue.js | 5173 | 5173 | http://localhost:5173 |

---

## Cross-OS Compatibility

This project is configured to work consistently across Windows, macOS, and Linux:

| Config file | Purpose |
|---|---|
| `.gitattributes` | Forces `*.sh` files to always use LF line endings; enforces case sensitivity |
| `.gitignore` | Excludes OS files (`.DS_Store`, `Thumbs.db`) and editor folders |
| `.editorconfig` | Enforces UTF-8, LF, consistent indent style across all editors |
| `Dockerfile.dockerignore` | Excludes `node_modules`, `vendor` etc. from Docker build context per service |

> **Windows users:** Install the [EditorConfig for VS Code](https://marketplace.visualstudio.com/items?itemName=EditorConfig.EditorConfig) extension to apply `.editorconfig` automatically.
