# Graph Report - .  (2026-05-19)

## Corpus Check
- 196 files · ~192,866 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 260 nodes · 368 edges · 25 communities (21 shown, 4 thin omitted)
- Extraction: 93% EXTRACTED · 7% INFERRED · 0% AMBIGUOUS · INFERRED: 25 edges (avg confidence: 0.8)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Design System Generation|Design System Generation]]
- [[_COMMUNITY_Asset Extraction|Asset Extraction]]
- [[_COMMUNITY_Core SearchBM25|Core Search/BM25]]
- [[_COMMUNITY_Security and Coverage Scans|Security and Coverage Scans]]
- [[_COMMUNITY_React Performance Checker|React Performance Checker]]
- [[_COMMUNITY_Checklist Runner|Checklist Runner]]
- [[_COMMUNITY_Verify All Script|Verify All Script]]
- [[_COMMUNITY_Convert Rules|Convert Rules]]
- [[_COMMUNITY_i18n Checker|i18n Checker]]
- [[_COMMUNITY_MCP Config|MCP Config]]
- [[_COMMUNITY_API Validator|API Validator]]
- [[_COMMUNITY_Auto Preview Server|Auto Preview Server]]
- [[_COMMUNITY_GEO Checker|GEO Checker]]
- [[_COMMUNITY_SEO Checker|SEO Checker]]
- [[_COMMUNITY_Session Manager|Session Manager]]
- [[_COMMUNITY_Mobile Audit|Mobile Audit]]
- [[_COMMUNITY_UX Audit|UX Audit]]
- [[_COMMUNITY_Database Schema Validator|Database Schema Validator]]
- [[_COMMUNITY_Accessibility Checker|Accessibility Checker]]
- [[_COMMUNITY_Lint Runner|Lint Runner]]
- [[_COMMUNITY_Test Runner|Test Runner]]
- [[_COMMUNITY_Lighthouse Audit|Lighthouse Audit]]
- [[_COMMUNITY_Playwright Runner|Playwright Runner]]
- [[_COMMUNITY_Update Script|Update Script]]
- [[_COMMUNITY_Search Utilities|Search Utilities]]

## God Nodes (most connected - your core abstractions)
1. `path` - 23 edges
2. `DesignSystemGenerator` - 11 edges
3. `PerformanceChecker` - 11 edges
4. `_search_csv()` - 8 edges
5. `run_script()` - 8 edges
6. `BM25` - 7 edges
7. `search()` - 7 edges
8. `generate_design_system()` - 7 edges
9. `run_script()` - 7 edges
10. `persist_design_system()` - 6 edges

## Surprising Connections (you probably didn't know these)
- `_generate_intelligent_overrides()` --calls--> `search()`  [INFERRED]
  .agent/.shared/ui-ux-pro-max/scripts/design_system.py → .agent/.shared/ui-ux-pro-max/scripts/core.py
- `persist_design_system()` --calls--> `path`  [INFERRED]
  .agent/.shared/ui-ux-pro-max/scripts/design_system.py → extract_assets.js
- `get_project_root()` --calls--> `path`  [INFERRED]
  .agent/scripts/auto_preview.py → extract_assets.js
- `main()` --calls--> `path`  [INFERRED]
  .agent/scripts/checklist.py → extract_assets.js
- `get_project_root()` --calls--> `path`  [INFERRED]
  .agent/scripts/session_manager.py → extract_assets.js

## Communities (25 total, 4 thin omitted)

### Community 0 - "Design System Generation"
Cohesion: 0.09
Nodes (25): DesignSystemGenerator, _detect_page_type(), format_ascii_box(), format_markdown(), format_master_md(), format_page_override_md(), generate_design_system(), _generate_intelligent_overrides() (+17 more)

### Community 1 - "Asset Extraction"
Cohesion: 0.10
Nodes (18): altMatch, buffer, cssContent, cssPath, cssSizeAfter, cssSizeBefore, fs, htmlContent (+10 more)

### Community 2 - "Core Search/BM25"
Cohesion: 0.15
Nodes (15): BM25, detect_domain(), _load_csv(), BM25 ranking algorithm for text search, Lowercase, split, remove punctuation, filter short words, Build BM25 index from documents, Score all documents against query, Load CSV and return list of dicts (+7 more)

### Community 3 - "Security and Coverage Scans"
Cohesion: 0.13
Nodes (17): path, main(), Validate no hardcoded secrets (OWASP A04).     Checks: API keys, tokens, passwor, Validate dangerous code patterns (OWASP A05).     Checks: Injection risks, XSS,, Validate security configuration (OWASP A02).     Checks: Security headers, CORS,, Execute security validation scans., Validate supply chain security (OWASP A03).     Checks: npm audit, lock file pre, run_full_scan() (+9 more)

### Community 4 - "React Performance Checker"
Cohesion: 0.17
Nodes (9): main(), PerformanceChecker, Check for data fetching in useEffect (Section 4), Check for missing React.memo, useMemo, useCallback (Section 5), Check for unoptimized images (Section 6), Generate final report, Check for sequential await patterns (Section 1), Check for barrel imports (Section 2) (+1 more)

