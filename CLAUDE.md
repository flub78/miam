# Conventions du projet miam

Ce fichier porte les règles **propres au projet**, à respecter par tous les assistants IA
(Claude Code, GitHub Copilot, Gemini CLI, etc.).

Les directives génériques de l'écosystème Laravel sont générées par Laravel Boost dans le bloc
délimité en fin de fichier (`php artisan boost:update`). **Ne pas éditer ce bloc à la main** et
**ne jamais écrire son nom de balise dans cette section** : la régénération remplace tout depuis
la première occurrence de la balise. Toute règle maison doit rester **au-dessus** du bloc.

## Aperçu du projet

miam est une application de suivi de poids et de calories, utilisable sur PC et smartphone.
C'est le **premier projet bâti sur Laraskel** (`../laraskel`), le socle d'application Laravel.

### Relation avec Laraskel

- Laraskel fournit ses briques sous forme de **packages Composer** ; miam en `require` un
  sous-ensemble. En phase de développement, ces packages sont consommés en *path repository*
  Composer depuis le monorepo local.
- On **étend le cœur par composition** (`class UserResource extends BaseUserResource`), jamais
  en modifiant le code d'un package Laraskel. Un besoin d'évolution du cœur se traite dans
  `laraskel`, pas par contournement dans `miam`.
- Les conventions de `laraskel` s'appliquent à `miam` ; ce fichier ne porte que les écarts et
  les spécificités de `miam`.

## Politique de validation des changements

- When the user asks to "Analyze", "Suggest a fix", "Investigate", "Propose", or "Review", provide findings or recommendations only — do not change code or files.
- Only implement changes when explicitly instructed: "Apply the fix", "Implement this", "Fix this issue", "Update the code", etc. If it is unclear whether you should implement, ask first.
- Do only what is instructed: do not add features, refactor, change design, or create extra documents beyond what was requested. If you notice unrelated improvements, refactors, or missing pieces, ask for permission before doing them.

## Contrôle de version (git)

- AI agents may create commits, but must **never** run `git push` except if explicitly instructed. Pushing to the remote is the user's decision, taken explicitly for a given batch of work — a request to "implement", "do lot X" or "run the tests" is not a request to push.
- Never commit directly on the default branch (`main`) except if explicitly instructed: create a topic branch first.
- If a change genuinely cannot be validated without reaching the remote (e.g. exercising a CI workflow), stop and ask the user to push, rather than doing it.

## Conventions de base de données

- Every new database table must include audit columns: `created_at` / `updated_at` (timestamps) and `created_by` / `updated_by` (references to the user who created/last updated the row). The `auditColumns()` / `dropAuditColumns()` Blueprint macros come from the Laraskel core package — use them rather than hand-writing the columns.

## Expérience utilisateur

- Never reject a user action silently. The result of every action (success or failure) must be obvious to the user.
- All error messages must be descriptive and contain enough information, so the user can fix the error.

## Fichiers de documentation

- You must only create documentation files if explicitly requested by the user.
- documentation is maintained in Markdown format. Use PlantUML for diagrams. Keep each `.puml` source and its generated image versioned together; regenerate images locally with `bin/puml.sh`. CI checks that a modified `.puml` is committed with its refreshed image (it does not render).
- the document must be managed in the doc directory, under design for design notes, prds for product requirements, plans for development plans and users for user documentation.
- PRDs must only contains functional requirements and describe the feature use cases. Do not include design elements in PRDs.
- Plans must describes the implementations steps and have checkboxes associated with every steps to monitor the progresses. While working through a plan, do not create extra summary or to-do documents — update the plan itself. When adding new work to a plan, restructure it if needed so it doesn't read as patched-on.
- Design notes contain design information. Stay minimalist and focused on architecture, not implementation details; include code only when necessary to understand the design. Use PlantUML diagrams for database schemas and class relationships when useful, and enforce clear separation of concerns between components.
- When there are several design possibilities, design notes must contain a short description of the alternatives and indicate why they have not been chosen.
- When a PRD, design note, or plan is modified, check the linked documents (PRD/design/plan) for the same feature and update them too if they are impacted.
- Keep the documentation concise and documents only the important points. Avoid redundancy and use links to others documents instead of repeating the information.
- User documentation should be oriented with How-to sections rather than describing in detail all options and parameters. When required include reference documentation.
- PlantUML diagrams live in `.puml` files under a `diagrams` subdirectory next to the document that uses them; generate the images and embed/link them in the document so they render on GitHub.
- Mockups: sketch UI mockups in ASCII art; build interactive prototypes as self-contained HTML files.
- Plans d'implémentation, les plans doivent comprendre la description détaillée des étapes d'implémentation, pas à pas. Ils doivent être organisés en lots de livraison. Chaque lot doit comprendre ses tests et la documentation utilisateur.
- Lorsque plusieurs options seront possibles pour un paramètre de configuration, une option dans le logiciel, etc. toutes les options devront être listées.
- Pour l'instant la documentation de développement et utilisateur doit être rédigée en français.

## Tests

- Delete temporary tests created only to investigate or debug an issue once you're done with them, unless you and the user agree to keep them as regression tests.
- Tests must be able to run on a copy of the production database, they should insure that they let the database in the state in which they found it. They should not delete tables or data without restoring them and should delete the data that they have created after execution. Tests must not depend on the database's initial state (existing rows, row counts, specific IDs); they must set up any data they need themselves (e.g. via factories) and assert only on the data they created. Of course during the test development, it is possible that buggy tests corrupt the test database that will have to be restored but it is an accepted risk.
- all feature, bug fix and development steps must be reasonably tested. It means that all the nominal use cases must be covered by at least one test and the behavior of each module or feature must have been tested against incorrect usage.
- When fixing bugs, start by writing a test or modifying an existing one to show the problem, then fix the code and check that test passes. Keep these tests for non regression checks. Use TDD for bug fixes.
- TDD is a good practice that must be used for bug fixing and is also recommended for new developments.
- The goal of the test suite is to be relatively confident that a version can be deployed without breaking the production.
- If possible have an option to measure the line coverage on unit tests, and maintain a document on the features test coverage for end to end tests.
- Before claiming a feature is complete, run the feature/unit tests (and a manual browser check for UI changes) that demonstrate it actually works. If something fails, reproduce and fix it before reporting completion. Keep these tests in the suite for future regression testing.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
