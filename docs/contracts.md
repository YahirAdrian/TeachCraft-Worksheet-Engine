# Data Contracts Between Layers

This document states how layers should communicate and support to work together.

The layers should communicate using explicit, versionable data structures rather than depending on each other's internal implementation.

### Template Inspector → AI Generator

Produces a **template definition** describing the content expected by the worksheet.

Conceptually:

```json
{

  "template": {
    "title": "Guess Who",
    "description": "A worksheet to practice yes/no questions in different verb tenses using the 'Guess Who' game format. This worksheet includes a set of characters and a series of questions that students can ask to guess the character based on the answers provided.",
    "instructions": "Students ask questions to guess the character based on the clues and answers provided.",
    "prompt_instructions": "Generate a set of four clues for the game.  Some clues can be repeated across different characters, but each character must have at least one unique clue. The clues should be designed to help students practice forming questions. Clues should be short and simple. Example: [Clue] - Will travel to...,[Answer] - France. Clues and answers should match gramatically. The example question must be taken from a real example of a character."
  },

  "requirements": {
        "characters": 9,
        "answers_per_character": 4
  },

  "output_schema": {
    "title": "string",
    "characters": [
        {
            "name": "string",
            "answers": ["answer1", "answer2"]
        }
    ]
  }
}

```

### AI Generator → Template Engine

Produces the actual educational content.

Conceptually:

```json
{
  "title": "Guess Who",
  "characters": [
    {
      "name": "Alex",
      "answers": ["Germany", "English", "pencil", "friend"]
    }
    ... (9 total characters)
  ]
}
```

The AI output contains content only. It should not contain PowerPoint coordinates, slide indexes, formatting instructions, or presentation-specific implementation details.

---

## Core Design Principles

### Separation of concerns

Each layer should have one clear responsibility:

- **Template Inspector:** template understanding and validation.
- **AI Generator:** educational content generation.
- **Template Engine:** content-to-PowerPoint rendering.

### Content and presentation remain independent

AI-generated data should not depend on the visual layout of a specific PowerPoint implementation.

### Provider independence

The AI Generator should expose a common internal contract so that OpenAI or other providers can be used without changing the Template Engine.

### Reusable templates

A single PowerPoint template should support many topics, lesson requirements, and generated worksheet versions.

### Declarative templates

PowerPoint templates describe their behavior through the TeachCraft DSL instead of requiring contributors to write code.

### Testable layers

Each layer should be executable and testable independently before being integrated into the complete TeachCraft application.

---
