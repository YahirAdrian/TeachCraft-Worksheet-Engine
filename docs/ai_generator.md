# 2. AI Generator

**Source:** [`src/AiGenerator`](../src/AiGenerator)  
**Specification:** [`docs/ai_generator.md`](./ai_generator.md)

The AI Generator creates the educational content required by a worksheet.

**It receives:**

- the template `schema.json` from `template/schema/`,
- lesson information from `template/input/lesson.json`,
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
template/schema/schema.json
+ template/input/lesson.json
        │
        ▼
AI Generator
        │
        ▼
template/content/content.json
```

Because this layer is independent from PowerPoint rendering, TeachCraft can support different AI providers without changing the template-processing logic.
