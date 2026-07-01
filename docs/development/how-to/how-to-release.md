# How to release scrybble-server

This guide shows how to publish a versioned image to Docker Hub.

## Tag convention

Tags use semantic versioning with a `v` prefix:

```
v<major>.<minor>.<patch>
```

**Examples:** `v1.0.0`, `v1.2.3`, `v2.0.0`

## Before you start

The production image must already be built and pushed to GHCR. This happens automatically when the target branch passes CI.

If you're unsure whether the image exists, check that the latest commit has a build record for the `production` target in the GitHub Actions run history.

## Publish a release

1. Create and push the tag on the commit you want to release:

   ```bash
   git tag v1.2.3
   git push origin v1.2.3
   ```

   Choose the version number that reflects the changes in this release.

2. Trigger `publish.yml` via **Actions → Publish to Docker Hub → Run workflow**, entering the version tag (e.g. `v1.2.3`).

   Refer to the [`publish.yml` reference](../reference/publish-workflow-reference.md) for triggers, inputs, and outputs.

3. Wait for the workflow to complete. It authenticates to both registries and re-tags the image.

4. Verify the image appears on Docker Hub:

   - `docker.io/laauurraaa/scrybble-server:v1.2.3` — versioned tag
   - `docker.io/laauurraaa/scrybble-server:latest` — updated to this release

   Each publish overwrites `:latest`.
