# TeachCraft Template Engine Specification

## Overview

The TeachCraft Template Engine is responsible for transforming **structured AI-generated content** into a completed PowerPoint worksheet (`.pptx`).

The engine separates the system into three independent layers:

1. **Template Inspection** – Describes what content should be generated from a `.pptx`template using a custom DSL (Domain-Specific Language) stored in PowerPoint object names. And  generates a `schema.json` to define the content to be generated.
2. **AI Generation** – Generates structured educational content through an AI provided in JSON format, outputing a `content.json` result.
3. **Template Engine** – Takes the `content.json`and injects the generated content into a `.pptx` template using a custom DSL .

This architecture completely decouples **content generation** from **presentation**, allowing any AI provider and any PowerPoint template to work together.

---

# Architecture

| Symbol | Meaning |
|--------|---------|
| ◀- | Input |
| -▶ | Output |
| (x) | Module / layer |
```
Template uploaded
    |
    ▼
template.pptx  ◀- (1) Template inspector    -▶ schema.json
    |
    ▼
schema.json     ◀- (2) AI Generation        -▶ content.json
    |
    ▼
content.json     ◀- (3) Template Engine     -▶ output.pptx
    |
    ▼
Generated PPTX
    │
    ▼
PDF / PPTX Download
```

---

# 1. Template Definition Request

The Template Definition Request is the payload sent to the AI provider.

Its purpose is **not** to describe PowerPoint placeholders.

Instead, it describes:

- lesson information
- teacher instructions
- worksheet requirements
- expected output schema

Example:

```json
{
    "lesson": {
        "language": "English",
        "cefr": "A1",
        "topic": "Future Simple",
        "grammar": [
            "will",
            "time expressions"
        ]
    },

    "teacher": {
        "instructions": "Use school vocabulary."
    },

    "template": {
        "id": "guess-who",

        "requirements": {
            "characters": 9,
            "answers_per_character": 4
        }
    },

    "output_schema": {
        ...
    }
}
```

The AI receives only educational requirements.

It never receives PowerPoint placeholders.

---

# 2. AI Response

The AI returns only structured content.

Example:

```json
{
    "title": "Guess Who",

    "clues": [
        {
            "icon": "shopping",
            "label": "will buy..."
        },
        {
            "icon": "meeting",
            "label": "will meet..."
        }
    ],

    "characters": [
        {
            "gender": "male",

            "portrait": {
                "prompt": "teenage boy with curly hair"
            },

            "answers": [
                {
                    "icon": "shopping",
                    "text": "buy a pencil tomorrow"
                },
                {
                    "icon": "meeting",
                    "text": "his teacher tomorrow"
                }
            ]
        }
    ]
}
```

The AI does **not** generate:

- placeholder names
- PowerPoint object names
- slide information
- formatting
- layout

Its only responsibility is generating educational content.

---

# 3. PowerPoint Template Engine

The Template Engine loads:

- PowerPoint template
- AI JSON
- Asset library

It then generates the final worksheet.

The engine is responsible for:

- parsing the PPTX
- reading object names from the Selection Pane
- interpreting the DSL
- duplicating groups
- replacing text
- replacing images
- replacing icons
- fitting text
- exporting PPTX/PDF

---

# DSL (Domain-Specific Language)

The DSL is stored in the **Selection Pane object names**.

Instead of using object names like:

```
CHARACTER_1
TEXTBOX_4
IMAGE_12
```

objects receive semantic names describing their purpose.

Example:

```
bind:title

repeat:characters

bind:text

lookup:icon

image:portrait
```

The DSL tells the engine **what to do** with each object.

---

# DSL Keywords

## bind:

Binds a text object to the current JSON property.

Selection Pane:

```
bind:title
```

JSON:

```json
{
    "title": "Guess Who"
}
```

Result:

```
Guess Who
```

---

## repeat:

Marks a PowerPoint group as repeatable.

Selection Pane:

```
repeat:characters
```

JSON:

```json
{
    "characters":[
        {...},
        {...},
        {...}
    ]
}
```

The engine duplicates the group three times.

Each duplicated group receives a different JSON context.

---

## lookup:

Replaces an object using an existing resource.

Selection Pane:

```
lookup:icon
```

Current JSON:

```json
{
    "icon":"shopping"
}
```

The engine loads:

```
assets/icons/shopping.svg
```

and replaces the picture.

Unlike `image:`, no AI image generation occurs.

---

## image:

Marks an object that should receive an AI-generated image.

Selection Pane:

```
image:portrait
```

Current JSON:

```json
{
    "portrait": {
        "prompt":"teenage girl with curly black hair"
    }
}
```

The engine:

1. sends the prompt to an image generator
2. receives an image
3. replaces the PowerPoint picture

---

## ignore

Objects marked as:

```
ignore
```

are skipped by the engine.

Useful for decorative elements.

---

# Component Tree

A PowerPoint slide is interpreted as a component tree.

Example:

```
Slide
│
├── bind:title
│
├── bind:instructions
│
├── Group: repeat:clues
│   │
│   ├── lookup:icon
│   └── bind:label
│
├── Group: repeat:characters
│   │
│   ├── image:portrait
│   │
│   ├── Group: repeat:answers
│   │   │
│   │   ├── lookup:icon
│   │   └── bind:text
│   │
│   └── bind:gender
│
└── bind:footer
```

Each `repeat:` keyword changes the current JSON context.

Nested repeat groups allow arbitrary levels of hierarchy.

---

# Generation Process

```
Load PPTX
      │
      ▼
Parse Selection Pane
      │
      ▼
Read DSL
      │
      ▼
Load AI JSON
      │
      ▼
repeat:*
      │
Duplicate groups
      │
      ▼
bind:*
      │
Replace text
      │
      ▼
lookup:*
      │
Replace existing assets
      │
      ▼
image:*
      │
Generate images
      │
      ▼
Adjust layout
      │
      ▼
Export PPTX
      │
      ▼
Export PDF
```

---

# Design Principles

The architecture follows these principles:

- **Content is independent of presentation.**
- **The AI generates educational data, not PowerPoint layouts.**
- **PowerPoint templates contain reusable layout components.**
- **The Template Engine is responsible for binding structured data to the template.**
- **The DSL is declarative, describing *what* should happen rather than *how* to implement it.**
- **Templates remain reusable regardless of the AI provider or educational topic.**

---

# Benefits

- Supports any AI provider through a common JSON contract.
- Templates are reusable across different lessons and topics.
- Contributors create templates without writing code.
- New worksheet types can be added without modifying the AI.
- Layout changes do not require changes to prompts or JSON schemas.
- New DSL directives can be introduced while preserving backward compatibility.
- The same Template Engine can generate worksheets, flashcards, board games, certificates, presentations, and other educational materials from structured content.
