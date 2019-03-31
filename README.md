# Custom layouts for Layout Builder

Adds custom layouts to Layout Builder, which contain options to add css classes and titles to each section.

- Several custom layouts are provided here, which can be further adjusted in the theme.
- Css classes are managed via a vocabulary. There is one vocabulary for general css and another for css to use on titles.
- We use Chosen to make the class selection super easy. That adds tags-style functionality to the simple selector.
- The code massages the Layout Builder block list to change some category names and block titles so they make more sense in Layout Builder.

## Configuration

- Navigate to admin > extend and enable the module.
- The code assumes that vocabularies called `classes` and `title_classes` already exist.
- The code assumes a field called `field_class` is used on these vocabularies to store the actual classes.
- The actual class(es) are stored in a field on the term, which means any descriptive title can be used for the name of the class or class collection.

## Composer 

To include this module in a composer project, add the following to the 'repositories' section of the top level composer.json file:

```
        "lullabot/custom_layouts": {
            "type": "vcs",
            "url": "https://github.com/lullabot/custom_layouts.git",
            "no-api": true
        }
```

Then type the following on the command line:

```
composer require lullabot/custom_layouts
```
