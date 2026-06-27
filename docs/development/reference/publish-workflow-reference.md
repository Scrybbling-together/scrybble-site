# `publish.yml` — Reference

Retags a pre-built Docker image from GHCR onto Docker Hub with version and `:latest` tags.

## Triggers

| Trigger | Source of version |
|---|---|
| `workflow_dispatch` (manual) | `version` input (required) |
| `release.published` | `github.event.release.tag_name` |

## Inputs

| Parameter | Type | Required | Description |
|---|---|---|---|
| `version` | string | yes (manual trigger only) | Version tag, e.g. `v1.2.3` |

When triggered by a published release, the version is resolved from the release tag name. The `workflow_dispatch` input and the release tag name are interchangeable — the workflow uses `inputs.version || github.event.release.tag_name`.

## Prerequisites

The workflow requires a source image to exist in GHCR **before** it runs.

- **Source image reference:** `ghcr.io/{owner}/scrybble-server:production-{GITHUB_SHA}`
- The image is produced by `build.yml` (target `production`).
- If the source image does not exist, the `imagetools create` step fails.

### Environment

- Runs against the `production` GitHub environment.
- Requires environment deployment approval if protection rules are configured.

### Secrets

| Secret | Registry |
|---|---|
| `GITHUB_TOKEN` | ghcr.io (auto-provided) |
| `DOCKERHUB_USERNAME` | Docker Hub |
| `DOCKERHUB_TOKEN` | Docker Hub |

Missing Docker Hub secrets cause authentication failure at the login step.

## Execution

1. Resolves the source image ref from GHCR using the current commit SHA.
2. Authenticates to GHCR and Docker Hub.
3. Uses `docker buildx imagetools create` to copy-manifest the source image with new tags.

No build occurs. No tests run. The workflow does not validate the image — it only re-tags.

## Outputs

| Tag | Description |
|---|---|
| `docker.io/laauurraaa/scrybble-server:<version>` | Versioned release tag |
| `docker.io/laauurraaa/scrybble-server:latest` | Always updated to the latest publish |

The source image in GHCR is not modified or removed.

## Constraints

- The version string is used as-is — no prefix stripping, no validation. A value of `v1.2.3` produces tag `v1.2.3`; a value of `1.2.3` produces tag `1.2.3`.
- Only one Docker Hub repository is targeted: `docker.io/laauurraaa/scrybble-server`.
- The `production` build target in `deployment/App.Dockerfile` is the only image stage this workflow operates on.
- Publishing the same version tag twice overwrites the previous tag on Docker Hub.
