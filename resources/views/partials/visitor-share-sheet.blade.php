<style>
    .vs-share-wrap {
        text-align: left;
        font-size: 13px;
        color: #334155;
    }
    .vs-share-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 10px 12px;
        margin-bottom: 14px;
    }
    .vs-share-meta-name {
        font-weight: 700;
        color: #0f172a;
        font-size: 13px;
    }
    .vs-share-meta-pin {
        font-family: monospace;
        font-weight: 800;
        font-size: 13px;
        color: #059669;
        background: #dcfce7;
        border: 1px solid #bbf7d0;
        padding: 3px 8px;
        border-radius: 6px;
        letter-spacing: 1px;
        white-space: nowrap;
    }
    .vs-share-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px 8px;
        margin-bottom: 16px;
    }
    .vs-share-app-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        background: transparent;
        border: none;
        padding: 4px 2px;
        cursor: pointer;
        color: #334155;
        font-size: 11px;
        font-weight: 600;
        transition: transform 0.15s ease;
    }
    .vs-share-app-btn:hover {
        transform: translateY(-2px);
    }
    .vs-share-icon {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        box-shadow: 0 3px 10px rgba(15, 23, 42, 0.12);
    }
    .vs-share-url-box {
        display: flex;
        gap: 6px;
        align-items: center;
        margin-bottom: 10px;
    }
    .vs-share-url-input {
        flex: 1;
        min-width: 0;
        padding: 8px 10px;
        font-size: 12px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #f8fafc;
        font-family: monospace;
        color: #0f172a;
    }
    .vs-share-copy-btn {
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        white-space: nowrap;
    }
    .vs-share-feedback {
        display: none;
        margin-top: 8px;
        padding: 8px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
        text-align: center;
    }
</style>

