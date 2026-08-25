# Domain-Specific Language (DSL) for Templates

TeachCraft templates use a Domain-Specific Language (DSL) to annotate template fields and values.First, the `Template Inspector` validates that the input `.pptx` template contains valid DSL keyworks in its selection pane.  The `AI generator` layer reads these annotations to determine how to generate content, which the `Template Generator` then applies to the validated `.pptx` template.

## DSL Keyworkds

**Syntax:** `[DSL_keyword]:[name]`
**Example:** `bind:title`


### bind:

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

### repeat:

Marks a PowerPoint group as repeatable.

Selection Pane:

```
repeat:characters
repeat:characters
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

RESULT: 
3 `repeat:characters` groups modified

---

### rbind:

Binds multiple text objects inside a group

Selection Pane:

```
repeat:characters
    rbind:answers
    rbind:answers
    rbind:answers
    rbind:answers
```

JSON:

```json
{
    "characters": [
        {
            "answers": [
                "coffee","by bus","football","never"
            ]
        },
        ...
    ]
}
```

Result:

```
4 texts binded inside the repeated group
```



### lookup:

Replaces an object using an existing image resource.

Selection Pane:

```
lookup:icon1
```

Current JSON:
**IMPORTANT:** Lookup should always use emoji unicode values, since the library is emoji-based.

```json
{
    "icon1":"1F6D2"
}
```


The engine loads:

```
assets/icons/1F6D2.svg
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


