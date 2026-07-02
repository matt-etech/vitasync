# VitaSync Care Management Platform Roadmap

## Phase 2 Development Tasks

### 1. Controlled Drugs Register Module

**Objective**

Develop a Controlled Drugs Register that complies with healthcare regulations for recording and monitoring controlled medication.

**Development Tasks**

- [x] Create Controlled Drugs Register module.
- [x] Record every controlled drug received.
- [x] Record stock balances.
- [x] Record administration events.
- [x] Record wastage and disposal.
- [x] Record returns to pharmacy.
- [x] Require two-person witness where applicable.
- [x] Record reason for administration.
- [x] Maintain complete audit trail.
- [x] Prevent editing after submission.
- [x] Generate stock discrepancy reports.
- [x] Display current stock balance.

**Expected Outcome**

The system should provide a legally compliant register showing the complete lifecycle of every controlled drug with full accountability and audit history.

### 2. Missed & Late Visit Alert System

**Objective**

Automatically detect visits that are late or missed.

**Development Tasks**

- [ ] Monitor scheduled visit times.
- [ ] Detect carers who have not checked in.
- [ ] Detect carers arriving after configured threshold.
- [ ] Generate alerts.
- [ ] Notify coordinators.
- [ ] Notify managers.
- [ ] Escalate unresolved missed visits.
- [ ] Record reason for missed visit.
- [ ] Record corrective action taken.
- [ ] Display alert dashboard.

**Expected Outcome**

Managers receive immediate alerts whenever care visits are missed or significantly delayed, allowing rapid intervention.

### 3. Mental Capacity Act (MCA) Module

**Objective**

Support Mental Capacity Act documentation and decision making.

**Development Tasks**

- [ ] Create Mental Capacity Assessment form.
- [ ] Record assessment date.
- [ ] Record assessor.
- [ ] Record outcome.
- [ ] Record capacity decision.
- [ ] Record Best Interest decisions.
- [ ] Store supporting documents.
- [ ] Link MCA records to service user.
- [ ] Record review dates.
- [ ] Notify when review becomes due.

**Expected Outcome**

Every service user requiring an MCA assessment has legally compliant documentation stored and managed within the platform.

### 4. Safeguarding Case Management

**Objective**

Provide safeguarding incident recording and investigation workflow.

**Development Tasks**

- [ ] Create safeguarding case module.
- [ ] Record incident.
- [ ] Record concern type.
- [ ] Record risk level.
- [ ] Assign investigator.
- [ ] Track investigation progress.
- [ ] Record actions taken.
- [ ] Upload evidence.
- [ ] Record external authority involvement.
- [ ] Record outcome.
- [ ] Record closure date.
- [ ] Maintain audit trail.

**Expected Outcome**

The platform should fully manage safeguarding concerns from initial report through investigation and closure.

### 5. Staff Training & Compliance Module

**Objective**

Track mandatory staff training.

**Development Tasks**

- [ ] Create training catalogue.
- [ ] Assign mandatory courses.
- [ ] Record completion.
- [ ] Record expiry date.
- [ ] Upload certificates.
- [ ] Track overdue training.
- [ ] Notify staff before expiry.
- [ ] Notify managers.
- [ ] Prevent assignment to visits if mandatory training has expired (configurable).

**Expected Outcome**

Managers always know which carers are compliant and which require refresher training.

### 6. Complaint Management Module

**Objective**

Provide complete complaint handling process.

**Development Tasks**

- [ ] Log complaint.
- [ ] Record complainant.
- [ ] Record complaint category.
- [ ] Assign investigator.
- [ ] Record investigation notes.
- [ ] Record actions.
- [ ] Record outcome.
- [ ] Record response dates.
- [ ] Track complaint status.
- [ ] Generate complaint reports.
- [ ] Maintain audit history.

**Expected Outcome**

Every complaint can be tracked from submission to final resolution.

### 7. Role & Permission Security Review

**Objective**

Strengthen access control across the platform.

**Development Tasks**

