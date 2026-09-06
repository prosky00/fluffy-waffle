# Faction Dashboard

Internal intranet panel for GTA RP factions — reports, events, announcements, intranet messaging, Discord integration, and admin tools.

---

## Requirements

| Tool | Minimum version |
|------|----------------|
| [Docker](https://docs.docker.com/get-docker/) | 24 |
| [Docker Compose](https://docs.docker.com/compose/install/) | v2 (bundled with Docker Desktop) |
| Git | any |

No PHP, Node, or Composer needed on the host machine — everything runs inside Docker.

---

## Quick start

```bash
# 1. Clone the repo
git clone <your-repo-url> faction-dashboard
cd faction-dashboard

# 2. Create your base environment file (no editing needed — the wizard handles it)
cp .env.example .env

# 3. Build and start
docker compose up -d --build
```

Visit **http://localhost** (or whatever port `APP_PORT` is set to).  
The install wizard opens automatically. It will ask for:

- **Frakció neve** — your faction's name
- **Discord Bot credentials** — Client ID, Secret, Bot Token, Guild ID (see [Discord setup](#discord-setup) below)
- **Admin fiók** — the first admin username and password

Click **Telepítés indítása** and within a few seconds you'll be redirected to the login page.

---

## Discord setup

### 1 — Create the application

1. Go to [discord.com/developers/applications](https://discord.com/developers/applications) → **New Application**
2. Note the **Client ID** on the General Information page → `DISCORD_CLIENT_ID`
3. Go to **OAuth2** → **Client Secret** → copy it → `DISCORD_CLIENT_SECRET`
4. Go to **Bot** → **Add Bot** → copy the token → `DISCORD_BOT_TOKEN`

### 2 — Bot permissions

On the **Bot** page enable these **Privileged Gateway Intents**:
- `SERVER MEMBERS INTENT`
- `MESSAGE CONTENT INTENT`

Add the bot to your server using this URL (replace `CLIENT_ID`):
```
https://discord.com/oauth2/authorize?client_id=CLIENT_ID&scope=bot&permissions=2684354560
```

The number covers: Send Messages, Embed Links, Read Message History, Manage Roles, View Channels.

### 3 — OAuth2 redirect URL

In **OAuth2 → Redirects** add:
```
http://your-domain/auth/discord/callback
```

For local dev: `http://localhost/auth/discord/callback`

### 4 — Collect IDs

Right-click items in Discord (with **Developer Mode** on in User Settings → Advanced):

| `.env` key | Where to find it |
|---|---|
| `DISCORD_GUILD_ID` | Right-click your **server** → Copy Server ID |
| `DISCORD_ANNOUNCEMENT_CHANNEL_ID` | Right-click the announcements **channel** → Copy Channel ID |
| `DISCORD_REPORTS_CHANNEL_ID` | Right-click the reports **channel** → Copy Channel ID |
| `DISCORD_MEMBER_ROLE_ID` | Server Settings → Roles → right-click the member role → Copy Role ID |

---

## Environment variables reference

| Variable | Required | Description |
|---|---|---|
| `APP_URL` | yes | Full URL the site is served from (`https://yourserver.com`) |
| `APP_KEY` | auto | Generated on first boot. Never change once set in production. |
| `APP_PORT` | no | Host port Docker binds to (default `80`) |
| `DB_CONNECTION` | no | `sqlite` (default) or `mysql` |
| `DISCORD_CLIENT_ID` | yes | Discord OAuth client ID |
| `DISCORD_CLIENT_SECRET` | yes | Discord OAuth client secret |
| `DISCORD_BOT_TOKEN` | yes | Discord bot token |
| `DISCORD_GUILD_ID` | yes | Your Discord server ID |
| `DISCORD_ANNOUNCEMENT_CHANNEL_ID` | no | Channel for announcement embeds |
| `DISCORD_REPORTS_CHANNEL_ID` | no | Channel for report notification embeds |
| `DISCORD_MEMBER_ROLE_ID` | no | Role ID for member verification |

---

## Switching to MySQL

1. Uncomment the `mysql` block in `docker-compose.yml`
2. Uncomment the MySQL lines in `.env` and set:
   ```
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_DATABASE=faction
   DB_USERNAME=faction
   DB_PASSWORD=your-password
   DB_ROOT_PASSWORD=your-root-password
   ```
3. Restart: `docker compose up -d`

---

## Useful commands

```bash
# View logs
docker compose logs -f app
docker compose logs -f web

# Run artisan commands
docker compose exec app php artisan <command>

# Open a shell in the container
docker compose exec app bash

# Stop everything
docker compose down

# Stop and delete all data (volumes) — DESTRUCTIVE
docker compose down -v
```

---

## Updating

```bash
git pull
docker compose up -d --build
```

The entrypoint runs `php artisan migrate --force` automatically on every start, so schema updates are applied without any manual step.

---

## Production checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Set `APP_URL` to your real HTTPS domain
- [ ] Put an SSL-terminating reverse proxy (Caddy, nginx, Cloudflare Tunnel) in front
- [ ] Rotate all Discord secrets after any accidental public exposure
- [ ] Change the default admin password immediately after first login
- [ ] Back up the `db_data` volume regularly (`docker run --rm -v faction_db_data:/data -v $(pwd):/backup alpine tar czf /backup/db-backup.tar.gz /data`)

---

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Frontend | Blade templates, vanilla JS, EasyMDE, marked.js |
| Database | SQLite (default) / MySQL 8 |
| Auth | Laravel session + Discord OAuth (Socialite) |
| Discord | Discord Bot API v10 via HTTP |
| Container | PHP-FPM 8.2 + Nginx 1.25 (Alpine) |
