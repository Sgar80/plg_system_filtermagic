# FilterMagic v1.1

Add custom filters to your Joomla 6 category pages.

<img src="screenshot.png" width="936" />

> This is a fork of the original [FilterMagic](https://github.com/nikosdion/plg_system_filtermagic) plugin
> by [Nicholas K. Dionysopoulos](https://www.dionysopoulos.me), ported to **Joomla 6** with additional
> features and bug fixes. A huge thank you to Nicholas for his excellent work and for making it
> available as open source. 🙏

---

## Overview

FilterMagic lets you add the following kinds of filters for each category displayed in the frontend of your site:

- **Subcategory** — display articles belonging to one or more selected subcategories; useful for Blog views and derivatives
- **Tag** — display articles tagged with one or more selected tags
- **Text search** _(new in v1.1)_ — display articles whose title, intro text, or body contains the typed string (partial, case-insensitive)
- **Custom fields** — display articles whose custom field value matches the filter selection

> **Custom field filters** only perform exact matching. Use them with `list`, `radio`, or `SQL` field types. Partial text search on custom fields is not supported — use the **Text search filter** instead.

You can also filter by custom fields located inside **subform fields**. An article will be shown if _any_ of its subform rows matches the filter value.

Filters are defined as standard Joomla XML form files.

---

## Requirements

|        | Minimum |
| ------ | ------- |
| Joomla | 6.0     |
| PHP    | 8.1     |

---

## Use cases

- **Resource directories** built with Joomla articles and custom fields — let users dynamically filter by subcategory, tag, or field value instead of browsing everything at once
- **Simple stores** based on Joomla content and extensions like J2Store — replace a long list of menu items with a dynamic filter interface

---

## Usage

### Location of filter files

Create a file named `filter_123.xml` where `123` is the category ID you want to filter. The frontend menu item **must** use a category article list view (Article List or Blog View) pointing to that category.

The file can be placed in either of the following locations (template path takes precedence):

```
templates/YOUR_TEMPLATE/filters/filter_123.xml
plugins/system/filtermagic/filters/filter_123.xml
```

---

### Format of filter files

A filter file is a standard Joomla XML form file. All filter fields go inside `<fields name="filter">`.

```xml
<?xml version="1.0" encoding="utf-8"?>
<form addfieldprefix="Dionysopoulos\Plugin\System\FilterMagic\Field">
    <fields name="filter">

        <field
            name="catid"
            type="subcategory"
            label="JCATEGORY"
            hint="JOPTION_SELECT_CATEGORY"
            published="1"
            language="*"
            root="123"
        />

        <field
            name="tag"
            type="tags"
            label="JTAG"
            hint="JOPTION_SELECT_TAG"
            multiple="false"
            mode="nested"
            custom="false"
        >
            <option value="">JOPTION_SELECT_TAG</option>
        </field>

        <field
            name="filter_text"
            type="text"
            label="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_LABEL"
            description="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_DESC"
            hint="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_HINT"
        />

        <field
            name="colour"
            type="custom"
            customfield="1"
            default=""
            class=""
            layout="joomla.form.field.list"
        >
            <option value="">- Colour -</option>
        </field>

    </fields>
</form>
```

---

### Subcategory filter

Filters articles by their category. The `<form>` tag **must** include `addfieldprefix="Dionysopoulos\Plugin\System\FilterMagic\Field"`.

The field **must** be named `catid`:

```xml
<field
    name="catid"
    type="subcategory"
    label="JCATEGORY"
    hint="JOPTION_SELECT_CATEGORY"
    root="123"
    published="1"
    language="*"
    multiple="false"
/>
```

| Attribute   | Description                                                                                      |
| ----------- | ------------------------------------------------------------------------------------------------ |
| `root`      | **Required.** Category ID of your frontend menu item — limits the list to its subcategories only |
| `published` | Comma-separated list of publish states to include. Recommended: `"1"`                            |
| `language`  | Comma-separated language filter. Default: `*` (all languages)                                    |
| `multiple`  | Set to `"1"` to allow selecting multiple subcategories                                           |

Subcategories are automatically filtered by the user's viewing access level. An empty "deselect" option is always added automatically.

---

### Tag filter

Filters articles by their assigned tags. The `<form>` tag **must** include `addfieldprefix`.

The field **must** be named `tag`. The `<option>` child is **required** to allow deselection:

```xml
<field
    name="tag"
    type="tags"
    label="JTAG"
    hint="JOPTION_SELECT_TAG"
    multiple="false"
    mode="nested"
    custom="false"
>
    <option value="">JOPTION_SELECT_TAG</option>
</field>
```

Set `multiple="true"` to allow selecting multiple tags. Use the optional `root="123"` attribute to limit the list to tags nested under a specific tag (tag 123 itself is excluded).

---

### Text search filter _(new in v1.1)_

Filters articles with a **case-insensitive partial match** against `title`, `introtext`, and `fulltext`.

The field **must** be named `filter_text`:

```xml
<field
    name="filter_text"
    type="text"
    label="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_LABEL"
    description="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_DESC"
    hint="PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_HINT"
/>
```

Add the following keys to your language `.ini` file (not `.sys.ini`):

**`en-GB/plg_system_filtermagic.ini`**

```ini
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_LABEL="Search by title"
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_DESC="Filter the list to show only articles whose title contains the entered text (case-insensitive, partial match)."
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_HINT="Type part of a title..."
```

**`it-IT/plg_system_filtermagic.ini`**

```ini
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_LABEL="Cerca per titolo"
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_DESC="Filtra l'elenco mostrando solo gli articoli il cui titolo contiene il testo inserito (senza distinzione maiuscole/minuscole, corrispondenza parziale)."
PLG_SYSTEM_FILTERMAGIC_FILTER_TEXT_HINT="Digita parte di un titolo..."
```

> **Performance note:** The text filter uses SQL `LIKE '%value%'` wildcards and is not a full-text search engine. For large datasets, combine it with other filters (subcategory, tag) to keep result sets manageable.

---

### Custom field filter

Filters articles by the **exact value** of a Joomla custom field.

```xml
<field
    name="colour"
    type="custom"
    customfield="1"
    default=""
    class=""
    layout="joomla.form.field.list"
>
    <option value="">- Colour -</option>
</field>
```

| Attribute                                  | Description                                                         |
| ------------------------------------------ | ------------------------------------------------------------------- |
| `name`                                     | **Must** match the custom field `name` (not its label) exactly      |
| `type`                                     | Always `"custom"` — ignored by Joomla but required for form parsing |
| `customfield`                              | **Must** be `"1"` — tells FilterMagic this is a custom field filter |
| `default`                                  | Set to `""` to allow filter deselection                             |
| `class="" layout="joomla.form.field.list"` | Renders the field as a plain dropdown instead of a toggle button    |

Always include an `<option value="">` child to let users deselect the filter.

#### Subform fields

You can filter by a field nested inside a subform. Set `name` to the subform field's name (not the subform itself). An article matches if **any** of its subform rows contains the searched value.

> Avoid using the same field in two or more different subforms — you may get unexpected results.

#### Supported field types

Only **exact-match** field types are supported: `list`, `radio`, `checkboxes`, `sql`. Using `text`, `url`, `email`, or complex types (maps, editors) is strongly discouraged — users would need to type the exact stored value including capitalisation and punctuation.

#### How does it work?

FilterMagic calls the same Joomla custom fields plugin events used in the backend article edit form (`onCustomFieldsPrepareDom`). The resulting XML field definition is transplanted into the filter form, enriched with the attributes and options from your XML file. This makes the filter display completely agnostic to the underlying field type.

---

## The frontend form

The filter form is rendered via the `onContentAfterTitle` event — it appears **below the category title** and above the category description. This position is currently non-configurable.

Rendering uses two Joomla layouts you can override in your template:

```
templates/YOUR_TEMPLATE/html/layouts/filtermagic/form.php
templates/YOUR_TEMPLATE/html/layouts/filtermagic/form/fields.php
```

The form POSTs to the current URL. Filter layouts are global — you cannot have different layouts per category.

---

## Under the hood

Filter values are stored in the **user session**, scoped per category. Values are reset when the user logs out or the session expires. There is no provision for persisting filter preferences across logins.

---

## Changelog

### v1.1.0 — Joomla 6 Compatibility Port _(this fork)_

This release ports the plugin to **Joomla 6 / PHP 8.1+**, fixes all deprecated and removed API calls, adds a new text search filter, and corrects several bugs present in the original code.

#### 🆕 New Feature: Text Search Filter

- Added `filter_text` field for partial case-insensitive search across article title, intro text, and full text
- Input is safe against SQL injection (prepared statement binding) and LIKE wildcard abuse (metacharacter escaping)

#### 🔧 Joomla 6 Compatibility

- Removed `#[ArrayShape]` — IDE-only annotation, not available in production
- Removed `QueryElement` — no longer public API in J6; replaced with `strpos((string) $query, 'WHERE')`
- Fixed Registry namespace: `Joomla\CMS\HTML\Registry` → `Joomla\Registry\Registry`
- Replaced `Factory::getUser()` with `Factory::getApplication()->getIdentity()` (null-safe)
- Updated type hint: `DatabaseDriver` → `DatabaseInterface`
- Service provider: removed unused `MVCComponent` import, added return types to closures
- XML manifest: added `<targetplatform name="joomla" version="6.*" />` and `<php_minimum>8.1</php_minimum>`
- Removed deprecated `JNO`/`JYES` option keys from XML config

#### 🐛 Bug Fixes

- `SubcategoryField`: fixed bind param typo `filter_access` → `filter.access` (access filter was silently broken)
- `TagsField`: renamed `$isNested` → `$isNestedMode` to avoid collision with `isNested()` method
- `Buffer`: fixed `SEEK_SET` boundary condition from `< strlen()` to `<= strlen()`
- `TagsField`: replaced loose `==` with strict `(int) $option->value === $id`
- Layout `form.php`: `Uri::current()` and dynamic form ID now escaped with `htmlspecialchars()`
- Layout `fields.php`: `$field->label` and `data-showon` JSON now properly escaped
- `SubcategoryField`: removed redundant double `$options = []` declaration

#### ✅ Code Quality

- Added missing return types across all classes
- Added `mixed` type hint to untyped parameters (PHP 8.0+)
- Public properties updated to typed `protected` where appropriate
- `array_map` closures replaced with arrow functions `fn() =>`
- `is_null()` replaced with `=== null`
- `LayoutHelper`: extracted duplicated path logic into `resolvePath()`
- `Buffer`: added null-safe `?? ''` guards on all `strlen()` calls
- Removed unreachable `break` after `return` in `switch` blocks
- `or die` replaced with `|| die`

#### 🌍 Translations

- Added Italian (`it-IT`) translation: `plg_system_filtermagic.ini` and `plg_system_filtermagic.sys.ini`

---

### v1.0.0 — Original release by Nicholas K. Dionysopoulos

See the [original repository](https://github.com/nikosdion/plg_system_filtermagic) for the original changelog and documentation.
