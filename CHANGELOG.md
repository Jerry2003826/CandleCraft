# Changelog

## 2026-04-15 - UI Modernization & Workflow Alignment

### Summary

- Completed a major UI/UX modernization across the CandleCraft Academy platform.
- Aligned key customer, admin, and teacher workflows with the latest product flow diagram.
- Refreshed local schema, seed data, and supporting business logic so the new experience is backed by working behavior rather than visual-only changes.

### 1. Core Theme System & Visual Foundation

- Rebuilt the shared styling foundation around a centralized CSS variable system in `redesign.css`.
- Standardized the `--admin-*` design tokens used across admin and portal pages.
- Added and refined dark mode support across cards, tables, forms, calendars, navigation, and action components.
- Fixed multiple light/dark contrast issues where text, surfaces, or actions were hard to read.

### 2. Global Layout & Navigation Refresh

- Reworked the shared admin and portal layouts for a more modern application shell.
- Rebuilt the sidebar and top navigation structure with stronger visual hierarchy and icon-based navigation.
- Kept logout fixed in the bottom navigation area.
- Improved sidebar scrolling behavior so navigation does not jump back to the top when switching pages.
- Updated visible navigation wording to better match product expectations:
  - `Students` -> `Customers`
  - `Messages` -> `Enquiries`
  - `Consumer Portal` -> `Customer Portal`
  - Teacher portal labels aligned to `Manage Availability`, `Manage Attendance`, `View Schedule`, and `Manage Learning Resources`

### 3. Dashboard Redesign

- Rebuilt the admin dashboard with modern stat cards, clearer summary sections, and a stronger visual grid.
- Added a high-visibility dashboard section for pending customer account requests so admins no longer need to discover them only through the enquiries list.
- Reworked the customer dashboard into a true portal landing page with:
  - account status
  - verification state
  - quick links to schedule, learning resources, booking system, and payment portal
- Improved teacher and customer dashboard card layout and alignment.
- Fixed the account request summary card color treatment so it works correctly in light and dark themes.

### 4. Lists, Tables, and Action Patterns

- Standardized list and table pages into a unified card-and-table style.
- Applied consistent badges, icon actions, spacing, and status treatments across admin views.
- Removed unnecessary `view` actions from selected teacher/student/class management lists where only edit/delete behavior was needed.
- Added backend search support to key management lists.

### 5. Forms, Detail Views, and Content Presentation

- Refreshed add/edit forms into a consistent card-based layout.
- Reworked student detail and profile presentation, including clearer age verification handling.
- Rebuilt message/enquiry detail pages into a thread-like presentation instead of a plain record view.
- Improved attendance detail presentation with clearer status styling and better readability.
- Upgraded the login page to a modern split layout with stronger branding and clearer user guidance.

### 6. Calendar, Schedule, and Availability Views

- Reworked calendar and schedule pages across admin and portal flows.
- Fixed layout compression, overflow, hidden text, and theme mismatches in weekly calendar views.
- Updated the admin class availability calendar to use a left-side time axis.
- Improved teacher schedule and customer schedule presentation.
- Extended customer schedule pages to surface:
  - booking state
  - attendance state
  - reminder state

### 7. Enquiry Form & Account Request Workflow

- Consolidated account request behavior into the main public enquiry form instead of using a separate standalone request page.
- Added a checkbox-based flow so a user can submit a normal enquiry or request a portal account from the same page.
- Standardized account requests to default to student accounts.
- Required age entry when submitting an account request.
- Stored account request metadata directly in the enquiry/message flow for admin review.

### 8. Student Account Model & Age Verification Rules

- Removed the old parent-account dependency from the active flow.
- Shifted the platform to a student-centric portal model with no parent/student ownership chain in the new workflow.
- Preserved the rule that students must be manually verified as 18+ by an admin before booking and payment unlock.
- Updated customer portal gating so:
  - browsing courses remains available before verification
  - booking and payment remain locked until admin verification

### 9. Admin Enquiry Handling Improvements

- Added clearer admin-facing entry points for account-request enquiries from the dashboard.
- Extended admin enquiry handling so replies are now intended as real external email replies rather than internal-only notes.
- Added enquiry reply audit data support, including recipient and delivery tracking fields.
- Updated enquiry detail and reply screens to better reflect real-world reply workflow and delivery status.

### 10. Portal Credential Delivery

- Preserved and improved the flow where admins can create a customer account from an enquiry.
- Kept the ability to generate a login and send customer portal credentials by email.
- Improved admin visibility around whether the account exists, whether a student record is linked, and where to continue next.

### 11. Customer Portal & Payment Portal Alignment

- Reframed the customer experience around the product diagram:
  - `Booking System`
  - `View Schedule & Attendance`
  - `Learning Resources`
  - `Payment Portal`
- Replaced the old fake payment-method modal with a real billing-profile style payment details flow.
- Added support for storing non-sensitive payment details only:
  - billing/contact information
  - preferred payment method
  - default profile selection
  - archive/default management
- Explicitly avoided storing raw card numbers or CVC values.

### 12. Reminder Automation

- Added a reminder command for class reminders:
  - `bin/cake send_class_reminders`
- Implemented the 24-hour reminder window behavior.
- Added reminder de-duplication through `bookings.reminder_sent_at`.
- Added reminder emails and in-app notification creation for matching bookings.

### 13. Database & Seed Updates

- Updated local database structure to match the newer student/verification/payment workflow.
- Added migrations for:
  - declared age support
  - relaxed legacy booking parent dependency
  - enquiry reply audit fields
  - reminder tracking
  - payment profiles
- Refreshed seed data to better reflect the new platform model and prevent old project data from mixing with current demo data.
- Ensured seed import behavior fully clears old records before reseeding.

### 14. Stability, Compatibility, and Bug Fixes

- Fixed runtime issues that could previously throw users into CakePHP error pages.
- Added compatibility handling for older local schemas during transition.
- Fixed teacher attendance history behavior when a teacher had no assigned classes.
- Repaired several UI regressions, spacing issues, alignment issues, and visibility bugs reported during review.
