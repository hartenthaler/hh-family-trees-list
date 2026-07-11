# Public API

`hh-family-trees-list` exposes the configured research purpose of a family tree to other webtrees modules.

## Research purpose

```php
public function researchPurpose(Tree $tree): string
```

### Parameter

- `$tree`: the webtrees `Fisharebest\Webtrees\Tree` whose configured purpose is requested. The calling module supplies the current or otherwise relevant tree explicitly; the API does not depend on an HTTP request attribute.

### Return value

The method returns the purpose label translated into the current user's language. It returns an empty string if no purpose is configured or if a stored purpose key is no longer supported.

The available purposes currently include family and ancestry research, one-name studies, one-place studies or local family books, farm and farmstead research, thematic research, migration research, community research, and test purposes. `Test` can describe a tree used to test webtrees functions as well as genealogical test data used for other purposes.

The purpose is stored as the tree preference `HH_FAMILY_TREES_PURPOSE`. Consumers should use this public method instead of reading the preference directly, because the method validates the stored key and translates its label.

## Optional integration

Modules must treat `hh-family-trees-list` as an optional dependency and verify the method before calling it.

```php
$purpose = '';

if (is_callable([$familyTreesListModule, 'researchPurpose'])) {
    $purpose = $familyTreesListModule->researchPurpose($tree);
}
```

The caller is responsible for locating the enabled module instance and deciding how an empty result is presented.

## Stability

The method name, parameter type, and return type form the public contract. New purpose keys may be added without changing the method signature.
