# Meta-plan: membership package closeout, review and later scaffold work

**Updated:** 2026-09-18. **Current sequence:** completed Phase 1/1b implementation → completed [bounded Phase 2 closeout and full review](phase-2-bounded-hardening-closeout.md) → separate Phase 3 plan. Implementation, verification, integration and release are separate statuses; A8 owns eventual release preparation. No phase boundary requires a tag.

## Completed Phase 1 and Phase 1b implementation

| Work | Current disposition | Detailed evidence |
|---|---|---|
| Phase 1 bugs/config | Implementation complete; original merge and verification record retained as historical evidence. | [Phase 1](phase-1-bugfixes-and-config-lockdown.md) |
| A1–A7 | Historically completed. A5's permission dependency was intentionally removed by replacement Track B; old role schema and upgrade instructions do not apply to fresh membership-only installations. | [Phase 1b / A8](phase-1b-pre-release-and-review-remainder.md) |
| Replacement B1–B8 | Implemented at `b40c711`: shared actions, host policies, participants, commit observations, invitations, tenancy, fresh schema and installer. | [Track B log](../change-logs/phase-1b-track-b-membership-only.md), [public contract](../membership-contract.md) |
| B9 and existing-account pending acceptance | Deferred; neither is a Phase 2 requirement. | [Replacement Track B](phase-1b-track-b-membership-only.md) |

Historical role-bearing invitations, package administrator policies/events, model compatibility wrappers and a backwards-compatible minor refactor are superseded. Hosts own privileges, panel admission and tenant enumeration. The accepted contract targets fresh installations and does not require a legacy-consumer migration audit. Earlier review documents remain historical evidence, not current implementation instructions.

## Phase 2 — Bounded closeout

**Status:** implemented and independently verified, full-package review completed with both demonstrated defects corrected; see the [closeout log](../change-logs/phase-2-bounded-hardening-closeout.md) and [full review](../code-reviews/2026-09-18-phase-2-membership-package-review.md). Separate Phase 3 planning is next; A8 release remains independent.

Scope was defined by [the closeout plan](phase-2-bounded-hardening-closeout.md): extract atomic program-team creation and explicit-actor candidate discovery, finish neutral sender copy/config comments, reconcile status, inventory residual boundaries, and verify. Keep each extraction and behavioral coverage together in a reviewable commit. The package remains one installable Laravel/Filament package.

Completion means verified implementation and a full-review handoff, not a published release. The full review must cover the entire membership package and distinguish architectural findings, code/documentation gotchas and demonstrated runtime bugs. Detailed Phase 3 planning follows that review and any required fixes.

## Phase 3 — Host-owned UI scaffold: objective and pending decisions

The surviving objective is a reusable membership core with host-owned UI, provisionally UI-free but Filament-aware. The full review may challenge that direction. A separate plan must decide navigation/URL and authentication boundaries, which presentation adapters remain reusable, scaffold namespaces and overwrite rules, installer/config responsibilities, optional panel topology, reference-host design, and release sequencing. The existing User subclass contract remains supported until that later decision.

No core-owned privilege policies, invitation role choice, mandatory tenancy-trait conversion, label/config deletion or prescribed installer flags are selected here. No scaffold conversion or reference app is part of Phase 2.

## Phase 4 — Monorepo direction pending companion-package validation

The broad direction remains shared development with the ODK companion packages and a runnable reference host, while preserving Composer distribution. The former proposed layout (`packages/team-management`, `packages/odk-link-core`, `packages/odk-link-filament`, `apps/reference-app`), history-import approach, path repositories, split mirrors, tooling and lockstep versions are hypotheses to validate with the companion package, not approved implementation requirements. Package boundaries, dependency direction, tooling, versioning and timing need an explicit later decision; this reconciliation does not revalidate them.

## Integration and release

[A8](phase-1b-pre-release-and-review-remainder.md#a8-release--pending) tracks integration of the actual membership-only work, final documentation and verification, and eventual release. Keep `composer test`, `composer analyse`, `vendor/bin/pint --test` and mechanical diff checks green for integration. Update public documentation with public API changes. Do not merge, tag or publish merely because Phase 2 is complete.