<script>
    window.escapeVisitorHtml = function(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    window.copyVisitorText = async function(text) {
        if (navigator.clipboard && window.isSecureContext) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (_) {}
        }
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-9999px';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            ta.setSelectionRange(0, text.length);
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            return ok;
        } catch (_) {
            return false;
        }
    };

    window.openSharePassFromBtn = function(btn) {
        if (!btn) return;
        const url = btn.dataset.url || '';
        const pin = btn.dataset.pin || '';
        const visitor = btn.dataset.visitor || 'Guest';
        window.openVisitorShareSheet(url, pin, visitor);
    };

    window.openVisitorShareSheet = function(url, pinCode, visitorName) {
        if (!url) return;

        const cleanName = (visitorName || 'Guest').trim();
        const cleanPin = (pinCode || '').trim();
        const pinPart = cleanPin ? ` Gate PIN: ${cleanPin}.` : '';
        const inviteWithoutUrl = `Althesa Subd (Zone 1, Tagbong, Pili): Hello ${cleanName}, your visitor pass is Approved!${pinPart} View your digital pass & route map:`;
        const fullInvite = `${inviteWithoutUrl} ${url}`;

        const showFeedback = (msg) => {
            const el = document.getElementById('vsShareFeedback');
            if (el) {
                el.textContent = msg;
                el.style.display = 'block';
            }
        };

        if (typeof Swal === 'undefined') {
            window.copyVisitorText(fullInvite).then((ok) => {
                if (ok) alert('Visitor pass invite copied! Paste it into Messenger, Viber, WhatsApp, or SMS.');
                else prompt('Copy this visitor pass link:', url);
            });
            return;
        }

        const canNativeShare = typeof navigator.share === 'function';
        const isMobile = /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
        const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);

        Swal.fire({
            title: 'Share Visitor Pass',
            width: 440,
            html: `
                <div class="vs-share-wrap">
                    <div class="vs-share-meta">
                        <span class="vs-share-meta-name">👤 ${window.escapeVisitorHtml(cleanName)}</span>
                        ${cleanPin ? `<span class="vs-share-meta-pin">PIN: ${window.escapeVisitorHtml(cleanPin)}</span>` : ''}
                    </div>

                    <div class="vs-share-grid">
                        <button type="button" class="vs-share-app-btn" data-share-app="messenger">
                            <span class="vs-share-icon" style="background: linear-gradient(135deg, #00B2FF, #006AFF);">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.477 2 2 6.145 2 11.259c0 2.915 1.454 5.512 3.726 7.21V22l3.405-1.869c.909.252 1.872.387 2.869.387 5.523 0 10-4.145 10-9.259S17.523 2 12 2zm1.002 12.463l-2.548-2.717-4.97 2.717 5.467-5.803 2.61 2.717 4.908-2.717-5.467 5.803z"/></svg>
                            </span>
                            <span>Messenger</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="whatsapp">
                            <span class="vs-share-icon" style="background: #25D366;">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12.031 2c-5.516 0-9.969 4.453-9.969 9.969 0 1.766.469 3.484 1.344 5l-1.406 5.156 5.281-1.375c1.469.797 3.109 1.219 4.75 1.219 5.516 0 9.969-4.453 9.969-9.969S17.547 2 12.031 2zm5.813 14.125c-.25.703-1.438 1.344-2 1.422-.516.078-1.172.109-1.891-.125-.438-.141-1-.328-1.719-.641-3.016-1.297-4.984-4.344-5.141-4.547-.156-.203-1.234-1.641-1.234-3.125s.781-2.219 1.063-2.516c.281-.297.609-.375.813-.375s.406 0 .578.016c.188.016.438-.078.688.516.25.609.859 2.094.938 2.25.078.156.125.344.031.547-.094.203-.141.328-.297.5-.156.172-.313.391-.453.516-.156.156-.313.313-.141.625.172.313.781 1.297 1.672 2.094 1.156 1.031 2.125 1.344 2.438 1.5.313.156.484.125.672-.078.188-.203.766-.891.969-1.203.203-.313.406-.25.688-.156.281.094 1.781.844 2.094 1 .313.156.516.234.594.359.078.125.078.734-.172 1.438z"/></svg>
                            </span>
                            <span>WhatsApp</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="viber">
                            <span class="vs-share-icon" style="background: #7360F2;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor"><path d="M11.4 1.5C5.9 1.5 2 4.9 2 10.1c0 2.9 1.2 5.3 3.4 6.9v4.2l3.8-2.1c.7.1 1.5.2 2.2.2 5.5 0 9.6-3.5 9.6-8.7C21 4.9 16.9 1.5 11.4 1.5zm4.8 12.1c-.2.6-1.2 1.1-1.7 1.2-.5.1-1 .1-1.7-.1-.4-.1-.9-.3-1.6-.6-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.9s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.4.2.5.7 1.8.8 1.9.1.2.1.3 0 .5-.1.2-.2.3-.3.5-.1.1-.3.3-.4.4-.1.1-.3.3-.1.6.2.3.7 1.2 1.5 1.9 1 .9 1.9 1.2 2.2 1.3.3.1.5.1.6-.1.2-.2.7-.8.9-1.1.2-.3.4-.2.6-.1.3.1 1.6.8 1.9.9.3.1.4.2.5.3.1.2.1.7-.1 1.3z"/></svg>
                            </span>
                            <span>Viber</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="telegram">
                            <span class="vs-share-icon" style="background: #229ED9;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="currentColor"><path d="M9.78 18.65l.28-4.23 7.68-6.92c.34-.31-.07-.46-.52-.19L7.74 13.3 3.64 12c-.88-.25-.89-.86.2-1.3l15.97-6.16c.73-.33 1.43.18 1.15 1.3l-2.72 12.81c-.19.91-.74 1.13-1.5.71L12.6 16.3l-1.99 1.93c-.23.23-.42.42-.83.42z"/></svg>
                            </span>
                            <span>Telegram</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="sms">
                            <span class="vs-share-icon" style="background: #10B981;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                            </span>
                            <span>Messages</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="email">
                            <span class="vs-share-icon" style="background: #64748B;">
                                <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            </span>
                            <span>Email</span>
                        </button>

                        <button type="button" class="vs-share-app-btn" data-share-app="copy-invite">
                            <span class="vs-share-icon" style="background: #0F172A;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                            </span>
                            <span>Copy Invite</span>
                        </button>

                        ${canNativeShare ? `
                        <button type="button" class="vs-share-app-btn" data-share-app="native">
                            <span class="vs-share-icon" style="background: #6366F1;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                            </span>
                            <span>More Apps</span>
                        </button>
                        ` : ''}
                    </div>

                    <label style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Direct Pass Link:</label>
                    <div class="vs-share-url-box">
                        <input id="vsShareUrlInput" type="text" class="vs-share-url-input" value="${window.escapeVisitorHtml(url)}" readonly>
                        <button type="button" id="vsShareCopyLinkBtn" class="vs-share-copy-btn" style="background: #0284c7; color: #fff;">Copy Link</button>
                    </div>

                    <div id="vsShareFeedback" class="vs-share-feedback"></div>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Done',
            confirmButtonColor: '#334155',
            didOpen: (popup) => {
                const urlInput = popup.querySelector('#vsShareUrlInput');
                if (urlInput) {
                    urlInput.addEventListener('click', () => urlInput.select());
                }

                const copyLinkBtn = popup.querySelector('#vsShareCopyLinkBtn');
                if (copyLinkBtn) {
                    copyLinkBtn.addEventListener('click', async () => {
                        const ok = await window.copyVisitorText(url);
                        showFeedback(ok ? 'Direct pass link copied to clipboard!' : 'Could not copy automatically. Please select and copy the link above.');
                    });
                }

                popup.querySelectorAll('[data-share-app]').forEach((btn) => {
                    btn.addEventListener('click', async () => {
                        const app = btn.dataset.shareApp;
                        const encodedUrl = encodeURIComponent(url);
                        const encodedText = encodeURIComponent(inviteWithoutUrl);
                        const encodedInvite = encodeURIComponent(fullInvite);
                        const encodedSubject = encodeURIComponent(`Althesa Subdivision Visitor Pass - ${cleanName}`);

                        if (app === 'native' && canNativeShare) {
                            try {
                                await navigator.share({
                                    title: 'Althesa Subdivision Visitor Pass',
                                    text: inviteWithoutUrl,
                                    url: url,
                                });
                            } catch (err) {
                                if (err && err.name !== 'AbortError') {
                                    await window.copyVisitorText(fullInvite);
                                    showFeedback('Native share unavailable here. Full invite copied to clipboard!');
                                }
                            }
                        } else if (app === 'messenger') {
                            await window.copyVisitorText(fullInvite);
                            if (isMobile) {
                                showFeedback('Opening Messenger... (Full invite also copied to clipboard)');
                                window.location.href = `fb-messenger://share/?link=${encodedUrl}`;
                            } else {
                                showFeedback('Full invite copied! Paste it into your Messenger chat.');
                                window.open('https://www.messenger.com/', '_blank', 'noopener');
                            }
                        } else if (app === 'whatsapp') {
                            window.open(`https://wa.me/?text=${encodedInvite}`, '_blank', 'noopener');
                        } else if (app === 'viber') {
                            window.location.href = `viber://forward?text=${encodedInvite}`;
                        } else if (app === 'telegram') {
                            window.open(`https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`, '_blank', 'noopener');
                        } else if (app === 'sms') {
                            window.location.href = isIOS ? `sms:&body=${encodedInvite}` : `sms:?body=${encodedInvite}`;
                        } else if (app === 'email') {
                            window.location.href = `mailto:?subject=${encodedSubject}&body=${encodedInvite}`;
                        } else if (app === 'copy-invite') {
                            const ok = await window.copyVisitorText(fullInvite);
                            showFeedback(ok ? 'Full invite (PIN + link) copied! Paste it into any chat app.' : 'Could not copy automatically. Please copy the link above.');
                        }
                    });
                });
            }
        });
    };

    // Backwards-compatible alias
    window.copyPassLink = window.openVisitorShareSheet;
</script>
