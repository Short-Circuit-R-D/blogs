<?php
/**
 * OpenAPI 3 spec + Swagger UI for /api/docs and /api/openapi.json
 */

function apiDocsPath(): string
{
    return rtrim(rawurldecode((string)(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '')), '/');
}

function apiServeDocsIfRequested(): void
{
    $path = apiDocsPath();
    if (preg_match('#/api/(docs|swagger)$#i', $path)) {
        apiRenderSwaggerUi();
    }
    if (preg_match('#/api/(?:v[0-9]+/)?openapi(?:\.json)?$#i', $path)) {
        apiServeOpenApiJson();
    }
}

function apiServeOpenApiJson(): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: public, max-age=60');
    echo json_encode(apiOpenApiSpec(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function apiRenderSwaggerUi(): void
{
    $specUrl = appUrl('api/openapi.json');
    $title = 'Lighting CMS API — Swagger';
    $logo = appUrl('logo.svg');
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    $specUrlJs = json_encode($specUrl, JSON_UNESCAPED_SLASHES);
    echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>
  <link rel="icon" href="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '">
  <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
  <style>
    html { box-sizing: border-box; overflow-y: scroll; }
    *, *:before, *:after { box-sizing: inherit; }
    body { margin: 0; background: #fafafa; }
    .sc-docs-bar {
      display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
      padding: 12px 20px; background: #111; color: #f4f1ea;
      font-family: system-ui, sans-serif; font-size: 14px;
    }
    .sc-docs-bar img { height: 28px; width: auto; }
    .sc-docs-bar strong { font-size: 16px; letter-spacing: .02em; margin-right: 8px; }
    .sc-token {
      display: flex; align-items: center; gap: 8px; flex: 1 1 420px;
      min-width: 280px;
    }
    .sc-token label { white-space: nowrap; opacity: .85; }
    .sc-token input[type="password"],
    .sc-token input[type="text"] {
      flex: 1; min-width: 160px; padding: 8px 10px; border: 1px solid #444;
      border-radius: 6px; background: #1c1c1c; color: #f4f1ea; font: inherit;
    }
    .sc-token button {
      padding: 8px 14px; border: 0; border-radius: 6px; cursor: pointer;
      background: #c9a227; color: #111; font-weight: 600; font: inherit;
    }
    .sc-token button:hover { filter: brightness(1.08); }
    .sc-token .sc-status { font-size: 12px; min-width: 9em; }
    .sc-token .ok { color: #8fd18f; }
    .sc-token .bad { color: #f0a0a0; }
    .sc-show { display: flex; align-items: center; gap: 4px; opacity: .8; white-space: nowrap; }
    .swagger-ui .topbar { display: none; }
    .swagger-ui .info { margin: 24px 0 10px; }
    .swagger-ui .scheme-container { display: none; }
  </style>
</head>
<body>
  <div class="sc-docs-bar">
    <img src="' . htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') . '" alt="">
    <strong>Lighting CMS API</strong>
    <form class="sc-token" id="scTokenForm" autocomplete="off">
      <label for="scApiToken">API token</label>
      <input id="scApiToken" name="api_token" type="password" placeholder="Paste API_TOKEN from .env" spellcheck="false">
      <label class="sc-show"><input type="checkbox" id="scShowToken"> Show</label>
      <button type="submit">Use token</button>
      <span class="sc-status bad" id="scTokenStatus">No token — Execute will return 401</span>
    </form>
  </div>
  <div id="swagger-ui"></div>
  <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" crossorigin></script>
  <script>
    (function () {
      var KEY = "sc_api_token";
      var input = document.getElementById("scApiToken");
      var status = document.getElementById("scTokenStatus");
      var form = document.getElementById("scTokenForm");
      var show = document.getElementById("scShowToken");

      function currentToken() {
        return (input.value || "").trim();
      }

      function setStatus() {
        if (currentToken()) {
          status.textContent = "Token set — Execute will send it";
          status.className = "sc-status ok";
        } else {
          status.textContent = "No token — Execute will return 401";
          status.className = "sc-status bad";
        }
      }

      function applyToken(save) {
        var token = currentToken();
        if (save) {
          try {
            if (token) sessionStorage.setItem(KEY, token);
            else sessionStorage.removeItem(KEY);
          } catch (e) {}
        }
        if (window.ui && token) {
          try { window.ui.preauthorizeApiKey("bearerAuth", token); } catch (e) {}
        }
        setStatus();
      }

      try {
        var saved = sessionStorage.getItem(KEY);
        if (saved) input.value = saved;
      } catch (e) {}

      show.addEventListener("change", function () {
        input.type = show.checked ? "text" : "password";
      });
      form.addEventListener("submit", function (e) {
        e.preventDefault();
        applyToken(true);
      });
      input.addEventListener("change", function () { applyToken(true); });

      window.ui = SwaggerUIBundle({
        url: ' . $specUrlJs . ',
        dom_id: "#swagger-ui",
        deepLinking: true,
        filter: true,
        tryItOutEnabled: true,
        persistAuthorization: true,
        displayRequestDuration: true,
        withCredentials: false,
        supportedSubmitMethods: ["get","put","post","delete","patch"],
        presets: [SwaggerUIBundle.presets.apis],
        layout: "BaseLayout",
        validatorUrl: null,
        requestSnippetsEnabled: true,
        requestSnippets: {
          defaultExpanded: true,
          languages: ["curl_cmd", "curl_powershell", "curl_bash"]
        },
        onComplete: function () { applyToken(false); },
        requestInterceptor: function (req) {
          req.headers = req.headers || {};
          var token = currentToken();
          if (token) {
            req.headers.Authorization = "Bearer " + token;
            req.headers["X-API-Token"] = token;
          }
          if (req.headers.Authorization && req.headers.Authorization.indexOf("Bearer Bearer ") === 0) {
            req.headers.Authorization = req.headers.Authorization.replace("Bearer Bearer ", "Bearer ");
          }
          return req;
        }
      });
    })();
  </script>
</body>
</html>';
    exit;
}

function oasRef(string $name): array
{
    return ['$ref' => '#/components/schemas/' . $name];
}

function oasRespRef(string $name): array
{
    return ['$ref' => '#/components/responses/' . $name];
}

function oasParamRef(string $name): array
{
    return ['$ref' => '#/components/parameters/' . $name];
}

function oasFlag(string $description): array
{
    return [
        'oneOf' => [
            ['type' => 'boolean'],
            ['type' => 'integer', 'enum' => [0, 1]],
        ],
        'description' => $description . ' Write as true/false or 1/0. Responses usually return 0 or 1.',
    ];
}

function oasEnvelope(array $dataSchema, bool $list = false): array
{
    $props = [
        'ok' => ['type' => 'boolean', 'example' => true],
        'version' => ['type' => 'string', 'example' => 'v1'],
        'data' => $dataSchema,
    ];
    $schema = [
        'type' => 'object',
        'required' => ['ok', 'version', 'data'],
        'properties' => $props,
    ];
    if ($list) {
        $schema['properties']['meta'] = oasRef('PaginationMeta');
    }
    return $schema;
}

function oasJson(array $schema, string $description): array
{
    return [
        'description' => $description,
        'content' => [
            'application/json' => ['schema' => $schema],
        ],
    ];
}

function oasBody(string $schemaName, string $description = 'JSON body'): array
{
    return [
        'required' => true,
        'description' => $description,
        'content' => [
            'application/json' => ['schema' => oasRef($schemaName)],
        ],
    ];
}

function oasCrudCollection(string $tag, string $summaryNoun, string $schema, string $writeSchema, array $listParams, array $extra = []): array
{
    $getDesc = $extra['listDescription'] ?? ('Paginated list of ' . $summaryNoun . '.');
    $postDesc = $extra['createDescription'] ?? ('Create a ' . $summaryNoun . ' record.');
    return [
        'get' => [
            'tags' => [$tag],
            'summary' => 'List ' . $summaryNoun,
            'description' => $getDesc,
            'operationId' => $extra['listOp'] ?? ('list' . str_replace(' ', '', ucwords($summaryNoun))),
            'parameters' => $listParams,
            'responses' => [
                '200' => oasJson(oasEnvelope(['type' => 'array', 'items' => oasRef($schema)], true), 'Paginated list'),
                '401' => oasRespRef('Unauthorized'),
                '503' => oasRespRef('TokenMissing'),
            ],
        ],
        'post' => [
            'tags' => [$tag],
            'summary' => 'Create ' . $summaryNoun,
            'description' => $postDesc,
            'operationId' => $extra['createOp'] ?? ('create' . str_replace(' ', '', ucwords($summaryNoun))),
            'requestBody' => oasBody($writeSchema),
            'responses' => [
                '201' => oasJson(oasEnvelope(oasRef($schema)), 'Created'),
                '401' => oasRespRef('Unauthorized'),
                '422' => oasRespRef('Validation'),
                '500' => oasRespRef('ServerError'),
            ],
        ],
    ];
}

function oasCrudItem(string $tag, string $summaryNoun, string $schema, string $writeSchema, array $idParams, array $extra = []): array
{
    $noun = rtrim($summaryNoun, 's');
    $patchSchema = preg_replace('/Write$/', 'Patch', $writeSchema) ?: $writeSchema;
    return [
        'get' => [
            'tags' => [$tag],
            'summary' => 'Get ' . $noun,
            'operationId' => $extra['getOp'] ?? ('get' . str_replace(' ', '', ucwords($noun))),
            'parameters' => $idParams,
            'responses' => [
                '200' => oasJson(oasEnvelope(oasRef($schema)), 'Record'),
                '401' => oasRespRef('Unauthorized'),
                '404' => oasRespRef('NotFound'),
            ],
        ],
        'put' => [
            'tags' => [$tag],
            'summary' => 'Replace ' . $noun,
            'description' => 'Same as PATCH. Send only the fields to change.',
            'operationId' => $extra['putOp'] ?? ('put' . str_replace(' ', '', ucwords($noun))),
            'parameters' => $idParams,
            'requestBody' => oasBody($patchSchema),
            'responses' => [
                '200' => oasJson(oasEnvelope(oasRef($schema)), 'Updated'),
                '401' => oasRespRef('Unauthorized'),
                '404' => oasRespRef('NotFound'),
                '422' => oasRespRef('Validation'),
            ],
        ],
        'patch' => [
            'tags' => [$tag],
            'summary' => 'Update ' . $noun,
            'operationId' => $extra['patchOp'] ?? ('patch' . str_replace(' ', '', ucwords($noun))),
            'parameters' => $idParams,
            'requestBody' => oasBody($patchSchema),
            'responses' => [
                '200' => oasJson(oasEnvelope(oasRef($schema)), 'Updated'),
                '401' => oasRespRef('Unauthorized'),
                '404' => oasRespRef('NotFound'),
                '422' => oasRespRef('Validation'),
            ],
        ],
        'delete' => [
            'tags' => [$tag],
            'summary' => 'Delete ' . $noun,
            'operationId' => $extra['deleteOp'] ?? ('delete' . str_replace(' ', '', ucwords($noun))),
            'parameters' => $idParams,
            'responses' => [
                '200' => oasJson(oasEnvelope(oasRef('Deleted')), 'Deleted'),
                '401' => oasRespRef('Unauthorized'),
                '404' => oasRespRef('NotFound'),
                '409' => oasRespRef('Conflict'),
            ],
        ],
    ];
}

function apiOpenApiServerUrl(): string
{
    return rtrim(appBase(), '/') . '/api/v1';
}

function apiOpenApiSpec(): array
{
    $idParam = [oasParamRef('Id')];
    $articleId = [oasParamRef('ArticleId')];
    $eventId = [oasParamRef('EventId')];
    $pageQPub = [oasParamRef('Page'), oasParamRef('PerPage'), oasParamRef('Q'), oasParamRef('IsPublished')];
    $pageQ = [oasParamRef('Page'), oasParamRef('PerPage'), oasParamRef('Q')];

    $spec = [
        'openapi' => '3.0.3',
        'info' => [
            'title' => 'Short Circuit Lighting CMS API',
            'version' => 'v1',
            'description' => <<<MD
Versioned REST API for the Short Circuit lighting technical-data CMS.

**Auth (required on every operation except this spec / Swagger UI)**  
Put `API_TOKEN` from `.env` in either:

- `Authorization: Bearer <token>`
- `X-API-Token: <token>`

Use **Authorize** in Swagger UI so Try it out sends the header.

**Conventions**

- JSON request and response bodies.
- Success: `{ "ok": true, "version": "v1", "data": ... }` plus `meta` on lists.
- Lists: `page` (default 1), `per_page` (default 20, max 100), `q` search.
- `password_hash`, `invite_token_hash`, and `unsubscribe_token` are never returned.
- If the host only allows GET/POST, send `POST` with `X-HTTP-Method-Override: PUT|PATCH|DELETE`.
- Creating an admin sends the same one-time confirm-email invite as the dashboard.
MD,
            'contact' => [
                'name' => 'Short Circuit Company',
                'url' => 'https://blogs.shortcircuit.company',
            ],
        ],
        'servers' => [
            ['url' => apiOpenApiServerUrl(), 'description' => 'This host (same origin — use this in Swagger)'],
            ['url' => 'https://blogs.shortcircuit.company/api/v1', 'description' => 'Production'],
        ],
        'tags' => [
            ['name' => 'Catalogue', 'description' => 'API index'],
            ['name' => 'Articles', 'description' => 'Lighting parameter / standards articles'],
            ['name' => 'Article ranges', 'description' => 'Recommended-range rows nested under an article'],
            ['name' => 'Article comments', 'description' => 'Discussion comments on an article'],
            ['name' => 'Standards', 'description' => 'Lighting standards (EN 12464, IES, WELL, …)'],
            ['name' => 'Terms', 'description' => 'Terminology comparison matrix'],
            ['name' => 'Tools', 'description' => 'Design tools carousel'],
            ['name' => 'Events', 'description' => 'Booth / event pages'],
            ['name' => 'Event images', 'description' => 'Gallery images nested under an event'],
            ['name' => 'Topics', 'description' => 'Moderated community topics'],
            ['name' => 'Users', 'description' => 'Public site accounts'],
            ['name' => 'Contacts', 'description' => 'Contact Us messages'],
            ['name' => 'Admins', 'description' => 'CMS admin users (invite on create)'],
            ['name' => 'Roles', 'description' => 'Role permission flags (string primary key)'],
        ],
        'security' => [
            ['bearerAuth' => []],
        ],
        'paths' => [
            '/' => [
                'get' => [
                    'tags' => ['Catalogue'],
                    'summary' => 'API catalogue',
                    'operationId' => 'getCatalogue',
                    'responses' => [
                        '200' => oasJson(oasEnvelope([
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'auth' => ['type' => 'string'],
                                'docs' => ['type' => 'string', 'format' => 'uri'],
                                'openapi' => ['type' => 'string', 'format' => 'uri'],
                                'resources' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
                            ],
                        ]), 'Catalogue of resources'),
                        '401' => oasRespRef('Unauthorized'),
                    ],
                ],
            ],
            '/articles' => oasCrudCollection('Articles', 'articles', 'Article', 'ArticleWrite', $pageQPub, [
                'createDescription' => 'Create an article. `slug` is generated from `title` if omitted. Optional `ranges[]` replaces the recommended-range table.',
            ]),
            '/articles/{id}' => oasCrudItem('Articles', 'articles', 'Article', 'ArticleWrite', $idParam),
            '/articles/{id}/ranges' => [
                'get' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'List article ranges',
                    'operationId' => 'listArticleRanges',
                    'parameters' => $articleId,
                    'responses' => [
                        '200' => oasJson(oasEnvelope(['type' => 'array', 'items' => oasRef('ArticleRange')]), 'Ranges'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'post' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'Create article range',
                    'operationId' => 'createArticleRange',
                    'parameters' => $articleId,
                    'requestBody' => oasBody('ArticleRangeWrite'),
                    'responses' => [
                        '201' => oasJson(oasEnvelope(oasRef('ArticleRange')), 'Created'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                        '422' => oasRespRef('Validation'),
                    ],
                ],
            ],
            '/articles/{id}/ranges/{rangeId}' => [
                'get' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'Get article range',
                    'operationId' => 'getArticleRange',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('RangeId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleRange')), 'Range'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'put' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'Replace article range',
                    'operationId' => 'putArticleRange',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('RangeId')],
                    'requestBody' => oasBody('ArticleRangePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleRange')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'patch' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'Update article range',
                    'operationId' => 'patchArticleRange',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('RangeId')],
                    'requestBody' => oasBody('ArticleRangePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleRange')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'delete' => [
                    'tags' => ['Article ranges'],
                    'summary' => 'Delete article range',
                    'operationId' => 'deleteArticleRange',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('RangeId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Deleted')), 'Deleted'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
            ],
            '/articles/{id}/comments' => [
                'get' => [
                    'tags' => ['Article comments'],
                    'summary' => 'List article comments',
                    'operationId' => 'listArticleComments',
                    'parameters' => $articleId,
                    'responses' => [
                        '200' => oasJson(oasEnvelope(['type' => 'array', 'items' => oasRef('ArticleComment')]), 'Comments'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'post' => [
                    'tags' => ['Article comments'],
                    'summary' => 'Create article comment',
                    'operationId' => 'createArticleComment',
                    'parameters' => $articleId,
                    'requestBody' => oasBody('ArticleCommentWrite'),
                    'responses' => [
                        '201' => oasJson(oasEnvelope(oasRef('ArticleComment')), 'Created'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                        '422' => oasRespRef('Validation'),
                    ],
                ],
            ],
            '/articles/{id}/comments/{commentId}' => [
                'get' => [
                    'tags' => ['Article comments'],
                    'summary' => 'Get article comment',
                    'operationId' => 'getArticleComment',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('CommentId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleComment')), 'Comment'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'put' => [
                    'tags' => ['Article comments'],
                    'summary' => 'Replace article comment',
                    'operationId' => 'putArticleComment',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('CommentId')],
                    'requestBody' => oasBody('ArticleCommentPatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleComment')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'patch' => [
                    'tags' => ['Article comments'],
                    'summary' => 'Update article comment',
                    'operationId' => 'patchArticleComment',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('CommentId')],
                    'requestBody' => oasBody('ArticleCommentPatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('ArticleComment')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'delete' => [
                    'tags' => ['Article comments'],
                    'summary' => 'Delete article comment',
                    'operationId' => 'deleteArticleComment',
                    'parameters' => [oasParamRef('ArticleId'), oasParamRef('CommentId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Deleted')), 'Deleted'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
            ],
            '/standards' => oasCrudCollection('Standards', 'standards', 'Standard', 'StandardWrite', $pageQPub),
            '/standards/{id}' => oasCrudItem('Standards', 'standards', 'Standard', 'StandardWrite', $idParam),
            '/terms' => oasCrudCollection('Terms', 'terms', 'Term', 'TermWrite', $pageQPub, [
                'listOp' => 'listTerms',
                'createOp' => 'createTerm',
            ]),
            '/terms/{id}' => oasCrudItem('Terms', 'terms', 'Term', 'TermWrite', $idParam, [
                'getOp' => 'getTerm',
                'putOp' => 'putTerm',
                'patchOp' => 'patchTerm',
                'deleteOp' => 'deleteTerm',
            ]),
            '/tools' => oasCrudCollection('Tools', 'tools', 'Tool', 'ToolWrite', $pageQPub),
            '/tools/{id}' => oasCrudItem('Tools', 'tools', 'Tool', 'ToolWrite', $idParam),
            '/events' => oasCrudCollection('Events', 'events', 'Event', 'EventWrite', $pageQPub),
            '/events/{id}' => oasCrudItem('Events', 'events', 'Event', 'EventWrite', $idParam),
            '/events/{id}/images' => [
                'get' => [
                    'tags' => ['Event images'],
                    'summary' => 'List event images',
                    'operationId' => 'listEventImages',
                    'parameters' => $eventId,
                    'responses' => [
                        '200' => oasJson(oasEnvelope(['type' => 'array', 'items' => oasRef('EventImage')]), 'Images'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'post' => [
                    'tags' => ['Event images'],
                    'summary' => 'Create event image',
                    'description' => '`image_url` is accepted as an alias of `image_path`.',
                    'operationId' => 'createEventImage',
                    'parameters' => $eventId,
                    'requestBody' => oasBody('EventImageWrite'),
                    'responses' => [
                        '201' => oasJson(oasEnvelope(oasRef('EventImage')), 'Created'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                        '422' => oasRespRef('Validation'),
                    ],
                ],
            ],
            '/events/{id}/images/{imageId}' => [
                'get' => [
                    'tags' => ['Event images'],
                    'summary' => 'Get event image',
                    'operationId' => 'getEventImage',
                    'parameters' => [oasParamRef('EventId'), oasParamRef('ImageId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('EventImage')), 'Image'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'put' => [
                    'tags' => ['Event images'],
                    'summary' => 'Replace event image',
                    'operationId' => 'putEventImage',
                    'parameters' => [oasParamRef('EventId'), oasParamRef('ImageId')],
                    'requestBody' => oasBody('EventImagePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('EventImage')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'patch' => [
                    'tags' => ['Event images'],
                    'summary' => 'Update event image',
                    'operationId' => 'patchEventImage',
                    'parameters' => [oasParamRef('EventId'), oasParamRef('ImageId')],
                    'requestBody' => oasBody('EventImagePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('EventImage')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'delete' => [
                    'tags' => ['Event images'],
                    'summary' => 'Delete event image',
                    'operationId' => 'deleteEventImage',
                    'parameters' => [oasParamRef('EventId'), oasParamRef('ImageId')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Deleted')), 'Deleted'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
            ],
            '/topics' => oasCrudCollection('Topics', 'topics', 'Topic', 'TopicWrite', [
                oasParamRef('Page'), oasParamRef('PerPage'), oasParamRef('Q'), oasParamRef('Status'),
            ], [
                'createDescription' => '`user_id` must be a public user. Slug is generated from title if omitted. Status defaults to pending unless sent.',
            ]),
            '/topics/{id}' => oasCrudItem('Topics', 'topics', 'Topic', 'TopicWrite', $idParam),
            '/users' => oasCrudCollection('Users', 'users', 'User', 'UserWrite', $pageQ, [
                'createDescription' => 'Create a public account. Optional `password` (min 8 on update). `password_hash` is never returned.',
            ]),
            '/users/{id}' => oasCrudItem('Users', 'users', 'User', 'UserWrite', $idParam),
            '/contacts' => oasCrudCollection('Contacts', 'contact messages', 'Contact', 'ContactWrite', $pageQ, [
                'listOp' => 'listContacts',
                'createOp' => 'createContact',
            ]),
            '/contacts/{id}' => oasCrudItem('Contacts', 'contact messages', 'Contact', 'ContactWrite', $idParam, [
                'getOp' => 'getContact',
                'putOp' => 'putContact',
                'patchOp' => 'patchContact',
                'deleteOp' => 'deleteContact',
            ]),
            '/admins' => oasCrudCollection('Admins', 'admins', 'Admin', 'AdminWrite', $pageQ, [
                'createDescription' => 'Creates a CMS admin and emails a one-time confirm link (72h). They cannot log in to /admin until they set a password via that link.',
            ]),
            '/admins/{id}' => oasCrudItem('Admins', 'admins', 'Admin', 'AdminWrite', $idParam, [
                'deleteOp' => 'deleteAdmin',
            ]),
            '/roles' => [
                'get' => [
                    'tags' => ['Roles'],
                    'summary' => 'List role permissions',
                    'operationId' => 'listRoles',
                    'parameters' => $pageQ,
                    'responses' => [
                        '200' => oasJson(oasEnvelope(['type' => 'array', 'items' => oasRef('Role')], true), 'Roles'),
                        '401' => oasRespRef('Unauthorized'),
                    ],
                ],
            ],
            '/roles/{role}' => [
                'get' => [
                    'tags' => ['Roles'],
                    'summary' => 'Get role permissions',
                    'operationId' => 'getRole',
                    'parameters' => [oasParamRef('RoleKey')],
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Role')), 'Role'),
                        '401' => oasRespRef('Unauthorized'),
                        '404' => oasRespRef('NotFound'),
                    ],
                ],
                'put' => [
                    'tags' => ['Roles'],
                    'summary' => 'Replace role permissions',
                    'description' => 'The `admin` role cannot be reduced via the API.',
                    'operationId' => 'putRole',
                    'parameters' => [oasParamRef('RoleKey')],
                    'requestBody' => oasBody('RolePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Role')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '403' => oasRespRef('Forbidden'),
                        '404' => oasRespRef('NotFound'),
                        '422' => oasRespRef('Validation'),
                    ],
                ],
                'patch' => [
                    'tags' => ['Roles'],
                    'summary' => 'Update role permissions',
                    'description' => 'The `admin` role cannot be reduced via the API.',
                    'operationId' => 'patchRole',
                    'parameters' => [oasParamRef('RoleKey')],
                    'requestBody' => oasBody('RolePatch'),
                    'responses' => [
                        '200' => oasJson(oasEnvelope(oasRef('Role')), 'Updated'),
                        '401' => oasRespRef('Unauthorized'),
                        '403' => oasRespRef('Forbidden'),
                        '404' => oasRespRef('NotFound'),
                        '422' => oasRespRef('Validation'),
                    ],
                ],
            ],
        ],
        'components' => apiOpenApiComponents(),
    ];

    foreach ($spec['components']['schemas'] as $name => $schema) {
        if (!str_ends_with($name, 'Write') || !is_array($schema)) {
            continue;
        }
        $patch = $schema;
        unset($patch['required']);
        $spec['components']['schemas'][substr($name, 0, -5) . 'Patch'] = $patch;
    }

    return $spec;
}

function apiOpenApiComponents(): array
{
    $dt = ['type' => 'string', 'format' => 'date-time', 'nullable' => true];
    $error = [
        'type' => 'object',
        'properties' => [
            'ok' => ['type' => 'boolean', 'example' => false],
            'version' => ['type' => 'string', 'example' => 'v1'],
            'error' => [
                'type' => 'object',
                'properties' => [
                    'code' => ['type' => 'string', 'example' => 'unauthorized'],
                    'message' => ['type' => 'string'],
                ],
            ],
            'fields' => ['type' => 'array', 'items' => ['type' => 'string']],
        ],
    ];

    return [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'API_TOKEN',
                'description' => 'Value of API_TOKEN from .env. Click Authorize and paste the token only (Swagger adds Bearer).',
            ],
            'apiTokenHeader' => [
                'type' => 'apiKey',
                'in' => 'header',
                'name' => 'X-API-Token',
                'description' => 'Alternative to Authorization: Bearer.',
            ],
        ],
        'parameters' => [
            'Id' => [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'ArticleId' => [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'description' => 'Article id',
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'RangeId' => [
                'name' => 'rangeId',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'CommentId' => [
                'name' => 'commentId',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'EventId' => [
                'name' => 'id',
                'in' => 'path',
                'required' => true,
                'description' => 'Event id',
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'ImageId' => [
                'name' => 'imageId',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'integer', 'minimum' => 1],
            ],
            'RoleKey' => [
                'name' => 'role',
                'in' => 'path',
                'required' => true,
                'schema' => ['type' => 'string', 'enum' => ['client', 'employee', 'leader', 'admin']],
            ],
            'Page' => [
                'name' => 'page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'default' => 1],
            ],
            'PerPage' => [
                'name' => 'per_page',
                'in' => 'query',
                'schema' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 20],
            ],
            'Q' => [
                'name' => 'q',
                'in' => 'query',
                'description' => 'Case-insensitive search across resource text fields.',
                'schema' => ['type' => 'string'],
            ],
            'IsPublished' => [
                'name' => 'is_published',
                'in' => 'query',
                'schema' => ['oneOf' => [['type' => 'boolean'], ['type' => 'integer', 'enum' => [0, 1]]]],
            ],
            'Status' => [
                'name' => 'status',
                'in' => 'query',
                'schema' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']],
            ],
        ],
        'responses' => [
            'Unauthorized' => oasJson($error, 'Missing or invalid API token'),
            'Forbidden' => oasJson($error, 'Not allowed'),
            'NotFound' => oasJson($error, 'Record not found'),
            'Validation' => oasJson($error, 'Validation error'),
            'Conflict' => oasJson($error, 'Conflict (e.g. cannot delete the last CMS admin)'),
            'ServerError' => oasJson($error, 'Server or database error'),
            'TokenMissing' => oasJson($error, 'API_TOKEN is not configured in .env'),
        ],
        'schemas' => [
            'PaginationMeta' => [
                'type' => 'object',
                'properties' => [
                    'page' => ['type' => 'integer', 'example' => 1],
                    'per_page' => ['type' => 'integer', 'example' => 20],
                    'total' => ['type' => 'integer'],
                    'total_pages' => ['type' => 'integer'],
                ],
            ],
            'Deleted' => [
                'type' => 'object',
                'properties' => [
                    'deleted' => ['type' => 'boolean', 'example' => true],
                    'id' => ['type' => 'integer'],
                ],
            ],
            'ArticleRangeWrite' => [
                'type' => 'object',
                'required' => ['stage_label', 'environment_label', 'range_text'],
                'properties' => [
                    'stage_label' => ['type' => 'string', 'maxLength' => 80, 'example' => 'Office'],
                    'environment_label' => ['type' => 'string', 'maxLength' => 80, 'example' => 'Writing / reading'],
                    'range_text' => ['type' => 'string', 'maxLength' => 120, 'example' => '500 lx'],
                    'notes' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'sort_order' => ['type' => 'integer'],
                    'stage' => ['type' => 'string', 'description' => 'Alias of stage_label (nested create via article only)'],
                    'env' => ['type' => 'string', 'description' => 'Alias of environment_label'],
                    'range' => ['type' => 'string', 'description' => 'Alias of range_text'],
                ],
            ],
            'ArticleRange' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'article_id' => ['type' => 'integer'],
                        ],
                    ],
                    oasRef('ArticleRangeWrite'),
                ],
            ],
            'ArticleCommentWrite' => [
                'type' => 'object',
                'required' => ['body'],
                'properties' => [
                    'user_id' => ['type' => 'integer', 'nullable' => true],
                    'body' => ['type' => 'string', 'example' => 'Helpful CRI explanation.'],
                ],
            ],
            'ArticleComment' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'article_id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer', 'nullable' => true],
                    'body' => ['type' => 'string'],
                    'created_at' => $dt,
                ],
            ],
            'ArticleWrite' => [
                'type' => 'object',
                'required' => ['title', 'excerpt', 'intro'],
                'properties' => [
                    'title' => ['type' => 'string', 'maxLength' => 160, 'example' => 'Colour rendering (CRI)'],
                    'excerpt' => ['type' => 'string', 'maxLength' => 300],
                    'intro' => ['type' => 'string'],
                    'slug' => ['type' => 'string', 'maxLength' => 80, 'description' => 'Auto-generated from title if omitted'],
                    'tag' => ['type' => 'string', 'maxLength' => 40, 'example' => 'CRI'],
                    'icon' => ['type' => 'string', 'maxLength' => 40],
                    'image_url' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                    'why_text' => ['type' => 'string'],
                    'physical_text' => ['type' => 'string', 'nullable' => true],
                    'physio_text' => ['type' => 'string', 'nullable' => true],
                    'psycho_text' => ['type' => 'string', 'nullable' => true],
                    'formula_text' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'formula_note' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'simulator_url' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'simulator_label' => ['type' => 'string', 'maxLength' => 120, 'nullable' => true],
                    'is_published' => oasFlag('Whether the article is public.'),
                    'sort_order' => ['type' => 'integer'],
                    'ranges' => [
                        'type' => 'array',
                        'description' => 'On create, optional. On update, if present, replaces all range rows.',
                        'items' => oasRef('ArticleRangeWrite'),
                    ],
                ],
                'example' => [
                    'title' => 'Colour rendering (CRI)',
                    'excerpt' => 'How faithfully a source reveals object colours.',
                    'intro' => 'CRI compares a source to a reference illuminant.',
                    'tag' => 'CRI',
                    'is_published' => true,
                    'ranges' => [
                        [
                            'stage_label' => 'Retail',
                            'environment_label' => 'Colour-critical',
                            'range_text' => 'Ra ≥ 90',
                        ],
                    ],
                ],
            ],
            'Article' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'notified_at' => $dt,
                            'created_at' => $dt,
                            'updated_at' => $dt,
                            'ranges' => ['type' => 'array', 'items' => oasRef('ArticleRange')],
                            'comments' => ['type' => 'array', 'items' => oasRef('ArticleComment')],
                        ],
                    ],
                    oasRef('ArticleWrite'),
                ],
            ],
            'StandardWrite' => [
                'type' => 'object',
                'required' => ['code', 'name', 'description'],
                'properties' => [
                    'code' => ['type' => 'string', 'maxLength' => 60, 'example' => 'EN 12464-1'],
                    'name' => ['type' => 'string', 'maxLength' => 160],
                    'region' => ['type' => 'string', 'maxLength' => 80, 'nullable' => true, 'example' => 'EU'],
                    'description' => ['type' => 'string'],
                    'official_url' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'is_published' => oasFlag('Whether the standard is public.'),
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'Standard' => [
                'allOf' => [
                    ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'created_at' => $dt]],
                    oasRef('StandardWrite'),
                ],
            ],
            'TermWrite' => [
                'type' => 'object',
                'required' => ['parameter'],
                'properties' => [
                    'parameter' => ['type' => 'string', 'maxLength' => 60, 'example' => 'Illuminance'],
                    'en_12464' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'iso_8995' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'ansi_ies' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'well_v2' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'is_published' => oasFlag('Whether the row is public.'),
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'Term' => [
                'allOf' => [
                    ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]],
                    oasRef('TermWrite'),
                ],
            ],
            'ToolWrite' => [
                'type' => 'object',
                'required' => ['name', 'description'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 120, 'example' => 'DIALux'],
                    'description' => ['type' => 'string', 'maxLength' => 500],
                    'url' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
                    'icon' => ['type' => 'string', 'maxLength' => 40],
                    'image_url' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                    'is_external' => oasFlag('Open in a new tab.'),
                    'is_published' => oasFlag('Whether the tool is public.'),
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'Tool' => [
                'allOf' => [
                    ['type' => 'object', 'properties' => ['id' => ['type' => 'integer']]],
                    oasRef('ToolWrite'),
                ],
            ],
            'EventWrite' => [
                'type' => 'object',
                'required' => ['name'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 160, 'example' => 'LedEXPO 3'],
                    'year' => ['type' => 'integer', 'nullable' => true, 'example' => 2025],
                    'description' => ['type' => 'string', 'maxLength' => 500, 'nullable' => true],
                    'is_published' => oasFlag('Whether the event is public.'),
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'Event' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'created_at' => $dt,
                            'images' => ['type' => 'array', 'items' => oasRef('EventImage')],
                        ],
                    ],
                    oasRef('EventWrite'),
                ],
            ],
            'EventImageWrite' => [
                'type' => 'object',
                'required' => ['image_path'],
                'properties' => [
                    'image_path' => ['type' => 'string', 'maxLength' => 255],
                    'image_url' => ['type' => 'string', 'description' => 'Alias of image_path if image_path is empty'],
                    'caption' => ['type' => 'string', 'maxLength' => 200, 'nullable' => true],
                    'sort_order' => ['type' => 'integer'],
                ],
            ],
            'EventImage' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'event_id' => ['type' => 'integer'],
                        ],
                    ],
                    oasRef('EventImageWrite'),
                ],
            ],
            'TopicWrite' => [
                'type' => 'object',
                'required' => ['title', 'body', 'user_id'],
                'properties' => [
                    'user_id' => ['type' => 'integer', 'description' => 'Public users.id'],
                    'title' => ['type' => 'string', 'maxLength' => 160],
                    'slug' => ['type' => 'string', 'maxLength' => 190],
                    'category' => ['type' => 'string', 'maxLength' => 60, 'example' => 'General'],
                    'body' => ['type' => 'string'],
                    'status' => ['type' => 'string', 'enum' => ['pending', 'approved', 'rejected']],
                    'reject_reason' => ['type' => 'string', 'maxLength' => 300, 'nullable' => true],
                ],
            ],
            'Topic' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'decided_by' => ['type' => 'string', 'nullable' => true],
                            'decided_at' => $dt,
                            'created_at' => $dt,
                            'updated_at' => $dt,
                        ],
                    ],
                    oasRef('TopicWrite'),
                ],
            ],
            'UserWrite' => [
                'type' => 'object',
                'required' => ['name', 'email'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 120],
                    'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 190],
                    'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8, 'description' => 'Write-only. Never returned. Min 8 characters on update.'],
                    'role' => ['type' => 'string', 'enum' => ['client', 'employee', 'leader', 'admin'], 'default' => 'client'],
                    'is_preapproved' => oasFlag('Skip topic moderation when posting.'),
                    'is_active' => oasFlag('Account can sign in.'),
                    'phone' => ['type' => 'string', 'maxLength' => 40, 'nullable' => true],
                    'profession' => ['type' => 'string', 'maxLength' => 40, 'nullable' => true],
                    'profession_other' => ['type' => 'string', 'maxLength' => 120, 'nullable' => true],
                    'company' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'is_subscribed' => oasFlag('Article-published emails.'),
                ],
            ],
            'User' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'role' => ['type' => 'string', 'enum' => ['client', 'employee', 'leader', 'admin']],
                    'is_preapproved' => ['type' => 'integer', 'enum' => [0, 1]],
                    'is_active' => ['type' => 'integer', 'enum' => [0, 1]],
                    'phone' => ['type' => 'string', 'nullable' => true],
                    'profession' => ['type' => 'string', 'nullable' => true],
                    'profession_other' => ['type' => 'string', 'nullable' => true],
                    'company' => ['type' => 'string', 'nullable' => true],
                    'is_subscribed' => ['type' => 'integer', 'enum' => [0, 1]],
                    'created_at' => $dt,
                ],
            ],
            'ContactWrite' => [
                'type' => 'object',
                'required' => ['name', 'email', 'message'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 120],
                    'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 190],
                    'company' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'message' => ['type' => 'string'],
                    'is_read' => oasFlag('Marked read in the CMS.'),
                ],
            ],
            'Contact' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'ip' => ['type' => 'string', 'nullable' => true],
                            'user_agent' => ['type' => 'string', 'nullable' => true],
                            'emailed_at' => $dt,
                            'created_at' => $dt,
                        ],
                    ],
                    oasRef('ContactWrite'),
                ],
            ],
            'AdminWrite' => [
                'type' => 'object',
                'required' => ['username', 'name', 'email'],
                'properties' => [
                    'username' => ['type' => 'string', 'maxLength' => 190],
                    'name' => ['type' => 'string', 'maxLength' => 120],
                    'email' => ['type' => 'string', 'format' => 'email', 'maxLength' => 190],
                    'phone' => ['type' => 'string', 'maxLength' => 40, 'nullable' => true],
                    'company' => ['type' => 'string', 'maxLength' => 160, 'nullable' => true],
                    'title' => ['type' => 'string', 'maxLength' => 120, 'nullable' => true],
                    'is_active' => oasFlag('Can sign in after email confirmation.'),
                ],
            ],
            'Admin' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'username' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'nullable' => true],
                    'phone' => ['type' => 'string', 'nullable' => true],
                    'company' => ['type' => 'string', 'nullable' => true],
                    'title' => ['type' => 'string', 'nullable' => true],
                    'is_active' => ['type' => 'integer', 'enum' => [0, 1]],
                    'email_verified_at' => $dt,
                    'invite_expires_at' => $dt,
                    'created_at' => $dt,
                    'updated_at' => $dt,
                ],
            ],
            'RoleWrite' => [
                'type' => 'object',
                'properties' => [
                    'can_post_topics' => oasFlag('May submit community topics.'),
                    'can_moderate_topics' => oasFlag('May approve or reject topics.'),
                    'auto_publish_topics' => oasFlag('Topics go live without moderation.'),
                ],
            ],
            'Role' => [
                'allOf' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'role' => ['type' => 'string', 'enum' => ['client', 'employee', 'leader', 'admin']],
                            'updated_at' => $dt,
                        ],
                    ],
                    oasRef('RoleWrite'),
                ],
            ],
        ],
    ];
}
