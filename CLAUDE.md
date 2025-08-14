<project_context>
<introduction>
Scrybble is an open-source, GPLv4 licensed project that is meant to facilitate the connection between various services, tools and devices for knowledge workers.
</introduction>

<project_integrations>
Currently, the most important integration is between reMarkable and Obsidian.
There's an Obsidian plugin that interacts with the api (see `routes/api.php`) to list and sync files from the reMarkable.
This plugin does not exist within this repository.
</project_integrations>

<project_structure>
This project is the Scrybble website, whose live version lives at https://scrybble.ink. It's written in Laravel v12 with PHP 8.3.
The frontend is made using Laravel blade in its entirety, there still exists some legacy React/ts code from a previous version.
This needs to be cleaned up at some point.

The project is deployed and developed using Docker and docker-compose, and offers encryption using `gocryptfs`.

We use Bootstrap v5.3 for styling, and have a custom colorscheme, the details of the scheme can be found in `resources/sass/_variables.scss`

- The development docker configuration lives in `/development`
- The deployment docker configuration lives in `/deployment`
- Tests live under the `tests` folder. phpunit is used.
</project_structure>
</project_context>


<coding_instructions>
<development_tools>
- You can run PHPUnit tests using `docker compose exec laravel.test ...`
</development_tools>
<purpose>
These rules ensure maintainability, safety, and developer velocity.
**MUST** rules are enforced; **SHOULD** rules are strongly recommended. **SHOULD-NOT** rules strongly recommend _against_ certain behavior.
</purpose>

<before_coding>
- **BP-1 (MUST)** Ask the user clarifying questions.
- **BP-2 (SHOULD)** Draft and confirm an approach for complex work.
- **BP-3 (SHOULD)** If ≥ 2 approaches exist, list clear pros and cons.
</before_coding>

<while_coding>
- **C-1 (MUST)** Follow TDD: scaffold stub -> write failing test -> implement.
- **C-2 (MUST)** Name functions with existing domain vocabulary for consistency.
- **C-3 (SHOULD NOT)** Introduce classes when small testable functions suffice. 
- **C-4 (SHOULD NOT)** Add comments except for critical caveats; rely on self‑explanatory code. 
- **C-5 (SHOULD NOT)** Extract a new function unless it will be reused elsewhere, is the only way to unit-test otherwise untestable logic, or drastically improves readability of an opaque block.
</while_coding>

<testing>
- **T-1 (MUST)** write integration tests: Use a real database connection, use real filesystems.
- **T-2 (MUST)** Use factories to generate test data.
- **T-3 (SHOULD NOT)** Do not mock functionality.
- **T-3a (SHOULD)** In the case of having to mock a class, service or function, write a dummy class or function that models the external service.
</testing>
</coding_instructions>




