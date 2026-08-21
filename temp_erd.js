const zlib = require('zlib');
const code = `
erDiagram
  USERS ||--o{ RESIDENTS : "has portal account"
  USERS ||--o{ PAYMENT_TRANSACTIONS : "records"
  USERS ||--o{ AUDIT_LOGS : "performs"
  USERS ||--o{ ANNOUNCEMENTS : "authors"
  USERS ||--o{ INCIDENT_COMMENTS : "posts"
  USERS ||--o{ APPOINTMENTS : "created by"

  RESIDENTS ||--o{ PROPERTIES : "occupies"
  RESIDENTS ||--o{ ELECTRICITY_BILLS : "has"
  RESIDENTS ||--o{ WATER_BILLS : "has"
  RESIDENTS ||--o{ MONTHLY_DUES : "pays"
  RESIDENTS ||--o{ VISITOR_PASSES : "generates"
  RESIDENTS ||--o{ INCIDENTS : "reports"

  ELECTRICITY_BILLS ||--o{ PAYMENT_TRANSACTIONS : "paid via"
  WATER_BILLS ||--o{ PAYMENT_TRANSACTIONS : "paid via"
  MONTHLY_DUES ||--o{ PAYMENT_TRANSACTIONS : "paid via"

  INCIDENTS ||--o{ INCIDENT_COMMENTS : "has"
  APPOINTMENTS ||--o| APPOINTMENT_REPORTS : "has"

  USERS { int id PK string name string email UK string password string role }
  RESIDENTS { string id PK int user_id FK string name string email string contact int block int lot date joined_date string status }
  PROPERTIES { int id PK int block int lot string resident_id FK decimal area_sqm }
  ELECTRICITY_BILLS { string id PK string resident_id FK string billing_month decimal usage_kwh decimal base_amount decimal total_amount boolean at_risk string status date due_date date paid_date }
  WATER_BILLS { string id PK string resident_id FK string billing_month decimal usage_m3 decimal base_amount decimal total_amount boolean at_risk string status date due_date date paid_date }
  MONTHLY_DUES { string id PK string resident_id FK string billing_month string assessment_type decimal base_amount decimal total_amount boolean at_risk string status date due_date date paid_date }
  PAYMENT_TRANSACTIONS { string id PK string billable_type string billable_id string resident_id FK decimal amount string payment_method string reference_number int recorded_by FK datetime payment_date }
  BILLING_SETTINGS { int id PK string type decimal rate_per_unit decimal penalty_rate int updated_by FK }
  AUDIT_LOGS { int id PK string loggable_type string loggable_id string action int performed_by FK }
  INCIDENTS { string id PK string resident_id FK string type string subject text description string priority string status }
  INCIDENT_COMMENTS { int id PK string incident_id FK int user_id FK text message boolean is_admin }
  ANNOUNCEMENTS { int id PK string title text content string category string status int author_id FK date scheduled_date }
  APPOINTMENTS { string id PK string client_name date appointment_date time appointment_time string status int created_by FK }
  APPOINTMENT_REPORTS { int id PK string appointment_id FK string assigned_agent int client_sentiment string next_action text analytical_notes }
  VISITOR_PASSES { string id PK string resident_id FK string visitor_name string purpose string pin_code string validity datetime expires_at string status }
`;
const state = { code, mermaid: '{"theme":"default"}', autoSync: true, updateDiagram: true };
const buffer = zlib.deflateSync(JSON.stringify(state));
const base64Str = buffer.toString('base64').replace(/\+/g, '-').replace(/\//g, '_');
console.log('https://mermaid.live/edit#pako:' + base64Str);
