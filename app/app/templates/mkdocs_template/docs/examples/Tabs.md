# Tabs

Sometimes, it's desirable to group alternative content under different [tabs](https://squidfunk.github.io/mkdocs-material/reference/content-tabs/), e.g. when describing how to access an API from different languages or environments. Material for MkDocs allows for beautiful and functional tabs, grouping code blocks and other content.

## Usage

Code blocks are one of the primary targets to be grouped[^1], and can be considered a special case of content tabs, as tabs with a single code block are always rendered without horizontal spacing:

````markdown
=== "C"

    ``` c
    #include <stdio.h>

    int main(void) {
      printf("Hello world!\n");
      return 0;
    }
    ```

=== "C++"

    ``` c++
    #include <iostream>

    int main(void) {
      std::cout << "Hello world!" << std::endl;
      return 0;
    }
    ```
````

=== "C"

    ``` c
    #include <stdio.h>

    int main(void) {
      printf("Hello world!\n");
      return 0;
    }
    ```

=== "C++"

    ``` c++
    #include <iostream>

    int main(void) {
      std::cout << "Hello world!" << std::endl;
      return 0;
    }
    ```

[^1]: Content tabs can contain arbitrary nested content, including further content tabs, and can be nested in other blocks like admonitions or blockquotes.
