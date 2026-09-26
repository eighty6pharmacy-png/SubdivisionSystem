# Complete System Functionality Test Cases & Improvement Suggestions

This document outlines the **Black Box Functionality Test Cases (UAT)** for the Subdivision System. 

Empty columns (`Result` and `Remarks`) have been added to the right side of the tables so that actual users (QA, Admins, Guards, Residents, Finance Officers) can print this out and write their answers while testing.

---

## 1. Admin Portal

| Test ID | Test Name | Scenario / Steps | Expected Result | Result (Pass/Fail) | Tester Notes / Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **— Authentication & Access Management** | | | | | |
| **ADM-01** | Test if Admin can log in with correct credentials | Enter valid Admin email and password. | Redirects successfully to the Admin Dashboard. | | |
| **ADM-02** | Test if Admin is rejected with incorrect credentials | Enter an incorrect password or unregistered Admin email. | Shows "Username or password is invalid!!" error message. | | |
| **ADM-03** | Test if Admin can request a password reset OTP | Submit registered Admin email on Forgot Password page. | System successfully emails a 6-digit OTP to the admin. | | |
| **ADM-04** | Test if Admin can verify OTP and reset password | Enter the correct 6-digit OTP and set a new password. | Password is updated successfully, Admin can now log in with the new password. | | |
| **— User Management** | | | | | |
| **ADM-05** | Test if Admin can filter the users list by name, block/lot, or role | On the Users list, type a resident's name, Block/Lot, or a Role in the Search/Filter box. | The list dynamically updates to accurately display only the users that match the inputted name, location, or role criteria. | | |
| **ADM-06** | Test if Admin can create a new user with a specific role | Go to Users -> Add New User. Fill valid details and select a role (e.g., Guard or Finance). | User is created successfully with the correct permissions and receives a welcome email. | | |
| **ADM-07** | Test if Admin can edit an existing user's details | Edit an existing user's contact number and role. | Details are updated and saved correctly in the database. | | |
| **ADM-08** | Test if Admin can safely archive a resident user | Click the "Archive" button next to a resident user. | The user is hidden from the active users list, but their past billing and payment records remain safely saved in the system. | | |
| **— Utility Billing Management** | | | | | |
| **ADM-09** | Test if Admin can generate monthly electricity bills | Go to Elec Billing -> Generate monthly bills. | Creates unpaid electrical bills for all residents. Skips provider-managed lots. | | |
| **ADM-10** | Test if Admin can disconnect a lot's electricity | Disconnect a resident's electricity via the dashboard. | Lot is flagged as `provider_managed`, and future bills are skipped. | | |
| **ADM-11** | Test if Admin can generate monthly water bills with penalties | Go to Water Billing -> Generate monthly bills. | Creates unpaid water bills. Automatically adds 5% penalty to previous overdue balances. | | |
| **ADM-12** | Test if Admin can export the Billing SOA to Excel | Click the "Export Excel" button on the Billing page. | The Excel file opens successfully and clearly displays columns for Resident Name, Lot Number, Amount Due, and Payment Status. | | |
| **— Real Estate & Sales Management** | | | | | |
| **ADM-13** | Test if Admin can add a new lot reservation fee | Go to Reservation Fee -> Add new reservation. | Records reservation, sets amount, changes lot status to "Reserved". | | |
| **ADM-14** | Test if Admin can create a downpayment contract | Add a Downpayment contract for a reserved lot. | Sets monthly amortization amount, duration, and first due date. | | |
| **ADM-15** | Test if Admin can record a monthly downpayment | Record a monthly downpayment cash transaction. | Balance decreases, next due date is extended by 1 month, history is logged. | | |
| **— Visitor & Appointment Management** | | | | | |
| **ADM-16** | Test if Admin can approve a site viewing appointment | View a pending site-viewing appointment. Mark as "Scheduled". | Sends an "Approved" email to the client containing their 6-digit gate PIN. | | |
| **ADM-17** | Test if Admin can cancel a site viewing appointment | Cancel an appointment and provide a reason. | Sends a "Cancellation Notice" email with the exact reason provided. | | |
| **— Incident & Content Management** | | | | | |
| **ADM-18** | Test if Admin can update a resident incident status | View Resident Incident -> Change status to "Progress". | Status updates immediately, reflecting on the Resident's personal dashboard. | | |
| **ADM-19** | Test if Admin can post an announcement and send emails | Post new announcement with title and content. | Announcement goes live, and an email blast is sent to all registered residents. | | |
| **— GIS & System Settings** | | | | | |
| **ADM-20** | Test if Admin can mark an empty lot as occupied via GIS map | Click on an empty lot on the map -> Change to "Occupied". | Lot status updates visually on the map. | | |
| **ADM-21** | Test if system prevents Admin from changing an occupied lot | Attempt to change an already resident-occupied lot. | System prevents change and shows "Lot is occupied by a resident" error. | | |
| **ADM-22** | Test if Admin can update global utility rates in settings | Change global Water Rate, Min Water m³, and Min Water Rate. | Next generated bills use the new calculation matrix. | | |

