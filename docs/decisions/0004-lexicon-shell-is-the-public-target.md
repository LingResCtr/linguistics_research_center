# 0004. The `/lexicon/{slug}` shell is the public target; `/lex` is legacy

- Status: Accepted
- Date: 2026-09-16
- Deciders: Danny Law (LRC)
- Revisit when: ADR 0009 selects a non-Laravel platform for new public surfaces.
- Related: review §3, §7, §9 item 9; `docs/vision.md` principle 2; `docs/architecture/overview.md`

## Context

The same IELEX data is rendered by two public surfaces: the legacy `/lex/*` pages on a 2015 Foundation template with jQuery 1.11, and the newer multi-lexicon shell at `/lexicon/{slug}` on Bootstrap 5 that also serves SEMITILEX, MAYALEX and DRAVIDILEX. EIEOL lessons use the legacy template. Three visual designs and two renderings of one dataset drift apart and triple the cost of every UI change.

## Decision

All new public dictionary work targets the `/lexicon/{slug}` shell and its views in `server/resources/views/lexicon`. The `/lex/*` pages are legacy: no new features, only fixes for broken links or data errors. The roadmap's consolidation phase adds the legacy features the new shell lacks (Pokorny page index, language-family index with counts, previous/next etymon), then redirects `/lex/*` to `/lexicon/ielex/*` with permanent redirects and removes the Foundation template. The EIEOL layout is brought under the same shell afterwards.

## Consequences

- One place to fix the audit's typography, search, sidebar and citation findings.
- Legacy URLs must be preserved by redirect; a redirect map is part of the consolidation work and is tested.
- Until consolidation, IELEX readers can reach two different renderings; that is accepted as temporary.

## Alternatives considered

- Keep both and share components: doubles the template work and leaves the design split.
- Rebuild the public site on a new framework: no capacity, and the Laravel/Blade stack is not the problem.
