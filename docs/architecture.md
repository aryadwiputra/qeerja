# Taska — Architecture

## System Context (C4 Level 1)

```mermaid
C4Context
  title System Context — Taska

  Person(user, "User", "Pengguna platform taska")

  System_Boundary(taska, "Taska Platform") {
    System(webapp, "Web App", "React + Inertia v3\nTailwind v4\nTypeScript")

    System_Boundary(be, "Backend (Laravel 13)") {
      Container(app, "Laravel App", "PHP 8.3", "Routing, auth, business logic")
      Container(queue, "Queue Worker", "PHP", "Background jobs")
    }

    SystemDb(db, "Database", "MySQL / SQLite", "57 tables")

    System_Boundary(gateways, "Gateways") {
      Container(rt, "Realtime Gateway", "Node.js\nSocket.IO :3002", "Broadcast mutations,\ntyping indicators")
      Container(wa, "WhatsApp Gateway", "Node.js\nwhatsapp-web.js :3001", "WA notification blasting")
    }
  }

  System_Ext(github, "GitHub", "OAuth + Webhook")
  System_Ext(discord, "Discord", "Webhook")
  System_Ext(slack, "Slack", "Webhook")
  System_Ext(telegram, "Telegram", "Bot API")
  System_Ext(email, "SMTP", "Email")
  System_Ext(webhook, "Custom Webhook", "HTTP POST")

  Rel(user, webapp, "HTTPS")
  Rel(webapp, app, "Inertia SPA + XHR")
  Rel(app, db, "ORM")
  Rel(app, queue, "Dispatches jobs")
  Rel(app, rt, "HTTP token")
  Rel(rt, app, "WS auth via Sanctum")
  Rel(webapp, rt, "WebSocket")
  Rel(app, wa, "send-message")
  Rel(app, github, "OAuth + webhook")
  Rel(app, discord, "Webhook URL")
  Rel(app, slack, "Webhook URL")
  Rel(app, telegram, "Bot API")
  Rel(app, email, "SMTP")
  Rel(app, webhook, "Custom URL")
```

## Container Diagram (C4 Level 2)

```mermaid
C4Container
  title Container Diagram — Taska

  Person(user, "User")

  System_Boundary(fe, "Frontend") {
    Container(spa, "React SPA", "React 19 + Inertia v3\nTailwind v4 + shadcn/ui\nreact-i18next", "Pages, layouts, components")
    Container(ssr, "SSR Server", "Node.js", "Inertia SSR bundle")
  }

  System_Boundary(be, "Backend") {
    Container(nginx, "Nginx", "Reverse proxy\nSSL termination\nStatic asset cache")
    Container(fpm, "PHP-FPM 8.3", "Laravel App")
    Container(fortify, "Fortify v1", "Package", "Login, register, 2FA,\npasskeys, email verify")
    Container(sanctum, "Sanctum v4", "Package", "SPA auth + API tokens")
    Container(spatie, "Spatie Permissions v8", "Package", "Roles & permissions")
    Container(wayfinder, "Wayfinder", "Plugin", "TypeScript route helpers")
  }

  System_Boundary(gw, "Node.js Gateways") {
    Container(realtime, "Realtime", "Socket.IO", ":3002")
    Container(whatsapp, "WhatsApp", "whatsapp-web.js", ":3001")
  }

  System_Boundary(data, "Data") {
    ContainerDb(db, "Database", "MySQL/SQLite")
    Container(queueStore, "Queue", "Database driver")
  }

  Rel(user, nginx, "HTTPS")
  Rel(nginx, fpm, "fastcgi")
  Rel(nginx, spa, "Static assets")
  Rel(nginx, realtime, "Reverse proxy /socket.io/")
  Rel(fpm, fortify, "Uses")
  Rel(fpm, sanctum, "Uses")
  Rel(fpm, spatie, "Uses")
  Rel(spa, ssr, "SSR")
  Rel(ssr, fpm, "Inertia SSR")
  Rel(fpm, db, "Read/Write")
  Rel(fpm, queueStore, "Dispatch")
  Rel(fpm, realtime, "HTTP (auth token)")
  Rel(fpm, whatsapp, "HTTP (send)")
  Rel(realtime, fpm, "WS auth (Sanctum)")
  Rel(spa, realtime, "WebSocket")
```

## System Layers

