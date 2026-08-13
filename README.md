# TeachCraft Worksheet Generation Engine 

<p align="center" style="margin-bottom: 32px;">
     <img alt="TeachCraft Worksheet Engine icon" src="assets/engine_icon.svg" width="256"/>
     <br/>
</div>



TeachCraft is an AI-powered platform for creating customizable, print-friendly English learning materials from reusable PowerPoint templates.

The Worksheet Generation Engine is the core system that turns a contributor-created .pptx template and a teacher's lesson requirements into a completed worksheet. It is intentionally divided into three independent layers so that template analysis, AI content generation, and PowerPoint rendering remain decoupled.

> ***In TeachCraft, a worksheet can refer to any printable educational material, including worksheets, flashcards, quizzes, games, graphics, and cheatsheets.***

## Engine Layers

The engine separates the system into three independent layers:

1. **Template Inspection** – Describes what content should be generated from a `.pptx`template using a custom DSL (Domain-Specific Language) stored in PowerPoint object names. And  generates a `schema.json` to define the content to be generated.
2. **AI Generation** – Generates structured educational content through an AI provided in JSON format, outputing a `content.json` result.
3. **Template Generator** – Takes the `content.json`and injects the generated content into a `.pptx` template using a custom DSL .

This architecture completely decouples **content generation** from **presentation**, allowing any AI provider and any PowerPoint template to work together.

## Architecture Overview

The generation workflow is divided into three layers:

```
PPTX Template
     │
     ▼
┌──────────────────────────┐
│  1. Template Inspector   │
│  /template_inspector     │
└────────────┬─────────────┘
             │
             │ schema.json (template metadad + schema)
             │
             ▼
┌──────────────────────────┐
│  2. AI Generator         │
│  /ai_generator           │
└────────────┬─────────────┘
             │
             │ content.json (generated from schema)
             ▼
┌──────────────────────────┐
│  3. Template Engine      │
│  /template_engine        │
└────────────┬─────────────┘
             │
             ▼
      Generated PPTX
             │
             ▼
        PDF Export
```

Each layer has a single primary responsibility and communicates with the next layer through structured data contracts.

## Project Structure

```
.
├── template_inspector/
│   └── ...
├── ai_generator/
│   └── ...
├── template_generator/
│   └── ...
├── docs/
│   ├── template_inspector.md
│   ├── ai_generator.md
│   └── template_generator.md
└── README.md

```

The root `README.md` provides the high-level architecture of the generation engine. Implementation rules, data contracts, validation behavior, and technical details belong in the corresponding documents under `/docs.



## End-to-End Generation Flow

A complete worksheet generation follows this sequence:

```text
1. Contributor uploads a PPTX template
                │
                ▼
2. Template Inspector analyzes the template
                │
                ▼
3. Template content schema is produced
                │
                ▼
4. Teacher selects the template and enters lesson requirements
                │
                ▼
5. AI Generator creates structured educational JSON
                │
                ▼
6. Template Engine binds the JSON to the PPTX template
                │
                ▼

The Template Inspector analyzes a contributor-created PowerPoint worksheet template before that template is used for generation.


```text
PowerPoint Template file
          │
          ▼
Template Inspector
          │
          ▼
Content schema.json
```

The Template Inspector validates that the PowerPoint template contains the DSL supported directives, maps it into the JSON schema, and joins it with the template metadata.

---



## End-to-End Generation Flow

A complete worksheet generation follows this sequence:

```text
1. Contributor uploads a PPTX template
                │
                ▼
2. Template Inspector analyzes the template
                │
                ▼
3. Template content schema is produced
                │
                ▼
4. Teacher selects the template and enters lesson requirements
                │
                ▼
5. AI Generator creates structured educational JSON
                │
                ▼
6. Template Engine binds the JSON to the PPTX template
                │
                ▼
7. Final PPTX worksheet is generated
                │
                ▼
8. Worksheet can be converted to PDF for printing
```

This separation keeps the three concerns independent:

```text
Template Inspector  →  What does this template require?
AI Generator        →  What educational content should fill it?
Template Engine     →  How is that content rendered in the template?
```
---
## Documentation

Detailed behavior for each layer is defined separately:

| Layer | Specification |
|---|---|
| Template Inspector | [`docs/template-inspector.md`](./docs/template_inspector.md) |
| AI Generator | [`docs/ai-generator.md`](./docs/ai_generator.md) |
| Template Engine | [`docs/template-engine.md`](./docs/template_engine.md) |

These specifications should be treated as the implementation source of truth for their respective modules. This `README.md` remains intentionally high level and serves as the main entry point to the Worksheet Generation Engine.

---

## Relationship to TeachCraft

The Worksheet Generation Engine is one subsystem of the larger TeachCraft application.

At the application level, TeachCraft provides features such as:

- worksheet/template discovery,
- template publishing,
- AI customization,
- generated worksheet storage,
- PPTX/PDF generation,
- and printable material delivery.

The three layers in this repository focus specifically on the pipeline that transforms a reusable PowerPoint template and teacher requirements into a generated educational worksheet.
