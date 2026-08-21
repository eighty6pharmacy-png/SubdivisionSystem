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

<style>
.pm-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.7);backdrop-filter:blur(8px);align-items:center;justify-content:center;}
.pm-modal{width:380px;background:#fff;border-radius:28px;overflow:hidden;box-shadow:0 30px 60px rgba(0,0,0,0.3);display:flex;flex-direction:column;position:relative;}
.pm-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #f1f5f9;width:100%;box-sizing:border-box;}
.pm-header-left{display:flex;align-items:center;gap:10px;font-size:12px;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.8px;}
.pm-close{background:#f1f5f9;border:none;width:30px;height:30px;border-radius:50%;color:#94a3b8;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;transition:0.2s;padding:0;flex-shrink:0;}
.pm-close:hover{background:#e2e8f0;color:#0f172a;}
.pm-body{padding:24px;flex:1;width:100%;box-sizing:border-box;}
.pm-merchant-badge{display:flex;align-items:center;gap:12px;background:#f8fafc;padding:12px 16px;border-radius:16px;display:inline-flex;margin:0 auto;border:1px solid #f1f5f9;}
.pm-merchant-logo{width:34px;height:34px;background:#fff;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;box-shadow:0 4px 6px rgba(0,0,0,0.05);}
.pm-qr-container{background:#f8fafc;border:1.5px solid #f1f5f9;border-radius:24px;padding:24px;margin-bottom:20px;position:relative;display:flex;flex-direction:column;align-items:center;}
.pm-qr-ph-logo{margin-bottom:16px;}
.pm-qr-canvas-wrapper{position:relative;background:#fff;padding:12px;border-radius:16px;box-shadow:0 10px 20px rgba(0,0,0,0.05);display:inline-block;}
.pm-qr-status-overlay{position:absolute;inset:0;background:rgba(255,255,255,0.92);display:flex;align-items:center;justify-content:center;border-radius:16px;z-index:10;}
.pm-instructions{margin-bottom:20px;padding:0 10px;}
.pm-timer-bar{background:#f1f5f9;height:34px;border-radius:17px;position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800;color:#64748b;margin-bottom:20px;width:100%;}
.pm-timer-fill{position:absolute;left:0;top:0;bottom:0;background:#e2e8f0;width:100%;transition:width 1s linear;z-index:1;}
#pmTimerText{position:relative;z-index:2;}
.pm-success-check{width:84px;height:84px;background:#10b981;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;box-shadow:0 15px 30px rgba(16,185,129,0.35);animation:pmPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);}
@keyframes pmPop{from{transform:scale(0.4);opacity:0;}to{transform:scale(1);opacity:1;}}
.pm-receipt-box{background:#f8fafc;border-radius:20px;padding:24px;margin-top:24px;text-align:left;border:1px solid #f1f5f9;}
.pm-receipt-line{display:flex;justify-content:space-between;padding:12px 0;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;}
.pm-receipt-line span:last-child{font-weight:800;color:#0f172a;}
.pm-btn-primary{flex:1;padding:16px;background:#0f172a;color:#fff;border:none;border-radius:16px;font-size:14px;font-weight:800;cursor:pointer;transition:0.2s;box-shadow:0 10px 20px rgba(15,23,42,0.2);}
.pm-btn-secondary{flex:1;padding:16px;background:#fff;color:#0f172a;border:1.5px solid #f1f5f9;border-radius:16px;font-size:14px;font-weight:800;cursor:pointer;transition:0.2s;}
.pm-btn-primary:hover{background:#000;transform:translateY(-1px);}
.pm-btn-secondary:hover{background:#f8fafc;border-color:#e2e8f0;}
.pm-proc-spinner{width:28px;height:28px;border:3px solid #f1f5f9;border-top-color:#0f172a;border-radius:50%;animation:pmSpin .8s linear infinite;}
@keyframes pmSpin{to{transform:rotate(360deg);}}
.pm-secured{display:flex;align-items:center;justify-content:center;gap:6px;font-size:11px;color:#94a3b8;margin-top:4px;}
.pm-step{display:none;width:100%;flex-direction:column;align-items:center;}
@media(max-width:400px){.pm-modal{width:100%;height:100%;border-radius:0;max-height:100vh;}.pm-overlay{padding:0;}}
</style>

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
