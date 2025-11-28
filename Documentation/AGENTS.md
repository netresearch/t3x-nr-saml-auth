<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2025-11-28 -->

# AGENTS.md — Documentation

## 1. Overview

RST documentation for rendering on docs.typo3.org. Follows TYPO3 documentation standards.

**Structure:**
- `Index.rst` — Main entry point
- `Introduction/` — Extension overview
- `Installation/` — Setup instructions
- `Configuration/` — Settings reference
- `Developer/` — Developer guide and events
- `Migration/` — Upgrade guides
- `Changelog/` — Version history

## 2. Setup & environment

Documentation renders automatically on docs.typo3.org when pushed to main branch.

**Local preview:**
```bash
# Using Docker (TYPO3 docs rendering)
docker run --rm -v $(pwd)/Documentation:/project/Documentation \
  t3docs/render-documentation makehtml
```

## 3. Build & tests

```bash
# Validate RST syntax (requires docutils)
rst-lint Documentation/**/*.rst

# Check internal links
sphinx-build -b linkcheck Documentation _build/linkcheck
```

## 4. Code style & conventions

- **reStructuredText** format (`.rst` files)
- **4-space indentation** for directives
- **Line length:** 80-100 characters preferred
- **Headings:** Use consistent underline characters

### Heading hierarchy

```rst
=======
Level 1
=======

Level 2
=======

Level 3
-------

Level 4
~~~~~~~
```

### Code blocks

```rst
..  code-block:: php

    // PHP code here
    $service = new SamlService();

..  code-block:: yaml

    # YAML configuration
    services:
      _defaults:
        autowire: true
```

## 5. Security & safety

- **Never include** real SAML configurations, certificates, or secrets
- **Use example.com** domains in examples
- **Sanitize** any screenshots (blur sensitive data)

## 6. PR/commit checklist

- [ ] RST syntax is valid
- [ ] Internal links work (`:ref:` targets exist)
- [ ] Code examples are tested and work
- [ ] No real credentials or secrets
- [ ] Changelog updated for user-facing changes

## 7. Good vs. bad examples

### Cross-references

```rst
..  Good: Use :ref: for internal links
See :ref:`events` for the complete event reference.

..  Bad: Hardcoded paths
See `Developer/Events.rst` for the complete event reference.
```

### Code examples

```rst
..  Good: Specify language and use realistic examples
..  code-block:: php

    use Netresearch\NrSamlAuth\Event\BeforeUserCreationEvent;

    final class CustomListener
    {
        public function __invoke(BeforeUserCreationEvent $event): void
        {
            $userData = $event->getUserData();
            $userData['company'] = $event->getSamlAttributes()['company'][0] ?? '';
            $event->setUserData($userData);
        }
    }

..  Bad: No language, incomplete example
..  code-block::

    // do something with the event
    $event->setUserData($data);
```

### Configuration references

```rst
..  Good: Use confval directive
..  confval:: sp.entityId
    :type: string
    :required: true

    The Service Provider entity ID (your application's identifier).

..  Bad: Plain text
sp.entityId - The Service Provider entity ID. Required. String type.
```

## 8. When stuck

- **TYPO3 Doc Style Guide:** https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/
- **RST Primer:** https://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html
- **TYPO3 Rendering:** https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/WritingReST/

## 9. House Rules

- **Settings.cfg** must have correct version and release
- **guides.xml** must exist for docs.typo3.org rendering
- **Includes.rst.txt** provides common text roles
- All pages must be in `toctree` (check `Sitemap.rst`)
