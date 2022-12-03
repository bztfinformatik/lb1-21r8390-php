# Code Blocks

[Code blocks](https://squidfunk.github.io/mkdocs-material/reference/code-blocks/) and examples are an **essential** part of technical project documentation. Material for MkDocs provides different ways to set up syntax highlighting for code blocks, either during build time using [Pygments](https://pygments.org/) or during runtime using a _JavaScript_ syntax highlighter.

## Usage

Code blocks must be enclosed with two separate lines containing three backticks. To add syntax highlighting to those blocks, add the language shortcode directly after the opening block. See the list of available [lexers](https://pygments.org/docs/lexers/) to find the shortcode for a given language:

````markdown title="Code block with syntax highlighting"
```python title="bubble_sort.py"
def bubble_sort(items):
    for i in range(len(items)):
        for j in range(len(items) - 1 - i):
            if items[j] > items[j + 1]:
                items[j], items[j + 1] = items[j + 1], items[j]
```
````

```python title="bubble_sort.py"
def bubble_sort(items):
    for i in range(len(items)):
        for j in range(len(items) - 1 - i):
            if items[j] > items[j + 1]:
                items[j], items[j + 1] = items[j + 1], items[j]
```

## Annotations

One of the flagship features of Material for MkDocs is the ability to inject [annotations](https://squidfunk.github.io/mkdocs-material/reference/annotations/) – little markers that can be added almost anywhere in a document and expand a tooltip containing arbitrary Markdown on click or keyboard focus.

Code annotations can be placed anywhere in a code block where a comment for the language of the block can be placed, e.g. for JavaScript in `// ...` and `/* ... */`, for YAML in `#`.:

````markdown title="Code block with annotations"
```yaml
theme:
    features:
        - content.code.annotate # (1)
```

1.  :man_raising_hand: I'm a code annotation! I can contain `code`, **formatted
    text**, images, ... basically anything that can be written in Markdown.
````

```yaml
theme:
    features:
        - content.code.annotate # (1)!
```

1.  :man_raising_hand: I'm a code annotation! I can contain `code`, **formatted
    text**, images, ... basically anything that can be written in Markdown.

## Line

[Line numbers](https://squidfunk.github.io/mkdocs-material/reference/code-blocks/#adding-line-numbers) can be added to a code block by using the `linenums="<start>"` option directly after the shortcode, whereas `<start>` represents the starting line number. A code block can start from a line number other than `1`, which allows to split large code blocks for readability.

Specific lines can be highlighted by passing the line numbers to the hl_lines argument placed right after the language shortcode. Note that line counts start at 1, regardless of the starting line number specified as part of `linenums`:

````markdown title="Code block with line numbers"
```py linenums="1" hl_lines="2 3"
def bubble_sort(items):
    for i in range(len(items)):
        for j in range(len(items) - 1 - i):
            if items[j] > items[j + 1]:
                items[j], items[j + 1] = items[j + 1], items[j]
```
````
