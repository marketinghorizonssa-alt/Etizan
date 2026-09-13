# Etizan Law Performance Landing Site

Production target: `https://etizan.hositee.com`

- Node 20+ / Express
- GTM: `GTM-5ZKR4X9C`
- Riyadh and Jeddah intent landing routes
- Server-side lead proxy to the existing ETIZAN Lead Router
- `form_submit_success` is emitted only after router acknowledgement
- `whatsapp_click` and `phone_click` are separate CTA events

## Required environment variables

Copy `.env.example` into the Hostinger application environment. **Never commit the ingest token.**

- `ETIZAN_INGEST_TOKEN` — required private receiver token
- `ETIZAN_ROUTER_URL` — Apps Script receiver URL (public endpoint, no token)
- `RELEASE_SHA` — deployed Git commit SHA for `/healthz` verification
- `PORT` — supplied by Hostinger when available

## Health check

`GET /healthz` returns the release marker and whether the router secret is configured, without exposing the secret.
