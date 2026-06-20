# Zadora LMS API Design

All MVP endpoints are under `/wp-json/zadora/v1`.

## Authentication

- Same-site dashboard API: WordPress auth cookie plus `X-WP-Nonce`.
- Public certificate verification: no authentication.
- Future public API: scoped API keys with rate limits and organization scopes.

## Endpoint Summary

### Courses

- `GET /courses`: list visible courses.
- `POST /courses`: create course.
- `GET /courses/{id}`: retrieve course.
- `PATCH /courses/{id}`: update course.
- `DELETE /courses/{id}`: archive course.
- `POST /courses/{id}/waitlist`: join waitlist for coming-soon course.

### Lessons

- `GET /courses/{course_id}/lessons`: list course lessons.
- `POST /courses/{course_id}/lessons`: create lesson.
- `PATCH /lessons/{id}`: update lesson.
- `POST /lessons/{id}/complete`: mark lesson complete.

### Enrollments

- `GET /enrollments`: list enrollments for current scope.
- `POST /enrollments`: enroll learner.
- `PATCH /enrollments/{id}`: update status.

### Certificates

- `GET /certificates`: list certificate wallet or admin certificates.
- `POST /certificates`: manually issue certificate.
- `GET /certificates/{id}`: retrieve certificate.
- `GET /certificates/{id}/download`: download PDF.
- `GET /verify-certificate/{hash}`: public verification.
- `POST /certificate-templates`: create template.
- `PATCH /certificate-templates/{id}`: update template.

### Companies

- `GET /companies`: list companies.
- `POST /companies`: create company.
- `GET /companies/{id}`: retrieve company.
- `PATCH /companies/{id}`: update company.
- `POST /companies/{id}/users`: add employee.
- `POST /companies/{id}/assignments`: assign course.

### Reports

- `GET /reports/learner`: learner progress.
- `GET /reports/course`: course enrollments and completion.
- `GET /reports/corporate`: company compliance.
- `GET /reports/certificates`: certificate issue and expiry.
- `POST /reports/export`: create export job.

### Payments

- `POST /orders`: create order.
- `POST /payments/stripe/webhook`: Stripe webhook.
- `POST /payments/paystack/webhook`: Paystack webhook.
- `GET /orders/{id}`: retrieve order.

### Notifications

- `GET /notifications`: list notifications.
- `PATCH /notifications/{id}/read`: mark as read.
- `PATCH /notifications/read-all`: mark all as read.

### AI

- `POST /ai/quiz`: generate quiz draft.
- `POST /ai/objectives`: generate learning objectives.
- `POST /ai/grading-suggestion`: generate grading suggestion.

## API Design Rules

- Controllers must be thin.
- Permission callbacks must call policy classes.
- All mutations use service commands.
- Responses use typed transformers.
- Errors use stable machine-readable codes.
- Webhooks must verify signatures and be idempotent.
- Public endpoints expose only intentionally public fields.
