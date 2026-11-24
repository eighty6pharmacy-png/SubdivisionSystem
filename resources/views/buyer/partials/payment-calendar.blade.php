<!-- Buyer Payment Due Calendar -->
<div class="analytic-card" style="background: #fff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.04);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h3 style="font-size: 17px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" fill="none" stroke="#4f46e5" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Payment Schedule Calendar
            </h3>
            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Track your monthly installment due dates, upcoming schedule, and completed payments.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 9999px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span> Due Now
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; background: #e0e7ff; color: #3730a3; padding: 4px 10px; border-radius: 9999px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #4f46e5;"></span> Scheduled
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 9999px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #ef4444;"></span> Overdue
            </span>
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; background: #dcfce7; color: #065f46; padding: 4px 10px; border-radius: 9999px;">
                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> Paid
            </span>
        </div>
    </div>
    <div id="buyerPaymentCalendar" style="min-height: 400px;"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const calEl = document.getElementById('buyerPaymentCalendar');
        if (calEl && typeof FullCalendar !== 'undefined') {
            const buyerCal = new FullCalendar.Calendar(calEl, {
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,listMonth'
                },
                events: @json($calendarEvents ?? []),
                eventClick: function(info) {
                    if (info.event.url) {
                        info.jsEvent.preventDefault();
                        window.location.href = info.event.url;
                    }
                }
            });
            buyerCal.render();
        }
    });
</script>
