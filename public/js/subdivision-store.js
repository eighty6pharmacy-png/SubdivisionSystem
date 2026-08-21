/**
 * SubdivisionStore - Unified Client-Side Storage Manager
 * Enables full interactive testing of UI/UX flows across Client, Resident, Admin, and Guard views
 * without requiring backend database infrastructure.
 */
(function(window) {
    const KEYS = {
        APPOINTMENTS: 'subdivision_appointments',
        INCIDENTS: 'subdivision_incidents',
        VISITORS: 'subdivision_visitors'
    };

    // Initial default seed items if storage is empty
    const DEFAULT_APPOINTMENTS = [
        { id: 'APT-1001', client: 'Sarah Jenkins', contact: '+63 917 111 2222', email: 'sarah@example.com', date: '2026-05-15', time: '10:00 AM', status: 'Pending', type: 'Site Visit / Lot Viewing', notes: 'Interested in corner lot near clubhouse.', report: '' },
        { id: 'APT-1002', client: 'Robert Tan', contact: '+63 918 333 4444', email: 'robert@example.com', date: '2026-05-18', time: '02:00 PM', status: 'Scheduled', type: 'Architectural Consultation', notes: 'Second viewing with property architect.', report: '' },
        { id: 'APT-1003', client: 'Maria Santos', contact: '+63 920 555 6666', email: 'maria@example.com', date: '2026-05-02', time: '11:00 AM', status: 'Completed', type: 'Price Negotiation', notes: 'Finalizing downpayment terms.', report: 'Client expressed satisfaction. Proceeding to reservation fee step.' }
    ];

    const DEFAULT_INCIDENTS = [
        { id: 'INC-1001', sub: 'Streetlight Issue - Gate 2', type: 'Maintenance', res: 'Anonymous', desc: 'Flickering street light at entrance since last night. Safety hazard.', status: 'pending', contact: '+63 917 999 1111', date: '2026-04-01 09:30 AM' },
        { id: 'INC-1002', sub: 'Water Leak Near Clubhouse', type: 'Utilities', res: 'Block A, Lot 5', desc: 'Large puddle forming on the sidewalk near the pool entrance.', status: 'progress', contact: '+63 918 222 3333', date: '2026-04-02 11:15 AM' }
    ];

    const DEFAULT_VISITORS = [
        { id: 'VIS-3001', pin: '482910', visitor: 'Alex Mendez', host: 'Juan Dela Cruz', block: '1', lot: '5', purpose: 'Plumbing Repair', validity: 'Today', status: 'Approved' },
        { id: 'VIS-3002', pin: '194820', visitor: 'Grab Food Delivery', host: 'Maria Santos', block: '2', lot: '12', purpose: 'Food Delivery', validity: 'Today', status: 'Pending' }
    ];

    function getItem(key, defaultVal) {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) {
                localStorage.setItem(key, JSON.stringify(defaultVal));
                return defaultVal;
            }
            return JSON.parse(raw);
        } catch (e) {
            console.error('SubdivisionStore Error:', e);
            return defaultVal;
        }
    }

    function setItem(key, val) {
        try {
            localStorage.setItem(key, JSON.stringify(val));
        } catch (e) {
            console.error('SubdivisionStore Write Error:', e);
        }
    }

    window.SubdivisionStore = {
        // --- APPOINTMENTS ---
        getAppointments: function() {
            return getItem(KEYS.APPOINTMENTS, DEFAULT_APPOINTMENTS);
        },
        addAppointment: function(apt) {
            const list = this.getAppointments();
            list.unshift(apt);
            setItem(KEYS.APPOINTMENTS, list);
            return apt;
        },
        updateAppointmentStatus: function(id, status, extraProps = {}) {
            const list = this.getAppointments();
            const index = list.findIndex(a => a.id === id);
            if (index !== -1) {
                list[index].status = status;
                Object.assign(list[index], extraProps);
                setItem(KEYS.APPOINTMENTS, list);
            }
            return list;
        },

        // --- INCIDENTS ---
        getIncidents: function() {
            return getItem(KEYS.INCIDENTS, DEFAULT_INCIDENTS);
        },
        addIncident: function(inc) {
            const list = this.getIncidents();
            list.unshift(inc);
            setItem(KEYS.INCIDENTS, list);
            return inc;
        },
        updateIncidentStatus: function(id, status) {
            const list = this.getIncidents();
            const index = list.findIndex(i => i.id === id);
            if (index !== -1) {
                list[index].status = status;
                setItem(KEYS.INCIDENTS, list);
            }
            return list;
        },

        // --- VISITORS ---
        getVisitors: function() {
            return getItem(KEYS.VISITORS, DEFAULT_VISITORS);
        },
        addVisitor: function(vis) {
            const list = this.getVisitors();
            list.unshift(vis);
            setItem(KEYS.VISITORS, list);
            return vis;
        },
        updateVisitorStatus: function(id, status, pin = null) {
            const list = this.getVisitors();
            const index = list.findIndex(v => v.id === id);
            if (index !== -1) {
                list[index].status = status;
                if (pin) list[index].pin = pin;
                setItem(KEYS.VISITORS, list);
            }
            return list;
        }
    };
})(window);
