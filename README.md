# Coastal Home Services — WordPress + Automation Demo

**A self-contained, Dockerized WordPress build that takes a lead from form submission to a structured record, through a Make.com scenario, and back to a confirmed sync. The code, the scenario and the screenshots are all here.**

📄 **Case study page (recommended first stop):** https://andipyk.github.io/coastal-home-services-wp-demo/

---

## The problem

Local service businesses (the demo brief here: a fictional HVAC/plumbing/electrical company) lose leads to manual busywork: a form comes in, someone copies it into a spreadsheet or CRM by hand, and nothing records whether that follow-up happened. This project builds the fix: a lead reaches WordPress as a structured record, flows into an automation platform with no copy and paste, and reports back, so the record shows when it was synced.

This mirrors, almost line for line, what a "WordPress + Automation Developer" role asks for: custom PHP, ACF + custom post types, REST APIs/webhooks, and a working Zapier/Make automation — demonstrated here as one connected system instead of four disconnected snippets.

## What I built

| | |
|---|---|
| **Tech** | WordPress · PHP (custom plugin, no page-builder-only work) · Docker Compose · MariaDB · ACF · WPForms · Elementor · REST API · Git |
| **Automation** | A Make.com scenario that ran against this site (`Custom webhook → HTTP callback → error handler → Data Store audit log → Google Sheets export`), with its execution history as evidence. Wired to a Cloudflare Tunnel so Make's cloud servers can call back into local WordPress |
| **Problem solved** | Lead capture with a verifiable audit trail: every submission becomes a structured `lead` record, gets pushed out via a signed webhook, and flips to "Synced to CRM" only once the automation platform confirms it, so an unconfirmed lead stays visibly `new` |

## Evidence

