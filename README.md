# InterviewAI — Backend

A full-stack AI-powered technical interview simulator. This is the Laravel API backend that handles authentication, question generation, AI-powered answer evaluation, and result tracking.

**Live Demo:** _(coming soon)_
**Frontend Repo:** [interview-simulator-frontend](https://github.com/samirajubaii/interview-simulator-frontend)

## Features

- 🔐 **Authentication** — Token-based auth with Laravel Sanctum (register, login, logout, password reset)
- 🤖 **AI Evaluation** — Real-time interview answer scoring and feedback powered by the Groq API (Llama 3.3 70B)
- 📝 **Dynamic Question Generation** — AI-generated, role-specific interview questions (Frontend, Backend, Fullstack, DevOps) at three difficulty levels
- 📊 **Result Tracking** — Stores and retrieves interview session history per user
- ⚡ **Redis Caching** — Caches dashboard results and category lists to reduce database load
- 🔄 **Background Job Queue** — Database-driven Laravel Queues with a dedicated worker container, used for asynchronous processing
- 🐳 **Fully Dockerized** — Five-container setup (App, Nginx, MySQL, Redis, Queue Worker) for consistent, reproducible environments
- ✅ **Tested** — PHPUnit feature tests covering authentication and core API endpoints

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 11 (PHP 8.2) |
| Database | MySQL 8.0 |
| Cache / Queue | Redis (phpredis extension) |
| AI Provider | Groq API (Llama 3.3 70B Versatile) |
| Auth | Laravel Sanctum |
| Web Server | Nginx |
| Containerization | Docker & Docker Compose |
| Testing | PHPUnit |

## Architecture

┌─────────────┐      ┌─────────────┐      ┌─────────────┐

│   Nginx     │ ───▶ │  Laravel    │ ───▶ │   MySQL     │

│  (Port 8000)│      │  App (FPM)  │      │  Database   │

└─────────────┘      └──────┬──────┘      └─────────────┘

│

┌──────────┴──────────┐

▼                     ▼

┌─────────────┐       ┌─────────────┐

│    Redis     │       │ Queue Worker │

│ (Cache/Queue)│       │  Container   │

└─────────────┘       └─────────────┘

## Getting Started

### Prerequisites
- Docker Desktop
- A free [Groq API key](https://console.groq.com)

### Setup

1. **Clone the repository**
```bash
   git clone https://github.com/samirajubaii/interview-simulator-backend.git
   cd interview-simulator-backend
```

2. **Create your environment file**
```bash
   cp .env.example .env.docker
```
   Then open `.env.docker` and add your Groq API key and generate an `APP_KEY`.

3. **Build and start all containers**
```bash
   docker compose up -d --build
```
   First run takes a few minutes while images are pulled and built.

4. **Set up the application**
```bash
   docker exec interviewai_app cp .env.docker .env
   docker exec interviewai_app php artisan key:generate --force
   docker exec interviewai_app php artisan migrate --force
   docker exec interviewai_app php artisan config:cache
```

5. **Verify it's running**

   Visit `http://localhost:8000/api/questions` — you should see an "Unauthenticated" JSON response, confirming the API is live.

### Running Tests

```bash
docker exec interviewai_app php artisan test
```

## Key Engineering Decisions

- **Synchronous AI evaluation over async queues**: Initially built answer evaluation as a queued background job, but reverted to synchronous processing after testing — the polling overhead added more perceived latency than it saved for a single-request use case. The queue infrastructure remains in place and is used for other background tasks.
- **Switched from Predis to phpredis**: Diagnosed a multi-second latency issue traced to the Predis PHP client's retry/backoff behavior. Migrating to the native phpredis extension reduced average cache response time by roughly 90%.
- **Redis caching with targeted invalidation**: Dashboard results are cached per-user and explicitly invalidated when a new interview session is saved, balancing performance with data freshness.

## License

This project is open source and available for educational purposes.