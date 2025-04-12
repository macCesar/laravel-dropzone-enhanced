# Contribution Guidelines

## Language

- This is an open source project, and all documentation, comments, variables, functions, methods, controllers, views, models, etc. must be written in English to make it accessible to a wider audience.

## Code Style

- Follow PSR-12 coding standards for PHP
- Use camelCase for method and variable names
- Use PascalCase for class names
- Use meaningful, descriptive names for all code elements

## Development Process

- Create feature branches from the `develop` branch
- Use pull requests for all changes
- Include comprehensive tests for all new features
- Ensure all tests pass before submitting a pull request
- Document all public methods and functions
- Use 2 spaces for indentation in all code files

## Documentation

- Keep documentation up-to-date with code changes
- Use clear, concise language
- Include examples where appropriate
- Document any breaking changes thoroughly

## Git Workflow

- Commit messages should be clear and descriptive
- Use imperative mood in commit message subject (e.g., "Add feature" not "Added feature")
- Reference issue numbers in commit messages when applicable
- Keep commits focused on a single logical change

## Testing

- Write unit tests for all new features
- Maintain or improve test coverage with each change
- Include both positive and negative test cases

## Versioning

- Follow semantic versioning (SEMVER) guidelines
- Document breaking changes in CHANGELOG.md

## Pull Requests

- Provide a clear description of the changes
- Include screenshots for UI changes
- Link to related issues
- Ensure CI passes before requesting review

## Localization

- Design with internationalization in mind
- Extract all user-facing strings into language files

## Performance

- Consider the performance impact of new features
- Optimize database queries
- Minimize JavaScript overhead
- Use caching where appropriate
