# 2. AI Generator

**Directory:** [`/ai_generator`](./ai_generator)  
**Specification:** [`/docs/ai-generator.md`](./docs/ai-generator.md)

The AI Generator creates the educational content required by a worksheet.

**It receives:**

- the template `schema.json`,
- lesson information,
- teacher instructions/additional prompt,
- worksheet-specific requirements,

and converts them into a request for an AI provider.

Its primary output is **structured JSON content** that satisfies the schema expected by the selected template.

Typical responsibilities include:

- Using prompts from lesson and template requirements.
- Communicating with an AI provider.
- Requesting structured output.
- Validating the returned JSON.
- Normalizing provider responses into TeachCraft's internal format.
- Returning only educational content required by the template.

The AI Generator does **not** decide PowerPoint positions, formatting, slide layout, or object placement.

Example:

```text
Template Definition (schema.json)
+ Teacher/lesson Requirements
        │
        ▼
AI Generator
        │
        ▼
Structured Worksheet JSON content
```

Because this layer is independent from PowerPoint rendering, TeachCraft can support different AI providers without changing the template-processing logic.