```mermaid
graph TB
  subgraph "Frontend Layer"
    REACT["React 19 + TypeScript"]
    INERTIA["Inertia v3 SPA + SSR"]
    TAILWIND["Tailwind v4 + shadcn/ui"]
    I18N["react-i18next (EN/ID)"]
    SOCKET["Socket.IO Client"]
  end

  subgraph "Application Layer"
    LARAVEL["Laravel 13"]
    FORTIFY["Fortify Auth"]
    SANCTUM["Sanctum SPA Auth"]
    SPATIE["Spatie RBAC"]
    ROUTES["Web Routes (Inertia)"]
    API["API Routes"]
    WAYFINDER["Wayfinder"]
  end

  subgraph "Business Logic"
    SERVICES["11 Services"]
    NOTIF["4 Notification Classes"]
    CHANNELS["7 Channels<br/>InApp / Mail / Discord / Slack<br/>Telegram / Webhook / WhatsApp"]
    POLICIES["7 Policies"]
    JOBS["3 Jobs"]
  end

  subgraph "Data Layer"
    DB["MySQL/SQLite<br/>57 tables"]
    MODELS["36 Eloquent Models"]
    QUEUE["Database Queue"]
  end

  subgraph "Real-time Layer"
    RT["Realtime Gateway<br/>Socket.IO :3002"]
    BROADCAST["Broadcasts: tasks, comments,<br/>labels, epics, sprints,<br/>boards, bulk ops"]
    TYPING["Typing indicators"]
  end

  subgraph "Deployment"
    NGINX["Nginx + SSL"]
    PM2["PM2: queue + gateways"]
  end

  REACT --> INERTIA
  INERTIA --> LARAVEL
  LARAVEL --> FORTIFY & SANCTUM & SPATIE
  LARAVEL --> ROUTES & API
  WAYFINDER --> ROUTES
  WAYFINDER --> REACT

  LARAVEL --> SERVICES
  SERVICES --> NOTIF
  NOTIF --> CHANNELS
  POLICIES --> LARAVEL
  JOBS --> LARAVEL

  LARAVEL --> DB
  MODELS --> DB
  LARAVEL --> QUEUE
  QUEUE --> JOBS

  LARAVEL --> RT
  CHANNELS --> LARAVEL
  REACT --> RT
  RT --> BROADCAST
  RT --> TYPING
```

## Domain Model (Core Entities)

```mermaid
erDiagram
  Workspace ||--|{ WorkspaceMember : has
  Workspace ||--|{ Project : contains
  Workspace ||--|{ NotificationChannel : configures
  Workspace ||--|{ Goal : sets
  Workspace ||--|{ TaskType : defines
  Workspace ||--|{ Priority : defines
  User ||--|{ WorkspaceMember : belongs_to
  User ||--|{ NotificationPreference : configures

  Project ||--|{ Board : has
  Project ||--|{ Task : contains
  Project ||--|{ Sprint : runs
  Project ||--|{ Epic : tracks
  Project ||--|{ Label : tags
  Project ||--|{ Component : groups
  Project ||--|{ Release : plans
  Project ||--|{ ProjectMember : assigns
  Project ||--|{ AutomationRule : automates
  Project ||--|{ ApprovalFlow : reviews
  Project ||--|{ SavedFilter : saves
  Project ||--|{ SlaPolicy : enforces
  Project ||--|{ NotificationRule : routes

  Board ||--|{ BoardColumn : contains
  BoardColumn ||--|{ Task : groups
  Sprint ||--|{ Task : plans
  Epic ||--|{ Task : tracks
  Release ||--|{ Task : ships

  Task ||--|{ TaskComment : discussed
  Task ||--|{ TaskAttachment : files
  Task ||--|{ TaskRelation : linked
  Task ||--|{ TaskActivity : logged
  Task ||--|{ TaskApproval : reviewed
  Task }|--|{ User : assigned_to
  Task }|--|{ User : watched_by
  Task }|--|{ Label : labeled
  Task }|--|{ Component : composed_of

  Goal ||--|{ KeyResult : measured_by
  Goal ||--|{ Epic : linked
```

## Notification Flow

```mermaid
flowchart LR
  EVENT["Event<br/>task.created<br/>task.assigned<br/>task.commented<br/>task.mentioned"]
  ENGINE["NotificationRuleEngine"]
  SERVICE["NotificationService"]
  CHANNEL_SELECT["Channel Routing<br/>per-user + per-workspace"]
  INAPP["InApp<br/>database + socket"]
  MAIL["Mail<br/>GenericNotificationMail"]
  DISCORD["Discord<br/>Webhook"]
  SLACK["Slack<br/>Webhook"]
  TELEGRAM["Telegram<br/>Bot API"]
  WEBHOOK["Custom Webhook<br/>HTTP POST"]
  WA["WhatsApp<br/>Gateway :3001"]

  EVENT --> ENGINE
  ENGINE --> SERVICE
  SERVICE --> CHANNEL_SELECT
  CHANNEL_SELECT --> INAPP
  CHANNEL_SELECT --> MAIL
  CHANNEL_SELECT --> DISCORD
  CHANNEL_SELECT --> SLACK
  CHANNEL_SELECT --> TELEGRAM
  CHANNEL_SELECT --> WEBHOOK
  CHANNEL_SELECT --> WA
```

## Real-time Broadcast