- A browser form submission → `lead` custom post created with correct ACF fields (phone, service type, status)
- The outbound webhook payload, captured externally, first against a stand-in endpoint, then against the Make.com scenario
- Make's own execution history: **Success, 1 second, 2 operations** — screenshot in `docs/make-scenario-history.jpg`
- The record flipping to `Synced to CRM` with a timestamp, ~2 seconds after submission — screenshot in `docs/lead-synced-proof.jpg`, animated walkthrough in `docs/coastal-lead-automation-demo.gif`
- No PHP warnings from the project's code with `WP_DEBUG` confirmed on (re-checked 2026-09-17 on page loads and the callback). The original run's empty `debug.log` does not count: the first stack never switched debug logging on (the case study's revision log explains how)

Full narrative, code excerpts and the debugging record: the case study page linked above.

---

## Stack in detail

- WordPress 7.1 on PHP 8.4 + MariaDB 11.8, via Docker Compose. The WordPress image (`docker/wordpress/`) adds phpredis and WP-CLI to the official one and is shared byte-for-byte with my other WordPress demos, so the web server and WP-CLI run the same image as the same user
- WPForms Lite (free) — the "Get a Free Quote" form
- ACF (free) — `lead` custom post type fields, registered in PHP (`acf_add_local_field_group`)
- Elementor (free) — the landing page (`/get-a-free-quote/`) embeds the WPForms shortcode inside a styled card
- Custom plugin (`wp-content/plugins/coastal-core/`) — no third-party automation plugin required for the WP side. It is a regular plugin rather than theme code, so switching themes never takes the lead records with it:
  - `includes/post-type.php` — registers the `lead` post type
  - `includes/acf-fields.php` — registers the ACF field group in code
  - `includes/lead-webhook.php` — on form submit: creates a `lead` post, then POSTs a JSON payload to an external automation webhook
  - `includes/rest.php` — custom REST route (`POST /wp-json/coastal/v1/lead-status`) that the automation tool calls back to flip the lead to "synced"
  - `includes/frontend-polish.php` — one CSS custom-property override fixing the active theme's global content padding site-wide
- `bin/seed-landing-page.php` — the landing page's Elementor layout, defined in code (see note below) so it's reproducible without hand-building it in the editor

## Quick start

```bash
cp .env.example .env   # fill in real values (or generate your own secrets)
./bin/setup.sh          # build image + docker compose up + WordPress install + plugins + permalinks
bin/wp eval-file bin/seed-landing-page.php --user=admin
bin/wp elementor flush_css --user=admin
```

Then:
- Site: the `WP_URL` in your `.env` (default `http://localhost:8090`)
- wp-admin: `<WP_URL>/wp-admin` (user/pass from `.env`)
- WP-CLI: `bin/wp <command>` (a throwaway container on the same image as the site)
- phpMyAdmin: `docker compose --profile tools up -d`, then `http://localhost:8091`
- Landing page: `<WP_URL>/get-a-free-quote/`
- Leads: wp-admin → **Leads** in the sidebar

Re-running `./bin/setup.sh` is safe — it skips steps that are already done.

> **Note on the `--user=admin` flag:** Elementor's `Document::save()` API, run under WP-CLI's default anonymous context, writes the layout data correctly but silently skips the step that activates it on the live front-end (the editor's own canvas preview looks right either way — only the public page is affected). Always pass `--user=admin` when re-running the seed script or `elementor flush_css`, and verify with a `curl` or browser fetch of the front-end; the editor preview is not enough.

## How the data flows

```
Visitor fills the form on /get-a-free-quote/ (WPForms + Elementor)
        │
        ▼
wpforms_process_complete hook (includes/lead-webhook.php)
        │
        ├─► wp_insert_post()  → new `lead` post + ACF fields (phone, service_type, status=new)
        │
        └─► wp_remote_post()  → JSON payload → COASTAL_WEBHOOK_URL
                                      │
                                      ▼
                         Make.com scenario: "Coastal Home Services - Lead Sync"
                         (Custom webhook → HTTP module)
                                      │
                                      ▼
                         POST back to /wp-json/coastal/v1/lead-status
                         with header  x-webhook-secret: <COASTAL_WEBHOOK_SECRET>
                                      │
                              ┌───────┴───────┐
                              ▼ (2xx)          ▼ (error)
                   lead.status → "synced"   Resume (error handler)
                   lead.synced_at → now     recovers the run instead
                              │             of failing the scenario
                              └───────┬───────┘
                                      ▼
                    Data store: audit-log every run (lead_id, occurred_at,
                    dynamic error_message: the HTTP status on success,
                    "0" sentinel on a recovered failure — never a
                    hardcoded string)
                                      │
                                      ▼ (filtered: statusCode ≠ 0)
                    Google Sheets: append a row, only for successful
                    successful syncs, never for a recovered failure
```

## What's verified

**Verified end-to-end, twice** (see `docs/lead-synced-proof.jpg` and `docs/make-scenario-history.jpg`):
- Real form submission on the live page → `lead` CPT created with correct ACF values
- Outbound webhook left the container and was received externally — first against a temporary [webhook.site](https://webhook.site) endpoint, then against the **Make.com scenario** ("Coastal Home Services - Lead Sync")
- Make's scenario runs its HTTP module and calls the inbound REST callback automatically — no manual `curl` involved on that second run
- Inbound REST callback: wrong secret → `403`, correct secret → `200` and the lead flips to "Synced to CRM" with a timestamp, confirmed in ~2 seconds
- No PHP warnings from project code with `WP_DEBUG` confirmed on (re-checked 2026-09-17). The empty `debug.log` from the original run proved nothing, because that stack never enabled debug logging

**How the Make scenario is wired up** (already done, documented for reproducing on a fresh setup):
1. Make.com scenario "Coastal Home Services - Lead Sync": **Custom webhook trigger → HTTP "Make a request"** module — `POST` to the callback route with body `{"lead_id": {{1.lead_id}}}` and header `x-webhook-secret: <COASTAL_WEBHOOK_SECRET>`.
2. An **error handler (Resume)** is attached directly to the HTTP module, so a failed callback (wrong secret, invalid `lead_id`, WordPress unreachable) recovers the run instead of leaving the scenario in an unhandled "Error" state. It substitutes `statusCode: 0` as a sentinel so downstream modules can tell a recovered failure apart from a response WordPress returned.
3. A **Data store** module logs every run — success or recovered failure — with a dynamic `error_message` (`{{"HTTP " + 3.statusCode + " (0 = recovered error)"}}`) instead of a hardcoded string, so the audit log stays accurate either way.
4. A **Google Sheets "Add a Row"** module, gated by a filter (`statusCode` not equal to `0`), appends each successful sync to a spreadsheet — a recovered failure still hits the Data Store audit trail but never shows up as a phantom row in the sheet.
5. That scenario's webhook URL is in `.env` as `COASTAL_WEBHOOK_URL`.
6. Since Make's cloud servers can't reach `localhost`, a Cloudflare quick tunnel exposes this WordPress install publicly:
   ```bash
   docker run --rm -it cloudflare/cloudflared:latest tunnel --url http://host.docker.internal:8090
   ```
   **This tunnel is ephemeral** — restarting it changes the URL, which then needs updating in the Make scenario's HTTP module, and in `COASTAL_CALLBACK_URL` in `.env` if the scenario reads the callback from the payload.

> **Two gotchas worth knowing if you extend this scenario:**
> 1. Don't place a Filter directly on the connector coming *out of* an HTTP module into its own error handler. The module's "normal" output fields (like `statusCode`) don't exist on the error path — only `{{3.Error.*}}` does — so a filter like `{{3.statusCode}} >= 400` silently evaluates false on every error and blocks the recovery route, which Make then treats as an unhandled error and can get the whole scenario auto-deactivated by Make.
> 2. `{{3.Error.*}}` fields don't survive past the error handler either — reference them only inside the Resume module's own field mappings, never in a module or filter further downstream (it silently resolves to nothing there). To carry error state downstream, have the error handler substitute a normal output field (like `statusCode: 0`) instead, and branch on that. Also: Make's mapping fields don't reliably persist a compound `if(...)` formula typed by hand — plain `{{"literal " + token}}` concatenation is what survives a save.

**Not included:** a Slack or email notification step. Make's built-in Email module needs a configured SMTP or OAuth-connected mailbox, which it does not make obvious at first, and that is not something to wire up without the account owner present. The scenario is the webhook and callback loop, which is the part that demonstrates the "API/webhook integration" and "Make automation" skills. A notification step is a two-minute addition once a mailbox or Slack workspace is connected.

If your actual client stack differs (Gravity Forms / Fluent Forms / Bricks instead of WPForms/Elementor), only the form-submission hook name changes (e.g. `gform_after_submission`) — the CPT/ACF/REST layer stays the same.

## Project layout

```
compose.yaml                 # WordPress + MariaDB, WP-CLI (profile cli), phpMyAdmin (profile tools)
docker/wordpress/            # the shared WordPress image: phpredis + WP-CLI on the official image
.env.example                 # copy to .env
bin/setup.sh                 # one-shot provisioning script (idempotent)
bin/wp                       # WP-CLI wrapper
bin/seed-landing-page.php    # content-as-code seed script (Elementor layout)
wp-content/plugins/coastal-core/  # the custom PHP — CPT, ACF fields, webhook, REST route, frontend CSS fix
docs/                        # evidence: screenshots + demo GIF
portfolio/                   # the case-study page (static HTML, deployed to GitHub Pages)
.github/workflows/pages.yml  # publishes portfolio/ on every push to main
```
