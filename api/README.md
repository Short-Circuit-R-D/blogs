# Lighting CMS API (v1)

**Swagger UI:** `/api/docs` (also `/api/swagger`)  
**OpenAPI JSON:** `/api/openapi.json` (also `/api/v1/openapi.json`)

Local example: `http://localhost/LightSCenter_roles_topics_update/api/docs`  
Production: `https://blogs.shortcircuit.company/api/docs`

The Swagger page does not require a token. **Try it out** does — click **Authorize** and paste `API_TOKEN` from `.env` (Bearer or `X-API-Token`).

---

Base URL (production): `https://blogs.shortcircuit.company/api/v1`  
Local example: `http://localhost/LightSCenter_roles_topics_update/api/v1`

All requests need the secret from `.env`:

```
API_TOKEN=your-token
```

Send it as either:

```
Authorization: Bearer API_TOKEN
```

or

```
X-API-Token: API_TOKEN
```

JSON in, JSON out. `PUT` / `PATCH` / `DELETE` are supported. If the host only allows GET/POST, send `POST` with header `X-HTTP-Method-Override: PUT`.

---

## Catalogue

```
GET /api/v1
```

---

## Resources (CRUD)

| Collection | Create | Read one | Update | Delete |
|---|---|---|---|---|
| `/articles` | POST | GET `/{id}` | PUT/PATCH `/{id}` | DELETE `/{id}` |
| `/articles/{id}/ranges` | POST | GET `/{rid}` | PUT/PATCH | DELETE |
| `/articles/{id}/comments` | POST | GET `/{cid}` | PUT/PATCH | DELETE |
| `/standards` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/terms` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/tools` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/events` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/events/{id}/images` | POST | GET `/{iid}` | PUT/PATCH | DELETE |
| `/topics` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/users` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/contacts` | POST | GET `/{id}` | PUT/PATCH | DELETE |
| `/admins` | POST (sends invite email) | GET `/{id}` | PUT/PATCH | DELETE |
| `/roles` | — | GET `/{role}` | PUT/PATCH `/{role}` | — |

List endpoints accept `?page=1&per_page=20&q=search`.  
Articles, standards, tools, events, terms also accept `?is_published=1`.  
Topics accept `?status=pending|approved|rejected`.

`password_hash`, `invite_token_hash`, and `unsubscribe_token` are never returned.

Creating an admin sends the same one-time confirm-email invite as the dashboard. They cannot log in to `/admin` until they confirm.

---

## Examples

Windows **CMD** (use double quotes; `curl.exe` so PowerShell does not alias `curl`):

```bat
curl.exe -X GET "http://localhost/LightSCenter_roles_topics_update/api/v1/" -H "accept: application/json" -H "Authorization: Bearer YOUR_TOKEN"
```

Swagger's default curl uses single quotes, which CMD treats as part of the URL and produces `Port number was not a decimal number`. After Execute, use the **cURL (CMD)** or **cURL (PowerShell)** snippet.

```bash
TOKEN=your-token
BASE=https://blogs.shortcircuit.company/api/v1

# List articles
curl -H "Authorization: Bearer $TOKEN" "$BASE/articles"

# Create an article
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"title":"CRI","excerpt":"Colour rendering.","intro":"...","tag":"CRI","is_published":true}' \
  "$BASE/articles"

# Update
curl -X PATCH -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"is_published":false}' "$BASE/articles/1"

# Delete
curl -X DELETE -H "Authorization: Bearer $TOKEN" "$BASE/articles/1"
```

Success:

```json
{ "ok": true, "version": "v1", "data": { } }
```

Error:

```json
{ "ok": false, "version": "v1", "error": { "code": "unauthorized", "message": "..." } }
```
