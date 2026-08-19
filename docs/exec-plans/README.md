# Execution plans

Working directory for agent execution plans (multi-step task breakdowns that outlive a single session).

- Put in-progress plans in `active/`, finished ones in `completed/` (create the directories on first use; they are not tracked while empty).
- A plan is a Markdown file named `YYYY-MM-DD-<topic>.md` describing goal, steps, and verification.
- Keep plans out of `Documentation/` — that tree is rendered for docs.typo3.org.
