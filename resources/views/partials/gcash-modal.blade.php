{{-- PayMongo-Style QR Ph Checkout Modal --}}
{{-- Include via: @include('partials.gcash-modal') --}}

<div id="gcashOverlay" class="pm-overlay" onclick="if(event.target===this) closeGcash()">
    <div class="pm-modal">
        <!-- Main Payment Flow -->
        <div id="pmMainFlow" class="pm-step">
            <div class="pm-header">
                <div class="pm-header-left">
                    <svg width="20" height="20" fill="none" stroke="#64748b" stroke-width="2.5" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Secure Checkout</span>
                </div>
                <button onclick="closeGcash()" class="pm-close">✕</button>
            </div>

            <div class="pm-body" style="text-align:center;">
                <div class="pm-merchant-badge">
                    <div class="pm-merchant-logo">🏘️</div>
                    <div style="text-align:left;">
                        <div style="font-size:13px;font-weight:800;color:#0f172a;">Althesa Property Mgmt.</div>
                        <div id="pmBillType" style="font-size:11px;color:#64748b;font-weight:600;">Utility Bill</div>
                    </div>
                </div>

                <div style="margin: 20px 0;">
                    <div style="font-size:11px;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;">Amount Due</div>
                    <div id="pmAmount" style="font-size:32px;font-weight:900;color:#0f172a;letter-spacing:-1px;">₱0.00</div>
                </div>

                <div class="pm-qr-container">
                    <div class="pm-qr-ph-logo">
                        <svg width="70" height="20" viewBox="0 0 100 30" fill="none">
                            <rect width="100" height="30" rx="4" fill="#fff"/>
                            <path d="M10 5L25 5L25 10L10 10Z" fill="#FFD700"/>
                            <path d="M10 10L17.5 25L10 25Z" fill="#0000FF"/>
                            <path d="M17.5 10L25 25L17.5 25Z" fill="#FF0000"/>
                            <text x="32" y="21" fill="#0f172a" style="font: bold 18px sans-serif;">QR Ph</text>
                        </svg>
                    </div>

                    <div class="pm-qr-canvas-wrapper">
                        <canvas id="pmQrCanvas" width="180" height="180" style="display:block;"></canvas>
                        
                        <!-- Processing Overlay -->
                        <div id="pmQrOverlay" class="pm-qr-status-overlay" style="display:none;">
                            <div style="text-align:center;">
                                <div class="pm-proc-spinner" style="margin:0 auto 12px;"></div>
                                <div style="font-size:13px;font-weight:700;color:#0f172a;">Confirming...</div>
                            </div>
                        </div>
                    </div>

                    <div id="pmRef" style="font-size:11px;color:#94a3b8;font-family:monospace;margin-top:12px;">REF-000000</div>
                </div>

                <div class="pm-instructions" style="background: #eff6ff; padding: 16px; border-radius: 12px; border: 1px solid #dbeafe;">
                    <div style="font-size:14px;font-weight:800;color:#1e40af;margin-bottom:6px;">QR Ph Payment</div>
                    <p style="font-size:12px;color:#1e40af;line-height:1.4;margin:0;">
                        GCash does not have a separate logo anymore. Simply use your <strong>GCash</strong> or any banking app to scan the <strong>QR Ph</strong> code above.
                    </p>
                </div>

                <div style="margin-top: 20px;">
                    <button class="pm-btn-primary" style="width:100%; background: #059669;" onclick="pmComplete()">I have completed the payment</button>
                </div>

                <div class="pm-secured">
                    <svg width="12" height="12" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-1 15l-3-3 1.41-1.41L11 13.17l4.59-4.58L17 10l-6 6z"/></svg>
                    <span>Secured by PayMongo</span>
                </div>
            </div>
        </div>


    </div>
</div>

<link rel="stylesheet" href="{{ asset('css/views/gcash-modal.css') }}">

<script>
(function(){
    let _pmCtx={};let _pmTimer=null;let _pmSeconds=900;

    window.openGcashPayment = function(billId,residentName,amount,billType){
        _pmCtx={billId,residentName,amount,billType,method:'qrph'};
        const fmt=v=>'₱'+Number(v).toLocaleString(undefined,{minimumFractionDigits:2});
        
        // Reset steps
        document.querySelectorAll('.pm-step').forEach(s=>s.style.display='none');
        document.getElementById('pmMainFlow').style.display='flex';
        document.getElementById('pmQrOverlay').style.display='none';
        
        // Populate UI
        const typeEl = document.getElementById('pmBillType');
        const amtEl = document.getElementById('pmAmount');
        const refEl = document.getElementById('pmRef');
        if(typeEl) typeEl.textContent=billType;
        if(amtEl) amtEl.textContent=fmt(amount);
        if(refEl) refEl.textContent='REF-'+billId;
        
        document.getElementById('gcashOverlay').style.display='flex';
        drawQR();
    };

    function pmSimulateSuccess() {
        const overlay = document.getElementById('pmQrOverlay');
        if(overlay) overlay.style.display = 'flex';
        setTimeout(() => {
            pmShowSuccess();
        }, 2000);
    }

    window.closeGcash = function(){
        document.getElementById('gcashOverlay').style.display='none';
    };

    function drawQR(){
        const c=document.getElementById('pmQrCanvas');
        if(!c) return;
        const x=c.getContext('2d');
        const size = 180;
        x.clearRect(0,0,size,size);
        x.fillStyle='#fff';
        x.fillRect(0,0,size,size);
        x.fillStyle='#0f172a';
        const s = size / 30; // Scale based on 30x30 grid
        const data=[];
        for(let i=0;i<30;i++){
            data[i]=[];
            for(let j=0;j<30;j++) data[i][j]=Math.random()>0.5?1:0;
        }
        // Static markers (QR standards)
        for(let i=0;i<7;i++)for(let j=0;j<7;j++){
            const v=(i===0||i===6||j===0||j===6||(i>=2&&i<=4&&j>=2&&j<=4))?1:0;
            data[i][j]=v;data[i][23+j]=v;data[23+i][j]=v;
        }
        for(let i=0;i<30;i++)for(let j=0;j<30;j++)if(data[i][j])x.fillRect(j*s,i*s,s,s);
    }

    window.pmComplete = function(){
        closeGcash();
        if(typeof onGcashPaymentComplete==='function') onGcashPaymentComplete(_pmCtx);
    };
})();
</script>
