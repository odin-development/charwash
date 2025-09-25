# Contributing to CharWash

Thanks for considering contributing! We welcome pull requests, bug reports, and feature suggestions.

## Getting Started

1. Fork the repository and clone your fork locally.
2. Install dependencies with Composer:
   ```bash
   composer install
   ```
3. Run the test suite to ensure everything works:
   ```bash
   vendor/bin/phpunit
   ```

## Coding Standards

- Follow **PSR-12** coding style.  
- Run code sniff before committing:
  ```bash
  vendor/bin/phpcs --standard=PSR12 src
  ```

If you have `phpcbf`, you can auto-fix many issues:
```bash
vendor/bin/phpcbf --standard=PSR12 src
```

## Static Analysis

If `phpstan` is configured, run:
```bash
vendor/bin/phpstan analyse
```

## Submitting Changes

1. Create a new branch for your feature or bugfix:
   ```bash
   git checkout -b feature/my-feature
   ```
2. Commit your changes with clear messages.
3. Push your branch to your fork.
4. Open a Pull Request (PR) against the `main` branch.

## Pull Request Guidelines

- Include tests for new features or fixes when possible.
- Update documentation (README, CHANGELOG) if your changes affect usage.
- Keep PRs focused — one feature or fix per PR.

## Reporting Issues

If you find a bug, please open an issue and include:
- A clear description of the problem.
- Steps to reproduce it.
- Expected vs. actual behavior.
- Your environment (PHP version, framework, etc.).

---

By contributing, you agree to license your work under the MIT License that covers this project.
