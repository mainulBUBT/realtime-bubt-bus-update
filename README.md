# Realtime BUBT Bus Update

Welcome to the BUBT Bus Tracker project. This is a real-time bus tracking system designed for the Bangladesh University of Business and Technology (BUBT), allowing students to track university buses and drivers to broadcast their live locations.

## Project Architecture

This project is structured as a **monorepo** consisting of two main parts:

1. **[`backend/`](backend/)**: The core backend application built with **Laravel 11**.
   - Serves the robust REST API.
   - Hosts the comprehensive Admin Panel.
   - Manages WebSocket broadcasting via **Laravel Reverb**.
   - Handles background jobs for trip cleanup, bus schedule automations, and tracking logic.
   
2. **[`frontend/`](frontend/)**: The cross-platform mobile application built with **Vue 3, Vite, and Capacitor**.
   - Contains two distinct app builds: the **Driver App** and **Student App**.
   - Utilizes Capacitor for native features (GPS tracking, device background geolocation).
   - Consumes the API and listens for real-time WebSocket events.

## Documentation Navigation

Detailed documentation has been organized to help you set up, deploy, and understand the project. For extensive backend and deployment guides, please refer to the `backend/docs/` directory:

- 📖 **[Main Backend Documentation](backend/docs/README.md)**: Overview of features, tracking mechanics, and architecture.
- ⚙️ **[Installation Guide](backend/docs/installation.md)**: Step-by-step instructions for local development setup across both backend and frontend.
- 🚀 **[Deployment Guide](backend/docs/deployment.md)**: Production deployment checklist and troubleshooting.

## Quick Start (Development)

To get started quickly, please refer to the [Installation Guide](backend/docs/installation.md) for full steps. In summary:

1. Setup the **Backend**:
   ```bash
   cd backend
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   php artisan serve
   ```
2. Setup the **Frontend**:
   ```bash
   cd frontend
   npm install
   npm run dev:student # or npm run dev:driver
   ```
