# DFD Level 0 - Subdivision Management System

This Data Flow Diagram (DFD) Level 0 (Context Diagram) represents the entire Subdivision Management System as a single process and illustrates its interactions with external entities.

```mermaid
graph LR
    subgraph SubdivisionManagementSystem ["Subdivision Management System"]
        SystemProcess((Subdivision System))
    end

    Resident[Resident]
    Admin[Administrator]
    Guard[Security Guard]
    Client[Prospective Buyer]
    PaymentGateway[Payment Gateway]
    UtilityProvider[External Utility Provider]

    Resident -- "Incident Reports, Visitor Details, Payments" --> SystemProcess
    SystemProcess -- "Bills, Announcements, Visitor PINs, Incident Status" --> Resident

    Admin -- "Resident Profiles, Meter Readings, Announcements, Settings" --> SystemProcess
    SystemProcess -- "Delinquency Reports, Incident Analytics, GIS Data" --> Admin

    Guard -- "PIN Verification Requests" --> SystemProcess
    SystemProcess -- "Visitor Entry Clearance" --> Guard

    Client -- "Appointment Requests" --> SystemProcess
    SystemProcess -- "Appointment Confirmations" --> Client

    SystemProcess -- "Payment Requests" --> PaymentGateway
    PaymentGateway -- "Transaction Confirmation" --> SystemProcess

    UtilityProvider -- "Utility Usage Data" --> SystemProcess
    SystemProcess -- "Managed Lot Updates" --> UtilityProvider
```

## Description of Flows

### Resident
- **Inputs**: Submits incident reports (maintenance, security, etc.), provides visitor information for entry PINs, and submits payment confirmations for utilities and dues.
- **Outputs**: Receives billing statements (Electricity, Water, HOA), community announcements, visitor PINs for guests, and updates on the status of reported incidents.

### Administrator
- **Inputs**: Updates resident profiles, enters manual meter readings, publishes community announcements, and manages system-wide configurations.
- **Outputs**: Receives delinquency reports (unpaid bills), data analytics for incidents, GIS monitoring data, and summaries of scheduled appointments.

### Security Guard
- **Inputs**: Requests verification for visitor PINs provided by guests at the gate.
- **Outputs**: Receives clearance or denial alerts for visitor entry.

### Prospective Buyer (Client)
- **Inputs**: Submits requests to view properties or book appointments with the sales team.
- **Outputs**: Receives confirmations and scheduling details for requested appointments.

### Payment Gateway (GCash/Office)
- **Inputs**: Receives payment transaction details from the system.
- **Outputs**: Returns confirmation of successful or failed transactions.

### External Utility Provider (CASURECO/Water District)
- **Inputs**: Provides usage data for lots not managed directly by the subdivision.
- **Outputs**: Receives operational data or updates regarding managed lot statuses.