---

## 2. Finance Officer Portal

| Test ID | Test Name | Scenario / Steps | Expected Result | Result (Pass/Fail) | Tester Notes / Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **— Authentication & Access Management** | | | | | |
| **FIN-01** | Test if Finance Officer can log in with correct credentials | Enter valid Finance email and password. | Redirects successfully to the Finance Dashboard. | | |
| **FIN-02** | Test if Finance Officer is rejected with incorrect credentials | Enter an incorrect password or unregistered Finance email. | Shows "Username or password is invalid!!" error message. | | |
| **FIN-03** | Test if Finance Officer can request a password reset OTP | Submit registered Finance email on Forgot Password page. | System successfully emails a 6-digit OTP to the Finance officer. | | |
| **FIN-04** | Test if Finance Officer can verify OTP and reset password | Enter the correct 6-digit OTP and set a new password. | Password is updated successfully, Finance officer can now log in. | | |
| **— Dashboard & Monitoring** | | | | | |
| **FIN-05** | Test if Finance dashboard displays master list of all houses | View the main finance dashboard. | Displays a master list of all houses and indicates Billed, Paid, or Pending statuses. | | |
| **FIN-06** | Test if Finance Officer can filter the dashboard by block/lot | Use the Search box to find a specific Block and Lot number. | The table filters instantly to show only that specific house's billing status. | | |
| **— Meter Reading & Billing** | | | | | |
| **FIN-07** | Test if Finance Officer can input a valid meter reading to generate bill | Input previous & current water meter reading for a house. | System calculates m³ usage and auto-generates the bill amount based on rates. | | |
| **FIN-08** | Test if zero meter reading defaults to the minimum water rate | Enter `0` for current and previous water reading. | System detects `0` usage but charges the standard "Minimum Water Rate". | | |
| **— Payment & Receivables** | | | | | |
| **FIN-09** | Test if Finance Officer can send an overdue SMS warning | Click "Send Warning SMS" for an overdue resident. | System triggers Textbee API; resident receives an SMS warning on their phone. | | |
| **FIN-10** | Test if Finance Officer can record an offline cash payment | Record an "Office Cash" payment for a resident's bill. | Bill status updates to `paid` if fully paid, remaining balance recalculates. | | |
| **— Financial Reporting** | | | | | |
| **FIN-11** | Test if Finance Officer can export the financial SOA to Excel | Click "Export Excel" for the current billing cycle. | The Excel file clearly lists every homeowner's name, their outstanding balance, and if they have paid. | | |

---

## 3. Resident Portal

| Test ID | Test Name | Scenario / Steps | Expected Result | Result (Pass/Fail) | Tester Notes / Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **— Authentication & Access Management** | | | | | |
| **RES-01** | Test if Resident can log in with correct credentials | Enter valid Resident email and password. | Redirects successfully to the Resident Dashboard. | | |
| **RES-02** | Test if Resident is rejected with incorrect credentials | Enter an incorrect password or unregistered Resident email. | Shows "Username or password is invalid!!" error message. | | |
| **RES-03** | Test if Resident can request a password reset OTP | Submit registered Resident email on Forgot Password page. | System successfully emails a 6-digit OTP to the resident. | | |
| **RES-04** | Test if Resident can verify OTP and reset password | Enter the correct 6-digit OTP and set a new password. | Password is updated successfully, resident can now log in. | | |
| **— Dashboard & Profile** | | | | | |
| **RES-05** | Test if Resident dashboard displays upcoming bills and announcements | Log in and view the resident dashboard. | Displays upcoming bills, current balances, and latest announcements. | | |
| **RES-06** | Test if Resident can update their profile contact number | Go to Profile -> Edit Contact Number. | Saves successfully. SMS notifications will now route to this new number. | | |
| **— Utility Billing & Payments** | | | | | |
| **RES-07** | Test if Resident can view their current electricity bill and graph | Go to Electricity -> View Current Bill. | Shows accurate kWh usage, historical graph (12 months), and exact amount due. | | |
| **RES-08** | Test if Resident can view their current water bill and graph | Go to Water -> View Current Bill. | Shows accurate m³ usage, historical graph, and minimum rate application if any. | | |
| **RES-09** | Test if Resident can initiate an online payment via PayMongo | Click "Pay Now" on any unpaid bill. | Redirects securely to PayMongo checkout. | | |
| **RES-10** | Test if completed PayMongo payment automatically updates bill to paid | Complete payment using GCash/Card on PayMongo. | Returns to portal. Bill is instantly marked as `paid` via background webhook. | | |
| **— Visitor Management & Mapping** | | | | | |
| **RES-11** | Test if Resident can view the subdivision GIS map | View the Subdivision Map. | Shows visual layout of the subdivision and lot assignments. | | |
| **RES-12** | Test if Resident can request a visitor pass and send PIN to guest | Request a visitor pass for a specific date/guest. | Sends an email to the guest with a 6-digit Gate PIN for the Guard. | | |
| **— Incident Reporting** | | | | | |
| **RES-13** | Test if Resident can submit a new incident report with a photo | Report an incident (e.g. noise complaint) with a photo attachment. | Report submits successfully, appears in the "Pending" list. | | |
| **RES-14** | Test if Resident can view the status history of past incidents | View past incidents. | Shows status updates (Pending -> Progress -> Resolved) made by the Admin. | | |

