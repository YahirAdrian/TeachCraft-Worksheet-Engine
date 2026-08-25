# 1. Template Inspector


The Template Inspector analyzes a contributor-created PowerPoint worksheet template before that template is used for generation.

Its job is to understand **what content the template expects**, not to generate that content.

Typical responsibilities include:

- Opening and inspecting the `.pptx` template.
- Reading semantic object names and TeachCraft DSL directives from PowerPoint objects.
- Detecting supported directives.
- Determining the content fields and collections required by the template to build the `schema.json` containing the template metadata, and template content schema.
- Validating that the template can be processed safely by the Worksheet Generation Engine.

Example:

```text
template/input/template.pptx
+ template/input/metadata.json
          │
          ▼
Template Inspector
          │
          ▼
template/schema/schema.json
```

The Template Inspector validates that the PowerPoint template contains the DSL supported directives, maps it into the JSON schema, and joins it with the template metadata.
