# Neunsoft Chat

A Slack-style client messaging app for Neunsoft. One workspace per project; you
invite clients and chat with them in channels, in real time. Intended to live at
`chat.neunsoft.com` as a standalone product, separate from the marketing site /
internal admin in `neunsoft-web`.

## Stack

Laravel 12 · Inertia + Vue 3 · Tailwind 4 (reka-ui) · Laravel Reverb (websockets)
· Fortify (auth). Bootstrapped from the `getstuffdone` starter and stripped of its
timer domain.

## Model

- **Workspace = a project.** Top of the hierarchy.
- **Channels** live inside a workspace. Visibility is membership-driven: a member
  only sees channels they've been added to, so two clients in the same workspace
  never see each other's channels.
- **One account, many workspaces.** A repeat client keeps a single login and
  switches between projects (the left rail).
- **Manual accounts, no email.** You create logins in the admin console and hand
  over the credentials yourself. Self-registration is disabled.
- **You (admin)** can see and post in every workspace/channel.

## Real-time

Messages broadcast over Reverb on `private-channel.{id}`, authorized per channel
membership (`routes/channels.php`). A presence channel `workspace.{id}` drives the
online roster. Sending is resilient: messages persist even if Reverb is down — the
broadcast is best-effort.

## Run locally

```bash
composer install && npm install
cp .env.example .env   # already configured for sqlite + reverb if you copied .env
php artisan key:generate
php artisan migrate --seed
composer dev           # serve + queue + logs + vite + reverb, all at once
```

Then open http://localhost:8000.

### Seeded logins

| Role   | Email                | Password   |
| ------ | -------------------- | ---------- |
| Admin  | diego@neunsoft.com   | `password` |
| Client | client@example.com   | `password` |

A sample workspace **Acme Redesign** (channels: general, design, bugs) is seeded
with both users.

## Admin console

`/admin` (admin only): create logins, create workspaces, add/remove workspace
members, create/delete channels, and toggle which members belong to each channel.