---

## 4. Security Guard Portal

| Test ID | Test Name | Scenario / Steps | Expected Result | Result (Pass/Fail) | Tester Notes / Remarks |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **— Authentication & Access Management** | | | | | |
| **GRD-01** | Test if Guard can log in with correct credentials | Enter valid Guard email and password. | Redirects successfully to the Guard Dashboard. | | |
| **GRD-02** | Test if Guard is rejected with incorrect credentials | Enter an incorrect password or unregistered Guard email. | Shows "Username or password is invalid!!" error message. | | |
| **GRD-03** | Test if Guard can request a password reset OTP | Submit registered Guard email on Forgot Password page. | System successfully emails a 6-digit OTP to the guard. | | |
| **GRD-04** | Test if Guard can verify OTP and reset password | Enter the correct 6-digit OTP and set a new password. | Password is updated successfully, guard can now log in. | | |
| **— Gate Control & Validation** | | | | | |
| **GRD-05** | Test if Guard dashboard displays PIN scanner and logging tools | Log in and view Guard Dashboard. | Access the PIN scanner and manual visitor logging tools. | | |
| **GRD-06** | Test if Guard can validate a correct visitor PIN | Enter a valid 6-digit PIN presented by a guest/appointment. | System retrieves Guest Name and Resident Host; marks status as "Approved". | | |
| **GRD-07** | Test if system rejects an incorrect visitor PIN | Enter an invalid or fabricated PIN. | System rejects PIN and flags entry as invalid. | | |
| **GRD-08** | Test if Guard can manually log a walk-in visitor without a PIN | Manually log a walk-in visitor (no PIN) who arrived at the gate. | Submits visitor details to the Resident for approval. | | |
| **— Log Monitoring & Auditing** | | | | | |
| **GRD-09** | Test if Guard can mark an approved visitor as Entered | Mark an approved visitor as "Entered". | Timestamp is recorded for entry. | | |
| **GRD-10** | Test if Guard can mark an Entered visitor as Exited | Mark a previously "Entered" visitor as "Exited". | Exit timestamp is recorded, closing the visitor's log. | | |
| **GRD-11** | Test if Guard can view the full visitor history log | Go to History Log. | Displays chronological timeline of all entries, exits, and rejections. | | |
| **GRD-12** | Test if Guard can filter the visitor history log by name or date | Type a date or visitor name in the History search box. | List filters instantly to show only logs matching the typed search term. | | |

---

## 💡 System Analysis & Suggestions for Improvement

Based on scanning the system's architecture, here are technical and functional suggestions to improve the system:

### 1. Finance & Billing Integrity
*   **Partial Payment Edge Cases:** Currently, the system supports recording manual payments and calculating balances. Ensure that the logic for partial payments via **PayMongo** properly flags bills as `partially paid` rather than strictly `paid` or `unpaid`.
*   **Zero Usage Feedback:** For water bills, if usage is 0, the system currently charges the minimum rate automatically. Consider adding a warning prompt for the Finance Officer when a 0 usage is entered to confirm if the meter is broken or if the house is simply vacant.

### 2. Guard & Security Enhancements
*   **PIN Expiration:** Visitor PINs generated for appointments currently do not have a strict chronological expiration tied to them in the validation logic (they just check against the database). Implement a check that invalidates PINs if the appointment date has passed.
*   **Exit Logging Enforcement:** Ensure the Guard dashboard clearly highlights visitors who have `Entered` but haven't `Exited` by the end of the day to prevent unauthorized overnight stays.

### 3. Admin & General Operations
*   **Soft Deletes for Users (Implemented as Archiving):** When an Admin removes a user, they should be "Archived" rather than hard-deleted. This is crucial to preserve financial records, incident logs, and payment histories associated with that user even after they move out.
*   **Role Separation:** The Admin portal currently handles real estate sales (Reservation/Downpayment). As the subdivision grows, consider separating this into a **Sales/Broker Role** so the main Admin doesn't get cluttered with accounting tasks.

### 4. System Notifications & SMS
*   **SMS Rate Limiting:** The system currently sends a billing warning SMS via Textbee API. Ensure there is a rate limit or cooldown on this function so Admins/Finance cannot accidentally spam a resident's phone by clicking the button multiple times.
*   **Background Queues:** Currently, sending Announcement emails to *all* residents happens synchronously during the web request. If you have 500 residents, the Admin's screen will freeze while it sends 500 emails. Implement **Laravel Queues** (`ShouldQueue`) so emails are sent in the background instantly.
