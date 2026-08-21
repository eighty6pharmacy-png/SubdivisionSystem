const fs = require('fs');
const https = require('https');

const code = `
flowchart TD
    DB[(Central Database)]
    
    Admin([Admin / Board]) -.-> AdminPortal
    Resident([Homeowner]) -.-> ResPortal
    Guard([Security Guard]) -.-> GuardPortal
    
    subgraph AdminPortal [Admin Portal]
        direction TB
        A1[Billing / Water / Monthly Due]
        A2[Residents Accounts]
        A3[Incident Management]
        A4[Property GIS Monitoring]
        A6[Appointments]
        A7[Meter Readings]
    end
    
    subgraph ResPortal [Resident Portal]
        direction TB
        R1[View & Pay Utilities]
        R2[Generate Visitor PINs]
        R3[Report Incidents]
        R4[View Notifications]
    end
    
    subgraph GuardPortal [Guard Portal]
        G1[Verify Visitor PINs]
    end

    AdminPortal --> DB
    ResPortal --> DB
    GuardPortal --> DB
`;

const state = {
  code: code.trim(),
  mermaid: { theme: 'default' }
};

const base64 = Buffer.from(JSON.stringify(state)).toString('base64');
const url = "https://mermaid.ink/img/" + base64;

https.get(url, (res) => {
  if (res.statusCode !== 200) {
    console.log('Failed to fetch image: ' + res.statusCode);
    return;
  }
  const file = fs.createWriteStream('d:/AntigravitySetup/SubdivisionSystem/System_Architecture_Diagram.png');
  res.pipe(file);
  file.on('finish', () => {
    file.close();
    console.log("Image downloaded.");
  });
}).on('error', (e) => {
  console.error(e);
});
