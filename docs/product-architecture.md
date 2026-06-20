# Zadora LMS Product And Platform Architecture

## Phase 1: Product Analysis

### Product Modules

Zadora LMS is a frontend-first workforce learning platform delivered as a WordPress plugin. The product is organized into independently maintainable modules:

- Identity and access: custom login, role-aware dashboards, permissions, future magic links and social login.
- User and organization management: learners, instructors, training providers, companies, corporate admins, departments, groups, and seat allocation.
- Course delivery: courses, lessons, course statuses, private access rules, waitlists, assignments, progress, assessments, and learner activity.
- Certification: certificate templates, dynamic fields, certificate name capture, manual issuing, PDF generation, QR verification, expiry, wallet, and reports.
- Dashboards: learner, training provider, and corporate dashboards with role-specific widgets.
- Reporting: learner, course, corporate, certificate, revenue, export pipelines, and future advanced analytics.
- Payments: Stripe, Paystack, multiple currencies, orders, invoices, webhook processing, and future gateway adapters.
- Notifications and announcements: in-app notification center, dashboard notice boards, email templates, and future WhatsApp delivery.
- AI: quiz generation, learning objective generation, assisted grading, usage limits, audit logs, and provider abstraction for OpenAI and Claude.
- Developer platform: REST API, hooks, filters, template overrides, custom CSS, custom JavaScript, and future public SDK.
- Presentation layer: Gutenberg blocks, shortcodes, frontend app shell, service worker, offline page, design system, and theme override boundaries.

### User Roles

- Super Admin: owns global settings, branding, payments, AI integrations, companies, courses, and cross-tenant reporting.
- Training Provider: manages courses, lessons, assessments, certificates, learners, and provider-level reports.
- Corporate Admin: manages company users, departments, seats, assignments, compliance dashboards, and company reports.
- Instructor: manages assigned courses, progress review, and assessment grading.
- Learner: consumes learning, completes assessments, downloads certificates, receives notifications, and participates in groups.

### System Boundaries

Zadora owns its learning, certification, reporting, notification, payment, and frontend dashboard domains. WordPress remains the host runtime, authentication provider, media library, REST framework, roles/capability substrate, plugin lifecycle manager, and extension surface. Daily product operations must not depend on wp-admin.

External systems are integrated through ports/adapters:

- Payments: Stripe and Paystack.
- AI: OpenAI and Claude.
- Storage: S3-compatible and Bunny-compatible storage.
- PDF: DomPDF.
- Charts: Chart.js.
- Exports: CSV, Excel, PDF.

### Core Workflows

- Learner onboarding: login, dashboard access, assigned courses, course progress, assessment completion, certificate name capture, certificate issue, wallet access.
- Training provider publishing: create course, build lessons, configure assessment, choose certificate template, publish course, monitor enrollments and revenue.
- Corporate assignment: create company, allocate seats, create departments, add employees, assign courses, monitor compliance, export reports.
- Certificate verification: generate certificate number, render PDF, attach QR verification URL, expose public verification endpoint.
- Payment purchase: select course, create order, redirect/confirm gateway payment, process webhook, create enrollment, notify learner.
- AI-assisted authoring: submit source content, generate quiz/objectives, review generated output, save approved result.
- Instructor grading: review essay submission, request AI suggestion, adjust score/feedback, approve grade, trigger completion checks.

### Dependencies

- Runtime: WordPress, PHP 8.3+, MySQL.
- Frontend: React, Tailwind CSS, WordPress REST API.
- PDF: DomPDF.
- Charts: Chart.js.
- Payment SDKs: Stripe and Paystack clients.
- AI SDKs or HTTP clients: OpenAI and Claude-compatible clients.
- Storage adapters: WordPress uploads initially, then S3/Bunny adapters.

### Risks

