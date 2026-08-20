# PHP Code Conventions

This document defines the PHP coding standards for the TeachCraft Worksheets project.

---

## 1. File Organization

- Use **PSR-4 autoloading** with a vendor namespace root (e.g., `TemplateEngine\`).
- Place source classes in `src/`.
- Entry point scripts go at the project root (e.g., `index.php`).
- Tests go in `tests/`.
- One class, interface, trait, or enum per file.
- Filename must match the class name exactly using **PascalCase** (e.g., `TemplateProcessor.php`).
- Omit the closing `?>` tag.

---

## 2. Namespace and Imports

- Declare namespace immediately after the opening `<?php` tag.
- Place `use` statements after the namespace declaration, one per line.
- Import specific classes; avoid wildcards.
- Reference global classes with a leading backslash:
  ```php
  throw new \RuntimeException('Error message');
  ```

---

## 3. Naming Conventions

| Element            | Convention    | Example                        |
|--------------------|---------------|--------------------------------|
| Classes            | PascalCase    | `TemplateProcessor`            |
| Interfaces         | PascalCase + `Interface` suffix | `ClientInterface` |
| Traits             | PascalCase + `Trait` suffix | `LoggerTrait` |
| Abstract classes   | `Abstract` prefix | `AbstractLogger`        |
| Enums              | PascalCase    | `OverrideStrategy`             |
| Methods            | camelCase     | `processShape()`               |
| Variables          | camelCase     | `$repeatState`                 |
| Properties         | camelCase     | `$nextShapeId`                 |
| Class constants    | UPPER_CASE    | `READ_WRITE_HASH`              |
| Enum cases         | PascalCase    | `case Merge`                   |
| Boolean methods    | `is`/`has` prefix | `isGroup()`, `hasSlide()`  |
| Getters            | `get` prefix  | `getSlidePath()`               |
| Setters            | `set` prefix  | `setBoundary()`                |

---

## 4. Class and Method Structure

### Class Declarations

- Use K&R brace style (opening brace on the same line):
  ```php
  class TemplateProcessor {
  ```
- Use `final` on concrete implementations that should not be extended.
- Use `abstract` on base classes and method declarations.

### Methods

- All methods must have an **explicit visibility** modifier (`public`, `protected`, `private`).
- All methods must have an **explicit return type** declaration.
- All parameters must have **explicit type declarations**.
- Use PHP 8.0 **constructor property promotion**:
  ```php
  public function __construct(private DOMElement $node, private DOMXPath $xpath) {
  ```
- Use nullable types with `?` prefix for optional parameters:
  ```php
  private function processShape(Shape $shape, ?array $repeatState = null): void {
  ```
- Separate methods with a single blank line.

### Properties

- Declare properties with **explicit visibility** and **type hints**:
  ```php
  private array $repeatPositions = [];
  private int $nextShapeId;
  ```
- Provide default values at declaration when applicable.

---

## 5. Code Style

- Use **4 spaces** for indentation (no tabs).
- Opening brace on the **same line** as the declaration (K&R / 1TBS):
  ```php
  if ($condition) {
      // ...
  }
  ```
- Place return type colon **directly after** the closing parenthesis (no space):
  ```php
  public function getNode(): DOMElement {
  public function getText(): string {
  ```
- Use PHP 8.0 **named arguments** in function calls for clarity:
  ```php
  json_encode($data, flags: JSON_PRETTY_PRINT);
  ```
- Use **arrow functions** for short closures:
  ```php
  fn($shape) => $shape->getText()
  ```
- Use **null coalescing** and **null-safe** operators:
  ```php
  $apiKey = $_ENV['API_KEY'] ?? null;
  $result?->getValue();
  ```

---

## 6. Comments and Documentation

- Use `//` for single-line comments.
- Use `/* */` for multi-line block comments.
- Place comments **above** the relevant code line.
- Use `//TODO:` for task markers.
- Write **PHPDoc blocks** for all public and protected methods:
  ```php
  /**
   * Process a shape with the given context.
   *
   * @param Shape $shape The shape to process
   * @param JSONContext $context The data context
   * @return void
   * @throws RuntimeException If the directive is invalid
   */
  ```
- Use `@param`, `@return`, `@throws`, `@see`, `@deprecated` tags as needed.
- Use `{@inheritdoc}` to inherit parent documentation.
- Include license/copyright blocks at the top of files using `/* */` format.

---

## 7. Error Handling

- Use **exceptions** for error conditions (not error codes or return flags).
- Prefer `RuntimeException` for runtime failures.
- Use `\InvalidArgumentException` for invalid method arguments.
- Use `die()` only in procedural entry points for fatal termination.
- For non-fatal warnings, use `echo` with `PHP_EOL`.

---

## 8. PHP 8.x Features (Required)

The project requires **PHP 8.0+**. Use the following features:

| Feature                      | Usage                                        |
|------------------------------|----------------------------------------------|
| Constructor property promotion | Inject and declare properties in constructor |
| Named arguments              | Use in function calls for clarity             |
| `mixed` type                 | For dynamic return/parameter types            |
| `str_starts_with()` / `str_ends_with()` | String operations                   |
| Backed enums                 | `enum OverrideStrategy: string`               |
| Union types                  | `callable|int|null`                           |
| `readonly` properties        | For immutable state (when applicable)         |
| Match expressions            | As alternative to switch when appropriate     |
| Null safe operator           | `$object?->method()`                          |

---

## 9. Dependency Management

- Use **Composer** for dependency management.
- Define PSR-4 autoloading in `composer.json`.
- Load the autoloader at the top of entry points:
  ```php
  require 'vendor/autoload.php';
  ```
- Use caret (`^`) version constraints for minor/patch flexibility.

---

## 10. Testing

- Place tests in `tests/`.
- Use descriptive file names with `Test` suffix (e.g., `TemplateProcessorRBindTest.php`).
- Load the autoloader with a relative path:
  ```php
  require __DIR__ . '/../vendor/autoload.php';
  ```
- Use `fwrite(STDERR, ...)` and `fwrite(STDOUT, ...)` for test output.
- Use `exit(1)` for test failure.
- Use heredoc syntax for multi-line test data (e.g., XML strings).

---

## 11. Design Patterns

- Use **static factory methods** over public constructors when appropriate:
  ```php
  public static function create($value): self { ... }
  ```
- Use **fluent interfaces** (return `$this`) for method chaining.
- Use **clone-and-modify** for immutable object updates:
  ```php
  $new = clone $this;
  $new->property = $value;
  return $new;
  ```
- Use **singleton pattern** sparingly (only when a single instance is required).

---

## 12. String Handling

- Use **double-quoted strings** for interpolation:
  ```php
  "Processing {$shape->getName()}"
  ```
- Use curly braces for complex expressions in interpolation.
- Use string concatenation with `.` operator.
- Use `sprintf()` for complex formatting.
- Use **heredoc syntax** for multi-line strings:
  ```php
  $xml = <<<'XML'
  <root><child/></root>
  XML;
  ```

---

## 13. Array Handling

- Use short array syntax: `[]`.
- Use double-quoted string keys for associative arrays.
- Use `array_key_exists()` or `??` for key checks.
- Use `array_map()`, `array_filter()`, `array_reduce()` for functional operations.
