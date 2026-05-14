# Metafors

Metafors is a small Laravel and Livewire application for collecting software metaphors that make technical ideas easier to explain.

The app is intentionally simple: visitors can submit metaphors, browse what others have written, search and filter by topic, and rate the entries they find most useful. Moderation is protected by a server-side key so approved editors can update or remove entries without adding a full user account system.

## What the app does

- Collects metaphors for software concepts like caching, queues, observability, and identity providers.
- Lets anyone submit a new metaphor from the homepage.
- Supports multiline metaphor text so longer explanations stay readable.
- Groups entries by the concept they explain.
- Provides live search and filtering.
- Allows 1 to 5 rating per visitor session.
- Includes moderation unlock for editing and deleting entries.
- Seeds the database with starter examples.

## Stack

- PHP 8.3
- Laravel 13
- Livewire 4
- Blade views with a static CSS frontend
- PHPUnit feature tests

## Local setup

1. Install dependencies:

```bash
composer install
```

2. Create the environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure your database in `.env`.

4. Run migrations and seed starter metaphors:

```bash
php artisan migrate --seed
```

5. Start the local server:

```bash
php artisan serve
```

You can also use the included Composer shortcut:

```bash
composer run setup
```

## Moderation

Set the moderation key in your environment to enable edit and delete actions from the UI:

```bash
METAFORS_ADMIN_KEY=your-secret-key
```

Once that value is present, enter the same key in the moderation section of the app to unlock moderation for the current session.

## Testing

Run the full test suite with:

```bash
php artisan test
```

## Notes for GitHub visitors

This repository is the application itself, not a package. The main experience lives on the homepage route and is powered by the `MetaforBoard` Livewire component, which handles submission, filtering, pagination, moderation, and rating.

## License

This project is open-sourced under the MIT license.