- [ ] Review all permissions.
- [ ] Review all user roles.
- [ ] Remove unnecessary permissions.
- [ ] Restrict access to medical records.
- [ ] Restrict finance access.
- [ ] Restrict HR access.
- [ ] Restrict safeguarding access.
- [ ] Restrict medication access.
- [ ] Test every permission.
- [ ] Document permission matrix.

**Expected Outcome**

Users can only access information necessary for their role, ensuring confidentiality and regulatory compliance.

### 8. Permission Testing

**Objective**

Verify all permissions function correctly.

**Development Tasks**

- [ ] Create permission test cases.
- [ ] Test every module.
- [ ] Test role inheritance.
- [ ] Test denied access.
- [ ] Test hidden menus.
- [ ] Test APIs.
- [ ] Test audit logs.

**Expected Outcome**

No user can access data outside their authorised role.

### 9. Compliance Dashboard

**Objective**

Provide management visibility of compliance.

**Development Tasks**

Dashboard should display:

- [ ] Missed visits.
- [ ] Late visits.
- [ ] Open safeguarding cases.
- [ ] Complaints.
- [ ] Staff training compliance.
- [ ] Medication errors.
- [ ] MCA reviews due.
- [ ] Care plan reviews due.
- [ ] Controlled drug discrepancies.

**Expected Outcome**

Managers immediately see compliance issues requiring attention.

### 10. Notification Engine

**Objective**

Centralise system alerts.

**Development Tasks**

Support notifications for:

- [ ] Missed visits.
- [ ] Late visits.
- [ ] Care plan review due.
- [ ] Training expiry.
- [ ] Medication overdue.
- [ ] Controlled drug discrepancy.
- [ ] Complaint deadlines.
- [ ] MCA review due.
- [ ] Safeguarding updates.

Support:

- [ ] In-app notifications.
- [ ] Email.
- [ ] SMS (future integration).
- [ ] Push notifications (future).

**Expected Outcome**

Critical events are communicated automatically without manual monitoring.

### 11. Reporting Module Enhancements

**Objective**

Expand regulatory reporting.

**Development Tasks**

Create reports for:

- [ ] Missed visits.
- [ ] Late visits.
- [ ] Medication compliance.
- [ ] Controlled drugs.
- [ ] Staff compliance.
- [ ] Complaints.
- [ ] Safeguarding.
- [ ] MCA assessments.
- [ ] Care plan reviews.
- [ ] Audit logs.

Allow:

- [ ] PDF export.
- [ ] Excel export.
- [ ] Date filtering.
- [ ] Home filtering.
- [ ] Staff filtering.

**Expected Outcome**

Managers can generate compliance reports suitable for regulators and internal audits.

### 12. Audit Trail Improvements

**Objective**

Ensure all critical activities are logged.

**Development Tasks**

Log:

- [ ] User login.
- [ ] Logout.
- [ ] Record creation.
- [ ] Record editing.
- [ ] Record deletion.
- [ ] Medication changes.
- [ ] Permission changes.
- [ ] Care plan approvals.
- [ ] Complaint updates.
- [ ] Safeguarding updates.
- [ ] Finance changes.
- [ ] Training updates.

**Expected Outcome**

Every significant action performed in the system is traceable for accountability and compliance.

## Acceptance Criteria

Before this phase is considered complete, the platform should:

- [x] Support controlled drug management.
- [ ] Automatically detect missed and late visits.
- [ ] Record Mental Capacity Act assessments.
- [ ] Manage safeguarding investigations.
- [ ] Track mandatory staff training and certification.
- [ ] Manage complaints from submission to resolution.
- [ ] Enforce secure role-based access across all modules.
- [ ] Provide a compliance dashboard for managers.
- [ ] Send automated compliance notifications.
- [ ] Generate regulatory reports.
- [ ] Maintain comprehensive audit trails across all critical operations.

Delivering these features will move the VitaSync Care Management Platform much closer to full alignment with the regulatory, clinical safety, and governance requirements referenced in the review.