### Community 5 - "Checklist Runner"
Cohesion: 0.27
Nodes (13): check_script_exists(), Colors, main(), print_error(), print_header(), print_step(), print_success(), print_summary() (+5 more)

### Community 6 - "Verify All Script"
Cohesion: 0.33
Nodes (11): Colors, main(), print_error(), print_final_report(), print_header(), print_step(), print_success(), print_warning() (+3 more)

### Community 7 - "Convert Rules"
Cohesion: 0.25
Nodes (10): generate_section_file(), group_rules_by_section(), main(), parse_frontmatter(), parse_rule_file(), Group all rules by their section prefix, Generate a merged section file, Main conversion function (+2 more)

### Community 8 - "i18n Checker"
Cohesion: 0.29
Nodes (9): check_hardcoded_strings(), check_locale_completeness(), find_locale_files(), flatten_keys(), main(), Flatten nested dict keys., Check for hardcoded strings in code files., Find translation/locale files. (+1 more)

### Community 9 - "MCP Config"
Cohesion: 0.25
Nodes (7): args, command, mcpServers, context7, shadcn, args, command

### Community 10 - "API Validator"
Cohesion: 0.36
Nodes (7): check_api_code(), check_openapi_spec(), find_api_files(), main(), Find API-related files., Check OpenAPI/Swagger specification., Check API code for common issues.

### Community 11 - "Auto Preview Server"
Cohesion: 0.54
Nodes (7): get_project_root(), get_start_command(), is_running(), main(), start_server(), status_server(), stop_server()

### Community 12 - "GEO Checker"
Cohesion: 0.36
Nodes (7): check_page(), find_web_pages(), is_page_file(), main(), Check a single web page for GEO elements., Check if this file is likely a public-facing page., Find public-facing web pages only.

### Community 13 - "SEO Checker"
Cohesion: 0.36
Nodes (7): check_page(), find_pages(), is_page_file(), main(), Check if this file is likely a public-facing page., Find page files to check., Check a single page for SEO issues.

### Community 14 - "Session Manager"
Cohesion: 0.57
Nodes (6): analyze_package_json(), count_files(), detect_features(), get_project_root(), main(), print_status()

### Community 17 - "Database Schema Validator"
Cohesion: 0.47
Nodes (5): find_schema_files(), main(), Find database schema files., Validate Prisma schema file., validate_prisma_schema()

### Community 18 - "Accessibility Checker"
Cohesion: 0.47
Nodes (5): check_accessibility(), find_html_files(), main(), Find all HTML/JSX/TSX files., Check a single file for accessibility issues.

### Community 19 - "Lint Runner"
Cohesion: 0.47
Nodes (5): detect_project_type(), main(), Detect project type and available linters., Run a single linter and return results., run_linter()

### Community 20 - "Test Runner"
Cohesion: 0.47
Nodes (5): detect_test_framework(), main(), Detect test framework and commands., Run tests and return results., run_tests()

### Community 21 - "Lighthouse Audit"
Cohesion: 0.50
Nodes (4): get_summary(), Run Lighthouse audit on URL., Generate summary based on scores., run_lighthouse()

### Community 22 - "Playwright Runner"
Cohesion: 0.40
Nodes (4): Run basic accessibility check., Run basic browser test on URL., run_accessibility_check(), run_basic_test()

## Knowledge Gaps
- **26 isolated node(s):** `fs`, `content`, `fs`, `imgDir`, `cssPath` (+21 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **4 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `path` connect `Security and Coverage Scans` to `Design System Generation`, `Asset Extraction`, `Checklist Runner`, `Verify All Script`, `Convert Rules`, `i18n Checker`, `API Validator`, `Auto Preview Server`, `GEO Checker`, `SEO Checker`, `Session Manager`, `Mobile Audit`, `UX Audit`, `Database Schema Validator`, `Accessibility Checker`, `Lint Runner`, `Test Runner`?**
  _High betweenness centrality (0.753) - this node is a cross-community bridge._
- **Why does `persist_design_system()` connect `Design System Generation` to `Security and Coverage Scans`?**
  _High betweenness centrality (0.291) - this node is a cross-community bridge._
- **Why does `search()` connect `Core Search/BM25` to `Design System Generation`?**
  _High betweenness centrality (0.124) - this node is a cross-community bridge._
- **Are the 22 inferred relationships involving `path` (e.g. with `persist_design_system()` and `get_project_root()`) actually correct?**
  _`path` has 22 INFERRED edges - model-reasoned connections that need verification._
- **What connects `fs`, `content`, `fs` to the rest of the system?**
  _101 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Design System Generation` be split into smaller, more focused modules?**
  _Cohesion score 0.0855614973262032 - nodes in this community are weakly interconnected._
- **Should `Asset Extraction` be split into smaller, more focused modules?**
  _Cohesion score 0.1 - nodes in this community are weakly interconnected._