- WordPress postmeta overuse can block reporting performance; dedicated tables are required.
- Certificate rendering and report exports can become long-running jobs; queue-ready service boundaries are needed.
- Payment webhooks require idempotency, signature verification, and strict audit trails.
- AI features require human approval, usage metering, prompt isolation, and provider abstraction.
- Corporate access rules can become complex; permissions must be policy-based rather than scattered conditionals.
- Multi-tenant V3 support must be anticipated with owner columns and organization boundaries from the start.
- Frontend quality can regress into wp-admin patterns unless the app shell, design tokens, and component system are treated as product code.

### Future Extensibility

The architecture preserves seams for marketplace apps, public API keys, white labeling, mobile app APIs, advanced analytics, additional payment gateways, WhatsApp notifications, and full SaaS multi-tenancy. Each domain exposes service contracts, REST controllers, database repositories, hooks, and filters.

## Phase 2: System Architecture

### Folder Structure

```text
zadora-lms.php
composer.json
package.json
docs/
assets/
  src/
    app/
    components/
    design-system/
    styles/
  dist/
src/
  Core/
  Database/
  Http/
  Security/
  Support/
  Modules/
    Ai/
    Announcements/
    Certificates/
    Companies/
    Courses/
    Enrollments/
    Notifications/
    Payments/
    Reporting/
    Users/
templates/
tests/
```

### Service Layer

Services are grouped by bounded context. Controllers translate REST requests into commands, services enforce business rules, repositories handle persistence, and policies authorize actions. WordPress hooks are only used at module boundaries.

### API Architecture

REST endpoints live under `/wp-json/zadora/v1`. Internal controllers are versioned and grouped by module. Public verification endpoints remain read-only and are intentionally separate from authenticated operational APIs.

### Authentication Strategy

MVP uses WordPress authentication cookies and nonces for same-site dashboard requests. REST endpoints validate the current user and capabilities. Future public APIs use scoped API keys or OAuth-style clients. Future passwordless login can be added behind the identity service without changing downstream modules.

### Permissions System

The permission model combines WordPress capabilities with Zadora policies. Capabilities answer "can this role attempt this action?" Policies answer "can this specific actor perform this action on this resource?" This avoids leaking company data across corporate boundaries.

### Certificate Engine

Certificate templates are stored in dedicated tables as structured JSON layouts. Issued certificates are immutable records with snapshot fields, certificate numbers, expiry metadata, verification hashes, and PDF storage references. Rendering is a service with a queue-ready interface.

### Reporting Architecture

Reporting reads from dedicated normalized tables and optional aggregate tables. MVP reports are generated live with indexed queries. V1 introduces export jobs. V2 introduces analytics snapshots and trend aggregation. Reporting never depends on scanning postmeta.

### Notification Architecture

Notifications use a channel-based model: in-app first, email next, WhatsApp later. Notification events are created by domain services and delivered by channel adapters. Templates are centralized in settings.

### AI Architecture

AI is exposed through provider adapters and task services. Each request is metered, logged, and associated with a user and organization. Assessment grading remains advisory and requires instructor approval.

## Phase 3: Database Design

See [database.md](database.md) for the full ERD, table design, indexes, foreign keys, and validation strategy.

## Phase 4: UI/UX System

See [design-system.md](design-system.md) for the complete product interface system.

## Phase 5: API Design

See [api.md](api.md) for endpoint documentation and future public API strategy.

## Phase 6: Development Roadmap

See [roadmap.md](roadmap.md) for MVP, V1, V2, and V3 scope.

## Phase 7: Code Implementation Strategy

The implementation begins with a production-grade foundation:

- PSR-4 PHP module structure.
- Dedicated database schema.
- Role and capability installer.
- Policy-aware REST routing.
- React app shell with design tokens.
- Extensible module registration.
- Hook and filter documentation.

Feature development should proceed milestone by milestone. The first code layer intentionally establishes boundaries that will not need to be rebuilt when certificates, payments, reporting, and AI grow.
