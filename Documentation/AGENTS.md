<!-- Managed by agent: keep sections & order; edit content, not structure. Last updated: 2026-08-19 -->

# AGENTS.md — Documentation

## Overview

RST documentation for rendering on docs.typo3.org. Follows TYPO3 documentation standards.

**Structure:**
- `Index.rst` — Main entry point
- `Introduction/` — Extension overview
- `Installation/` — Setup instructions
- `Configuration/` — Settings reference
- `Developer/` — Developer guide and events
- `Migration/` — Upgrade guides
- `Changelog/` — Version history

## Setup & environment

Rendering configuration lives in `guides.xml` (docs.typo3.org toolchain). `Settings.cfg` holds legacy metadata.

**Local preview (requires Docker):**
```bash
composer docs:render   # or: make docs — renders via ghcr.io/typo3-documentation/render-guides
make docs-serve        # serve Documentation-GENERATED-temp at http://localhost:8000
composer docs:clean    # remove the generated output
```

## Build & tests

There is no dedicated RST lint script in this repo. The render itself is the check — it fails on invalid RST and reports broken `:ref:` targets:

```bash
composer docs:render
```

## Code style & conventions

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

## Security & safety

- **Never include** real SAML configurations, certificates, or secrets
- **Use example.com** domains in examples
- **Sanitize** any screenshots (blur sensitive data)

## PR/commit checklist

- [ ] RST syntax is valid
- [ ] Internal links work (`:ref:` targets exist)
- [ ] Code examples are tested and work
- [ ] No real credentials or secrets
- [ ] Changelog updated for user-facing changes

## Good vs. bad examples

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

    use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;

    final class CustomListener
    {
        public function __invoke(AfterUserLoggedInEvent $event): void
        {
            $user = $event->getUser();
            // react to the SSO login
        }
    }

..  Bad: No language, incomplete example
..  code-block::

    // do something with the event
    $event->setUserData($data);
```

Caution: `Developer/Events.rst` documents a `Netresearch\NrSamlAuth\Event\*` namespace (`BeforeUserCreationEvent` etc.) that does not exist in `Classes/` — the code only ships listeners for TYPO3 core events. Do not copy those class names into new docs or code examples until the drift is resolved.

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

## When stuck

- **TYPO3 Doc Style Guide:** https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/
- **RST Primer:** https://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html
- **TYPO3 Rendering:** https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/WritingReST/

## House Rules

- **Settings.cfg** must have correct version and release
- **guides.xml** must exist for docs.typo3.org rendering
- **Includes.rst.txt** provides common text roles
- All pages must be in `toctree` (check `Sitemap.rst`)
