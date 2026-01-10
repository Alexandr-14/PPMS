# Agent Rules (PPMS)

# Agent Rules (PPMS)

## 1) Strict Tag Completion
- Always explicitly close every opened HTML tag.
- Always explicitly close every `<style>` block and every `<script>` tag.
- Never use placeholders (e.g., `...`, `""`, or similar) inside `<script>` or `<style>` blocks unless explicitly requested.

## 2) No Partial Refactors
- When editing a file, always include the full closing sequence of the block you are working on.
- If approaching token/length limits, prioritize correctly closing syntax blocks (CSS/JS/HTML) over starting new changes.

## 3) Encapsulation Check
Before finishing a response or patch, verify:
- `{ }`, `( )`, and `[ ]` are balanced.
- All CSS rules have closing braces.
- All HTML elements are properly nested.

## 4) Syntax Integrity
- Never leave a page in a broken state.
- If a code block is too long to complete safely, stop at a clean boundary (after closing tags/blocks) and warn the user.

## 5) Keep It Light
To reduce editor/chat overhead:
- Keep replies concise; avoid large code blocks unless requested.
- Prefer small, targeted patches over broad refactors.
- Minimize unnecessary tool calls and verbose output.

## 6) Updated Files to Report
- Always list all updated files after task completion.
- Include every file regardless of format.