```mermaid
flowchart LR
  CLIENT["Browser<br/>Socket.IO Client"]
  NGNIX["Nginx<br/>/socket.io/ proxy"]
  GATEWAY["Realtime Gateway<br/>Socket.IO :3002"]
  SANCTUM_API["Sanctum API<br/>POST /api/socket/auth"]
  CHANNELS["Channels"]
  PRIVATE_WORKSPACE["workspace:{id}"]
  PRIVATE_PROJECT["project:{id}"]
  PRIVATE_TASK["task:{id}"]
  PRIVATE_USER["user:{id}"]

  CLIENT --> NGNIX
  NGNIX --> GATEWAY
  GATEWAY --> SANCTUM_API
  CLIENT --> SANCTUM_API

  GATEWAY --> CHANNELS
  CHANNELS --> PRIVATE_WORKSPACE
  CHANNELS --> PRIVATE_PROJECT
  CHANNELS --> PRIVATE_TASK
  CHANNELS --> PRIVATE_USER

  PRIVATE_WORKSPACE --> CLIENT
  PRIVATE_PROJECT --> CLIENT
  PRIVATE_TASK --> CLIENT
  PRIVATE_USER --> CLIENT
```

## Deployment Architecture

```mermaid
flowchart TB
  subgraph "VPS Ubuntu"
    NGINX["Nginx<br/>:80/:443<br/>SSL termination"]
    FPM["PHP-FPM 8.3<br/>Unix socket"]
    MYSQL["MySQL<br/>:3306"]

    subgraph "PM2 Process Manager"
      PM2_QUEUE["taska-queue<br/>php artisan queue:listen"]
      PM2_RT["taska-realtime<br/>Node.js :3002"]
      PM2_WA["taska-whatsapp<br/>Node.js :3001"]
    end

    STORAGE["Storage<br/>uploads, logs, sessions"]
  end

  INET["Internet"] --> NGINX
  NGINX --> FPM
  NGINX --> STORAGE
  NGINX --> PM2_RT
  FPM --> MYSQL
  FPM --> PM2_RT
  FPM --> PM2_WA
  FPM --> STORAGE
```

## Directory Structure (Simplified)

```
taska/
├── app/
│   ├── Actions/Fortify/     Auth action classes
│   ├── Http/Controllers/   41 controllers + 3 sub-namespaces
│   ├── Jobs/               3 job classes
│   ├── Mail/               GenericNotificationMail
│   ├── Models/             36 Eloquent models
│   ├── Notifications/      4 notifications + 7 channels
│   ├── Policies/           7 authorization policies
│   ├── Providers/          App + Fortify service providers
│   ├── Queries/            TaskSearchQuery
│   └── Services/           11 service classes
├── config/                 17 config files
├── database/
│   ├── factories/          Model factories
│   ├── migrations/         47 migration files
│   └── seeders/            Database seeders
├── deploy/
│   ├── DEPLOY.md           Deployment guide (ID)
│   ├── ecosystem.config.cjs PM2 process config
│   ├── nginx.conf           Nginx site config
│   ├── supervisor-queue.conf
│   └── .env.production.example
├── docs/
│   ├── architecture.md     This file
│   ├── github-integration.md
│   └── plans/
├── realtime-gateway/       Node.js Socket.IO gateway
├── whatsapp-gateway/       Node.js WhatsApp gateway
├── resources/
│   └── js/
│       ├── components/     71 components + ui/ + board/ + charts/ + dashboard/
│       ├── layouts/        5 layout types
│       ├── pages/          11 page groups (33 pages)
│       ├── hooks/          Socket, theme, etc.
│       ├── i18n/           EN/ID translations
│       └── app.tsx         Inertia app entry
├── routes/
│   ├── web.php             Main Inertia routes
│   ├── api.php             API routes
│   ├── settings.php        Settings routes
│   └── admin.php           Admin routes
└── tests/
    ├── Feature/            33 feature tests
    └── Unit/               2 unit tests
```

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | PHP + Laravel | 8.3 / 13 |
| Auth | Fortify + Sanctum | v1 / v4 |
| RBAC | Spatie Permissions | v8 |
| Frontend | React + Inertia + TypeScript | 19 / v3 / strict |
| CSS | Tailwind CSS via Vite | v4 |
| UI Kit | shadcn/ui (Radix UI) | latest |
| i18n | react-i18next | latest |
| Realtime | Socket.IO (custom Node.js) | Port 3002 |
| WhatsApp | whatsapp-web.js (custom Node.js) | Port 3001 |
| Database | MySQL (production) / SQLite (dev) | |
| Queue | Database driver | |
| Process | PM2 + Nginx + Supervisor | |
| Testing | Pest PHP | v4 |
| Linting | Pint + ESLint + Prettier | |
| Route Gen | Laravel Wayfinder | v0 |
