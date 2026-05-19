# Security and Coverage Scans

> 20 nodes · cohesion 0.13

## Key Concepts

- **path** (23 connections) — `extract_assets.js`
- **security_scan.py** (6 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **main()** (4 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- **type_coverage.py** (3 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- **run_full_scan()** (3 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **scan_code_patterns()** (3 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **scan_configuration()** (3 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **scan_dependencies()** (3 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **scan_secrets()** (3 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **check_python_coverage()** (3 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- **check_typescript_coverage()** (3 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- **.__init__()** (2 connections) — `.agent/skills/nextjs-react-expert/scripts/react_performance_checker.py`
- **main()** (2 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Validate no hardcoded secrets (OWASP A04).     Checks: API keys, tokens, passwor** (1 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Validate dangerous code patterns (OWASP A05).     Checks: Injection risks, XSS,** (1 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Validate security configuration (OWASP A02).     Checks: Security headers, CORS,** (1 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Execute security validation scans.** (1 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Validate supply chain security (OWASP A03).     Checks: npm audit, lock file pre** (1 connections) — `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- **Check TypeScript type coverage.** (1 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- **Check Python type hints coverage.** (1 connections) — `.agent/skills/lint-and-validate/scripts/type_coverage.py`

## Relationships

- [[Asset Extraction]] (1 shared connections)
- [[Design System Generation]] (1 shared connections)
- [[Auto Preview Server]] (1 shared connections)
- [[Checklist Runner]] (1 shared connections)
- [[Session Manager]] (1 shared connections)
- [[Verify All Script]] (1 shared connections)
- [[API Validator]] (1 shared connections)
- [[Database Schema Validator]] (1 shared connections)
- [[Accessibility Checker]] (1 shared connections)
- [[UX Audit]] (1 shared connections)
- [[GEO Checker]] (1 shared connections)
- [[i18n Checker]] (1 shared connections)

## Source Files

- `.agent/skills/lint-and-validate/scripts/type_coverage.py`
- `.agent/skills/nextjs-react-expert/scripts/react_performance_checker.py`
- `.agent/skills/vulnerability-scanner/scripts/security_scan.py`
- `extract_assets.js`

## Audit Trail

- EXTRACTED: 40 (59%)
- INFERRED: 28 (41%)
- AMBIGUOUS: 0 (0%)

---

*Part of the graphify knowledge wiki. See [[index]] to navigate.*