# Contributing to Transformer

Thank you for considering contributing to the Transformer package! This document outlines the guidelines and process for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Pull Request Process](#pull-request-process)
- [Reporting Issues](#reporting-issues)

## Code of Conduct

Please be respectful and constructive in all interactions. We aim to maintain a welcoming and inclusive environment for all contributors.

## Getting Started

Before you begin:

1. Check the [issue tracker](https://github.com/surgiie/transformer/issues) to see if your bug or feature has already been reported
2. For major changes, please open an issue first to discuss what you would like to change
3. Make sure you have the required PHP version (8.2 or higher) installed

## Development Setup

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR-USERNAME/transformer.git
   cd transformer
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Create a new branch for your feature or bugfix:
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b fix/your-bugfix-name
   ```

## Development Workflow

### Running Tests

Run the test suite using Pest:
```bash
composer test
```

Run tests with coverage:
```bash
composer test:coverage
```

### Code Formatting

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting:

```bash
# Format all files
composer format

# Check formatting without making changes
composer format:test
```

### Static Analysis

Run PHPStan for static analysis:
```bash
composer phpstan
```

### Quality Check

Run all quality checks at once (formatting, static analysis, and tests):
```bash
composer quality
```

Make sure all quality checks pass before submitting a pull request.

## Coding Standards

### General Guidelines

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards
- Use type hints for all method parameters and return types
- Write clear, descriptive variable and method names
- Add PHPDoc blocks for all public methods
- Keep methods focused and single-purpose

### Code Style

- Use 4 spaces for indentation (no tabs)
- Use single quotes for strings unless interpolation is needed
- Place opening braces on the same line for classes and methods
- Use trailing commas in multiline arrays
- Keep lines under 120 characters when possible

### Example

```php
<?php

declare(strict_types=1);

namespace Surgiie\Transformer;

class Example
{
    /**
     * Transform the given value.
     */
    public function transform(string $value, array $rules): string
    {
        // Implementation
        return $transformedValue;
    }
}
```

## Testing

### Test Requirements

- All new features must include tests
- Bug fixes should include regression tests
- Aim for high code coverage (minimum 80%)
- Use descriptive test names that explain what is being tested

### Test Structure

This project uses Pest for testing. Tests are organized in the `tests/` directory:

- `tests/Unit/` - Unit tests for individual classes and methods
- `tests/Feature/` - Integration tests for features

### Writing Tests

```php
<?php

use Surgiie\Transformer\Transformer;

it('transforms a value using multiple functions', function () {
    $transformer = new Transformer('  hello  ', ['trim', 'ucfirst']);

    expect($transformer->transform())->toBe('Hello');
});

it('handles null values correctly', function () {
    $transformer = new Transformer(null, ['?', 'trim']);

    expect($transformer->transform())->toBeNull();
});
```

## Pull Request Process

### Before Submitting

1. Ensure all tests pass: `composer test`
2. Run code formatting: `composer format`
3. Run static analysis: `composer phpstan`
4. Update documentation if needed
5. Add or update tests for your changes
6. Commit your changes with clear, descriptive commit messages

### Commit Message Guidelines

Use clear and descriptive commit messages:

```
Add support for custom transformation guards

- Implement guard callback functionality
- Add tests for guard behavior
- Update documentation with guard examples
```

### Submitting a Pull Request

1. Push your changes to your fork
2. Create a pull request against the `master` branch
3. Fill out the pull request template with:
   - A clear description of the changes
   - The motivation for the changes
   - Any breaking changes
   - Related issues (if applicable)

4. Wait for review and address any feedback

### Pull Request Checklist

- [ ] Tests pass locally (`composer test`)
- [ ] Code is formatted (`composer format:test`)
- [ ] Static analysis passes (`composer phpstan`)
- [ ] Documentation is updated (if needed)
- [ ] Tests are added/updated
- [ ] No breaking changes (or clearly documented if unavoidable)
- [ ] Branch is up to date with master

## Reporting Issues

### Bug Reports

When reporting a bug, please include:

- PHP version
- Laravel version (if applicable)
- Package version
- Steps to reproduce the issue
- Expected behavior
- Actual behavior
- Code samples or test cases

### Feature Requests

When requesting a feature, please include:

- Clear description of the feature
- Use cases and motivation
- Possible implementation approach (if you have ideas)
- Examples of how the feature would be used

## Questions and Discussions

- For general questions, use [GitHub Discussions](https://github.com/surgiie/transformer/discussions)
- For bugs and feature requests, use the [issue tracker](https://github.com/surgiie/transformer/issues)

## License

By contributing to this project, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Transformer!
