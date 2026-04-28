<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

# Polls API v1.0 (final)

Optional additions to the endpoints or its payload may occur.

The Polls API is documented via the [OpenAPI](https://www.openapis.org/) specification.
The generated spec files are located in the root of this repository:

| File                          | Scope                                      |
| ----------------------------- | ------------------------------------------ |
| `openapi.json`                | All endpoints available to regular users   |
| `openapi-administration.json` | Admin-only endpoints                       |
| `openapi-full.json`           | All endpoints combined                     |

## Browsing the spec

Use any OpenAPI-compatible viewer, e.g. [Swagger UI](https://swagger.io/tools/swagger-ui/)
or the [Redoc CLI](https://github.com/Redocly/redoc):

```bash
npx @redocly/cli preview-docs openapi.json
```

[Bruno](https://www.usebruno.com/) can import the spec directly:
_File → Import Collection → OpenAPI V3 → select `openapi.json`_

## Base URL

All endpoints are served under:

```
/index.php/apps/polls
```

## Authentication

Standard Nextcloud authentication applies (Basic Auth, App Passwords, session cookies).

```bash
curl -u username:password https://nextcloud.example.com/index.php/apps/polls/api/v1.0/polls
```

## Available enum values

Valid values for poll type, access mode, showResults, and votingVariant are exposed
through the [Nextcloud Capabilities API](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-api-overview.html#capabilities):

```
GET /ocs/v2.php/cloud/capabilities
```

Look for the `polls` key in the response.

## Keeping the spec up to date

The spec is generated from source annotations and must be regenerated after any
endpoint change:

```bash
composer run openapi
```

A CI check enforces that the committed spec matches the current source.
