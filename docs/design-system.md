# Zadora LMS UI/UX System

## Design Language

Zadora should feel calm, premium, and operational. The interface should use restrained color, clear typography, crisp spacing, and fast feedback. It should help non-technical users understand what to do next without explaining the software to them.

## Layout Structure

- App shell: persistent left sidebar on desktop, collapsible drawer on mobile.
- Top bar: page title, search, notifications, account menu, and primary action.
- Content region: max-width controlled by view type; dashboards are fluid, forms are narrower.
- Drawers: used for create/edit flows that do not require full-page focus.
- Full pages: used for course builder, certificate builder, reports, and settings.

## Navigation

Navigation is role-aware:

- Learner: Dashboard, My Courses, Certificates, Groups, Notifications.
- Training Provider: Dashboard, Courses, Learners, Assessments, Certificates, Reports, Payments.
- Corporate Admin: Dashboard, Employees, Departments, Seats, Assignments, Reports, Certificates.
- Super Admin: Dashboard, Companies, Courses, Users, Payments, AI, Settings.

## Components

- Cards: 8px radius, subtle border, no heavy shadows, clear heading and metric hierarchy.
- Tables: dense but readable, sticky headers for large reports, bulk actions, filters, export controls.
- Forms: grouped sections, inline validation, progressive disclosure, save bars for complex edits.
- Buttons: primary for one main action, secondary for neutral actions, ghost for low-emphasis actions, destructive for irreversible actions.
- Modals: confirmation and focused decisions only.
- Drawers: object creation, quick edits, notification details, and filter panels.
- Toasts: lightweight success/failure feedback.
- Skeletons: dashboards, tables, and cards load with skeleton states.
- Dashboard widgets: metric, list, chart, action, progress, and alert variants.

## Design Tokens

- Radius: `8px` for cards and controls, `999px` only for pills and avatars.
- Spacing: 4px base scale.
- Typography: system font stack, strong contrast, no negative letter spacing.
- Color: neutral-first with a confident blue primary, green success, amber warning, red danger, and violet accent used sparingly.
- Motion: 120-180ms for hover and panel transitions; avoid theatrical animation.

## Accessibility

- Keyboard navigable navigation, drawers, forms, and modals.
- Visible focus states.
- WCAG AA contrast targets.
- Labels attached to controls.
- Status changes announced for async actions where appropriate.

## Core Screens

- Role dashboard with widgets and recent activity.
- Course catalog with filters, status badges, and waitlist/private states.
- Course builder with lessons, assessment settings, pricing, and certificate selection.
- Learner course player with progress persistence.
- Certificate wallet with verify/share/download actions.
- Corporate compliance dashboard with departments and exportable reports.
- Settings with clear sections for branding, login, certificates, payments, AI, and notifications.
